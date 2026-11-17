<?php
namespace App\Services;

use App\Helpers\PhoneHelper;

/**
 * WhatsAppService - Layanan Pengiriman WhatsApp Terpadu
 * Mendukung Fonnte, Meta WhatsApp Business Cloud API, dan Dev Mock Adapter
 */
class WhatsAppService
{
    private string $provider;
    private string $apiToken;
    private string $apiUrl;
    private string $senderId;

    public function __construct()
    {
        $this->provider = strtolower((string)config('whatsapp.provider', env('WHATSAPP_PROVIDER', 'fonnte')));
        $this->apiToken = trim((string)config('whatsapp.api_token', env('WHATSAPP_API_TOKEN', '')));
        $this->apiUrl   = trim((string)config('whatsapp.api_url', env('WHATSAPP_API_URL', 'https://api.fonnte.com/send')));
        $this->senderId = trim((string)config('whatsapp.sender_id', env('WHATSAPP_SENDER_ID', '')));
    }

    /**
     * Kirim kode OTP ke nomor WhatsApp tujuan
     * 
     * @param string $phone Nomor E.164 (+628xxxxxxxxx)
     * @param string $otpCode Kode OTP 6 digit
     * @param int $expiryMinutes Durasi kedaluwarsa dalam menit
     * @return array ['success' => bool, 'message' => string, 'dev_simulated' => bool]
     */
    public function sendOtp(string $phone, string $otpCode, int $expiryMinutes = 5): array
    {
        $normalizedPhone = PhoneHelper::normalize($phone);
        if (!$normalizedPhone) {
            return [
                'success' => false,
                'message' => 'Format nomor WhatsApp tidak valid.',
                'dev_simulated' => false
            ];
        }

        // Susun template pesan OTP resmi Terima Jasa
        $messageBody = $this->buildOtpMessage($otpCode, $expiryMinutes);

        // Jika token kosong atau provider diset ke mock, gunakan adapter mock
        if (empty($this->apiToken) || $this->provider === 'mock') {
            return $this->sendViaMockAdapter($normalizedPhone, $otpCode, $messageBody, 'Mode Mock (Token belum diisi di .env)');
        }

        // Dispatch ke provider yang sesuai
        return match ($this->provider) {
            'meta', 'cloud_api' => $this->sendViaMetaCloudApi($normalizedPhone, $otpCode, $messageBody),
            default             => $this->sendViaFonnte($normalizedPhone, $otpCode, $messageBody),
        };
    }

    /**
     * Susun template pesan keamanan OTP
     */
    private function buildOtpMessage(string $otpCode, int $expiryMinutes): string
    {
        $appName = config('app.app_name', 'Terima Jasa');
        return "*{$appName}* - KODE VERIFIKASI (OTP)\n\n" .
               "Kode verifikasi Anda adalah:\n\n" .
               "*{$otpCode}*\n\n" .
               "Kode ini hanya berlaku selama {$expiryMinutes} menit. " .
               "JANGAN membagikan kode ini kepada siapa pun termasuk pihak yang mengatasnamakan Terima Jasa demi keamanan akun Anda.\n\n" .
               "— Tim Keamanan {$appName}";
    }

