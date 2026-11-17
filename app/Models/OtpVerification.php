<?php
namespace App\Models;

use App\Core\Model;
use App\Helpers\PhoneHelper;
use PDO;

/**
 * OtpVerification Model
 * Mengelola Siklus Hidup Kode OTP, Hashing Kriptografis, Rate Limiting, dan Proteksi Brute Force
 */
class OtpVerification extends Model
{
    protected string $table = 'otp_verifications';

    protected array $fillable = [
        'user_id',
        'phone',
        'otp_hash',
        'expires_at',
        'attempts',
        'max_attempts',
        'resend_available_at',
        'consumed_at',
        'ip_address',
        'user_agent',
        'created_at',
        'updated_at'
    ];

    /**
     * Buat transaksi OTP baru dengan kode acak aman secara kriptografis
     */
    public static function generateOtp(
        string $phone,
        ?int $userId = null,
        string $ip = '127.0.0.1',
        string $userAgent = '',
        int $expirySeconds = 300,
        int $cooldownSeconds = 60
    ): array {
        $normalizedPhone = PhoneHelper::normalize($phone);
        if (!$normalizedPhone) {
            throw new \InvalidArgumentException('Nomor telepon tidak valid.');
        }

        // 1. Invaliasi/batalkan seluruh OTP lama yang belum terpakai untuk nomor ini
        self::invalidatePendingOtps($normalizedPhone);

        // 2. Generate kode 6 digit kriptografis aman
        $otpPlain = (string)random_int(100000, 999999);

        // 3. Hash OTP menggunakan Bcrypt
        $otpHash = password_hash($otpPlain, PASSWORD_BCRYPT);

        $now = time();
        $expiresAt = date('Y-m-d H:i:s', $now + $expirySeconds);
        $resendAvailableAt = date('Y-m-d H:i:s', $now + $cooldownSeconds);

        $attributes = [
            'user_id'             => $userId,
            'phone'               => $normalizedPhone,
            'otp_hash'            => $otpHash,
            'expires_at'          => $expiresAt,
            'attempts'            => 0,
            'max_attempts'        => 5,
            'resend_available_at' => $resendAvailableAt,
            'consumed_at'         => null,
            'ip_address'          => $ip,
            'user_agent'          => $userAgent,
            'created_at'          => date('Y-m-d H:i:s', $now),
            'updated_at'          => date('Y-m-d H:i:s', $now)
        ];

        $model = self::create($attributes);

        // Fallback simpan ke session jika DB tidak tersedia
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        $_SESSION['_active_otp_tx_' . md5($normalizedPhone)] = array_merge($attributes, [
            'id' => $model ? $model->id : time()
        ]);

        return [
            'otp'                 => $otpPlain,
            'model'               => $model,
            'normalized_phone'    => $normalizedPhone,
            'expires_in'          => $expirySeconds,
            'resend_cooldown'     => $cooldownSeconds,
            'expires_at'          => $expiresAt,
            'resend_available_at' => $resendAvailableAt
        ];
    }

    /**
     * Dapatkan transaksi OTP aktif terakhir untuk nomor tertentu
     */
    public static function getActiveRecord(string $phone): ?self
    {
        $normalizedPhone = PhoneHelper::normalize($phone);
        if (!$normalizedPhone) {
            return null;
        }

        $db = self::getDb();
        if ($db) {
            $stmt = $db->prepare("
                SELECT * FROM `otp_verifications`
                WHERE `phone` = ? AND `consumed_at` IS NULL
                ORDER BY `id` DESC LIMIT 1
            ");
            $stmt->execute([$normalizedPhone]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $instance = new self();
                $instance->attributes = $row;
                return $instance;
            }
        }

        // Fallback dari session
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        $sessionKey = '_active_otp_tx_' . md5($normalizedPhone);
        if (!empty($_SESSION[$sessionKey])) {
            $data = $_SESSION[$sessionKey];
            if (empty($data['consumed_at'])) {
                $instance = new self();
                $instance->attributes = $data;
                return $instance;
            }
        }

        return null;
    }

    /**
     * Verifikasi kode OTP dengan proteksi timing attack, brute force, dan batas percobaan
     */
    public static function verify(string $phone, string $inputOtp): array
    {
        $cleanOtp = preg_replace('/[^\d]/', '', trim($inputOtp));
        if (strlen($cleanOtp) !== 6) {
            return [
                'success' => false,
                'message' => 'Kode OTP harus terdiri dari 6 digit angka.',
                'status'  => 'invalid_format'
            ];
        }

        $record = self::getActiveRecord($phone);
        if (!$record) {
            return [
                'success' => false,
                'message' => 'Tidak ditemukan permintaan OTP aktif. Silakan minta kode baru.',
                'status'  => 'not_found'
            ];
        }

        $now = time();
        $expiresTimestamp = strtotime((string)$record->expires_at);

        // 1. Cek kedaluwarsa
        if ($now > $expiresTimestamp) {
            return [
                'success' => false,
                'message' => 'Kode OTP telah kedaluwarsa. Silakan kirim ulang kode baru.',
                'status'  => 'expired'
            ];
        }

        // 2. Cek apakah sudah pernah digunakan (consumed)
        if (!empty($record->consumed_at)) {
            return [
                'success' => false,
                'message' => 'Kode OTP ini sudah digunakan sebelumnya.',
                'status'  => 'already_consumed'
            ];
        }

        // 3. Cek apakah batas percobaan (max_attempts) sudah habis
        $attempts = (int)$record->attempts;
        $maxAttempts = (int)($record->max_attempts ?: 5);

        if ($attempts >= $maxAttempts) {
            return [
                'success' => false,
                'message' => 'Batas percobaan OTP telah habis (maksimal 5 kali). Silakan minta kode baru.',
                'status'  => 'max_attempts_exceeded'
            ];
        }

        // 4. Verifikasi kecocokan OTP menggunakan password_verify (aman dari timing attack)
        $isMatch = password_verify($cleanOtp, $record->otp_hash);

        if (!$isMatch) {
            // Tingkatkan counter percobaan gagal
            $newAttempts = $attempts + 1;
            $record->attempts = $newAttempts;
            $record->updated_at = date('Y-m-d H:i:s');
            $record->save();

            // Sync ke session fallback jika ada
            if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
                session_start();
            }
            $sessionKey = '_active_otp_tx_' . md5($record->phone);
            if (isset($_SESSION[$sessionKey])) {
                $_SESSION[$sessionKey]['attempts'] = $newAttempts;
            }

            $remaining = $maxAttempts - $newAttempts;
            if ($remaining <= 0) {
                return [
                    'success'            => false,
                    'message'            => 'Batas maksimal 5 kali percobaan telah habis. Silakan klik "Kirim Ulang OTP".',
                    'status'             => 'max_attempts_exceeded',
                    'remaining_attempts' => 0
                ];
            }

            return [
                'success'            => false,
                'message'            => "Kode OTP tidak valid. Sisa percobaan: {$remaining} kali.",
                'status'             => 'invalid_code',
                'remaining_attempts' => $remaining
            ];
        }

        // 5. Berhasil! Tandai OTP sebagai telah digunakan (consumed)
        $record->consumed_at = date('Y-m-d H:i:s');
        $record->updated_at = date('Y-m-d H:i:s');
        $record->save();

        // Update di session fallback
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        $sessionKey = '_active_otp_tx_' . md5($record->phone);
        if (isset($_SESSION[$sessionKey])) {
            $_SESSION[$sessionKey]['consumed_at'] = $record->consumed_at;
        }

        return [
            'success' => true,
            'message' => 'Verifikasi OTP berhasil.',
            'record'  => $record
        ];
    }