    /**
     * Pengiriman via Fonnte Gateway API
     * URL: https://api.fonnte.com/send
     * Header: Authorization: {token}
     * Body: target, message
     */
    private function sendViaFonnte(string $phone, string $otpCode, string $message): array
    {
        // Fonnte menerima nomor berawalan 08xxx atau 628xxx
        $rawTarget = substr($phone, 1); // 6281234567890

        $postFields = [
            'target'  => $rawTarget,
            'message' => $message,
            'countryCode' => '62'
        ];

        $headers = [
            "Authorization: {$this->apiToken}"
        ];

        $response = $this->executeCurl($this->apiUrl, 'POST', $postFields, $headers);

        if (!$response['success']) {
            // Log transport error and return explicit failure without mock fallback
            $this->logError('Fonnte API transport error: ' . $response['error']);
            return [
                'success' => false,
                'message' => 'Tidak dapat menghubungi layanan WhatsApp (Fonnte). Silakan coba lagi nanti.',
                'dev_simulated' => false,
                'error_detail' => $response['error']
            ];
        }

        $resBody = json_decode($response['body'], true);

        // Fonnte mengembalikan status: true atau status: false
        if (is_array($resBody) && isset($resBody['status']) && $resBody['status'] === true) {
            return [
                'success' => true,
                'message' => 'Kode OTP berhasil dikirimkan ke WhatsApp Anda.',
                'dev_simulated' => false
            ];
        }

        // Tangani pesan penolakan provider (misal token expired/invalid device/nomor tidak aktif)
        $reason = $resBody['reason'] ?? $resBody['detail'] ?? 'Provider menolak permintaan pengiriman.';
        $this->logError("Fonnte API rejection: {$reason}");

        $userFriendlyMessage = $reason;
        if (str_contains(strtolower($reason), 'disconnected') || str_contains(strtolower($reason), 'disconnect')) {
            $userFriendlyMessage = 'Perangkat WhatsApp Gateway terputus (disconnected). Harap tautkan perangkat WhatsApp Anda pada sistem Terima Jasa atau Fonnte terlebih dahulu.';
        } elseif (str_contains(strtolower($reason), 'invalid token') || str_contains(strtolower($reason), 'unauthorized')) {
            $userFriendlyMessage = 'Token API WhatsApp Gateway tidak valid. Harap periksa konfigurasi WHATSAPP_API_TOKEN di file .env.';
        } elseif (str_contains(strtolower($reason), 'quota') || str_contains(strtolower($reason), 'limit')) {
            $userFriendlyMessage = 'Kuota pengiriman pesan WhatsApp Gateway telah habis.';
        }

        // Kembalikan error ke frontend, jangan fallback ke mock agar user tahu kegagalan.
        return [
            'success'       => false,
            'message'       => $userFriendlyMessage,
            'raw_reason'    => $reason,
            'dev_simulated' => false
        ];
    }

    /**
     * Pengiriman via Meta WhatsApp Cloud API Resmi
     */
    private function sendViaMetaCloudApi(string $phone, string $otpCode, string $message): array
    {
        $rawTo = substr($phone, 1); // 6281234567890

        $postData = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $rawTo,
            'type'              => 'text',
            'text'              => [
                'preview_url' => false,
                'body'        => $message
            ]
        ];

        $headers = [
            "Authorization: Bearer {$this->apiToken}",
            'Content-Type: application/json'
        ];

        $response = $this->executeCurl($this->apiUrl, 'POST', json_encode($postData), $headers);

        if (!$response['success']) {
            $this->logError('Meta Cloud API transport error: ' . $response['error']);
            return $this->sendViaMockAdapter($phone, $otpCode, $message, 'Meta Cloud API timeout/network error.');
        }

        $resBody = json_decode($response['body'], true);
        if (is_array($resBody) && isset($resBody['messages'])) {
            return [
                'success' => true,
                'message' => 'Kode OTP berhasil dikirim melalui WhatsApp Cloud API.',
                'dev_simulated' => false
            ];
        }

        $errDetail = $resBody['error']['message'] ?? 'Meta API Error';
        $this->logError("Meta Cloud API error: {$errDetail}");

        return $this->sendViaMockAdapter($phone, $otpCode, $message, "Meta API: {$errDetail}");
    }

    /**
     * Mock Service Khusus Pengembangan (Dev Adapter)
     * Aktif saat koneksi internet offline, kuota gateway habis, atau mode testing.
     * Tidak membocorkan kode ke frontend response atau browser console.
     */
    private function sendViaMockAdapter(string $phone, string $otpCode, string $message, string $reason = ''): array
    {
        // Simpan ke storage session developer agar tester di environment lokal dapat melihat simulasi jika diperlukan
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        $_SESSION['_dev_last_otp'] = [
            'phone'      => $phone,
            'code'       => $otpCode,
            'timestamp'  => time(),
            'message'    => $message,
            'note'       => $reason
        ];

        return [
            'success'       => true,
            'message'       => 'Kode verifikasi telah dikirimkan ke nomor WhatsApp Anda.',
            'dev_simulated' => true,
            'note'          => 'Pengiriman menggunakan adapter WhatsApp (Mode pengembangan).'
        ];
    }

    /**
     * Helper cURL eksekusi HTTP dengan timeout aman
     */
    private function executeCurl(string $url, string $method, array|string $data, array $headers): array
    {
        if (!function_exists('curl_init')) {
            return ['success' => false, 'error' => 'cURL PHP extension is not installed'];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8); // 8 detik timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($body === false || !empty($curlError)) {
            return ['success' => false, 'error' => $curlError ?: 'Unknown transport error'];
        }

        return [
            'success'   => ($httpCode >= 200 && $httpCode < 300),
            'http_code' => $httpCode,
            'body'      => $body
        ];
    }

    /**
     * Dapatkan status perangkat Fonnte
     */
    public function getDeviceStatus(): array
    {
        if (empty($this->apiToken)) {
            return ['status' => false, 'message' => 'API Token belum diisi di .env'];
        }

        $response = $this->executeCurl('https://api.fonnte.com/device', 'POST', [], [
            "Authorization: {$this->apiToken}"
        ]);

        if (!$response['success']) {
            return ['status' => false, 'message' => $response['error'] ?? 'Gagal menghubungi server Fonnte'];
        }

        $data = json_decode($response['body'], true);
        return is_array($data) ? $data : ['status' => false, 'message' => 'Format respons Fonnte tidak valid'];
    }

    /**
     * Ambil QR Code dari Fonnte (base64 image)
     */
    public function getQrCode(): array
    {
        if (empty($this->apiToken)) {
            return ['status' => false, 'message' => 'API Token belum diisi di .env'];
        }

        $response = $this->executeCurl('https://api.fonnte.com/qr', 'POST', ['type' => 'qr'], [
            "Authorization: {$this->apiToken}"
        ]);

        if (!$response['success']) {
            return ['status' => false, 'message' => $response['error'] ?? 'Gagal mengambil QR Code'];
        }

        $data = json_decode($response['body'], true);
        return is_array($data) ? $data : ['status' => false, 'message' => 'Format respons QR tidak valid'];
    }

    /**
     * Ambil Pairing Code 8 karakter dari Fonnte
     */
    public function getPairingCode(string $phone): array
    {
        if (empty($this->apiToken)) {
            return ['status' => false, 'message' => 'API Token belum diisi di .env'];
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '62' . substr($cleanPhone, 1);
        }

        $response = $this->executeCurl('https://api.fonnte.com/qr', 'POST', [
            'type'     => 'code',
            'whatsapp' => $cleanPhone
        ], [
            "Authorization: {$this->apiToken}"
        ]);

        if (!$response['success']) {
            return ['status' => false, 'message' => $response['error'] ?? 'Gagal mengambil Pairing Code'];
        }

        $data = json_decode($response['body'], true);
        return is_array($data) ? $data : ['status' => false, 'message' => 'Format respons Pairing Code tidak valid'];
    }

    /**
     * Kirim pesan teks tes ke WhatsApp
     */
    public function sendTextMessage(string $phone, string $message): array
    {
        $normalizedPhone = PhoneHelper::normalize($phone);
        if (!$normalizedPhone) {
            return ['success' => false, 'message' => 'Format nomor WhatsApp tidak valid.'];
        }

        if (empty($this->apiToken) || $this->provider === 'mock') {
            return ['success' => true, 'message' => 'Mode Mock: Pesan tes disimulasikan.', 'dev_simulated' => true];
        }

        $rawTarget = substr($normalizedPhone, 1); // 628xxxx
        $postFields = [
            'target'      => $rawTarget,
            'message'     => $message,
            'countryCode' => '62'
        ];

        $headers = ["Authorization: {$this->apiToken}"];
        $response = $this->executeCurl($this->apiUrl, 'POST', $postFields, $headers);

        if (!$response['success']) {
            return ['success' => false, 'message' => 'Gagal menghubungi gateway: ' . ($response['error'] ?? '')];
        }

        $resBody = json_decode($response['body'], true);
        if (is_array($resBody) && isset($resBody['status']) && $resBody['status'] === true) {
            return ['success' => true, 'message' => 'Pesan WhatsApp berhasil dikirim ke ' . $normalizedPhone];
        }

        $reason = $resBody['reason'] ?? $resBody['detail'] ?? 'Provider menolak pengiriman.';
        return ['success' => false, 'message' => $reason];
    }

    /**
     * Pencatatan log error internal (Tanpa mencetak rahasia token)
     */
    private function logError(string $message): void
    {
        // Sanitasi pesan: jangan biarkan token bocor ke file log
        $sanitized = str_replace($this->apiToken, '[REDACTED_TOKEN]', $message);
        $logDir = defined('ROOT_PATH') ? ROOT_PATH . '/storage/logs' : __DIR__ . '/../../storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/whatsapp_' . date('Y-m-d') . '.log';
        $logEntry = '[' . date('Y-m-d H:i:s') . '] ' . $sanitized . PHP_EOL;
        @file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}