    /**
     * Invaliasi OTP pending yang belum dikonsumsi saat user meminta OTP baru
     */
    public static function invalidatePendingOtps(string $phone): void
    {
        $db = self::getDb();
        if ($db) {
            $stmt = $db->prepare("
                UPDATE `otp_verifications`
                SET `consumed_at` = NOW()
                WHERE `phone` = ? AND `consumed_at` IS NULL
            ");
            $stmt->execute([$phone]);
        }

        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        $sessionKey = '_active_otp_tx_' . md5($phone);
        unset($_SESSION[$sessionKey]);
    }

    /**
     * Cek aturan Rate Limiting & Cooldown sebelum mengirim OTP
     */
    public static function checkRateLimit(string $phone, string $ip): array
    {
        $normalizedPhone = PhoneHelper::normalize($phone);
        if (!$normalizedPhone) {
            return ['allowed' => false, 'message' => 'Format nomor tidak valid.'];
        }

        $now = time();
        $db = self::getDb();

        // 1. Cek Jeda Pengiriman Ulang (Cooldown e.g. 60 detik)
        $active = self::getActiveRecord($normalizedPhone);
        if ($active && !empty($active->resend_available_at)) {
            $resendTs = strtotime($active->resend_available_at);
            if ($now < $resendTs) {
                $secondsLeft = $resendTs - $now;
                return [
                    'allowed'     => false,
                    'message'     => "Mohon tunggu {$secondsLeft} detik sebelum meminta kode OTP kembali.",
                    'cooldown'    => $secondsLeft,
                    'type'        => 'cooldown'
                ];
            }
        }

        // 2. Cek Rate Limit Frekuensi Permintaan OTP (Maksimal per jam)
        $maxPerPhone = (int)config('whatsapp.rate_limit_phone', 5); // 5 OTP/jam per nomor
        $maxPerIp    = (int)config('whatsapp.rate_limit_ip', 15);   // 15 OTP/jam per IP

        if ($db) {
            $oneHourAgo = date('Y-m-d H:i:s', $now - 3600);

            // Periksa limit per nomor telepon
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM `otp_verifications`
                WHERE `phone` = ? AND `created_at` >= ?
            ");
            $stmt->execute([$normalizedPhone, $oneHourAgo]);
            $phoneCount = (int)$stmt->fetchColumn();

            if ($phoneCount >= $maxPerPhone) {
                return [
                    'allowed' => false,
                    'message' => 'Anda telah mencapai batas maksimal permintaan OTP untuk nomor ini. Silakan coba kembali dalam 1 jam.',
                    'type'    => 'rate_limit_phone'
                ];
            }

            // Periksa limit per IP address
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM `otp_verifications`
                WHERE `ip_address` = ? AND `created_at` >= ?
            ");
            $stmt->execute([$ip, $oneHourAgo]);
            $ipCount = (int)$stmt->fetchColumn();

            if ($ipCount >= $maxPerIp) {
                return [
                    'allowed' => false,
                    'message' => 'Terlalu banyak permintaan OTP dari perangkat/jaringan ini. Silakan coba beberapa saat lagi.',
                    'type'    => 'rate_limit_ip'
                ];
            }
        }

        return ['allowed' => true];
    }
}
