<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Auth;
use App\Models\User;
use App\Models\OtpVerification;
use App\Services\WhatsAppService;
use App\Helpers\PhoneHelper;

/**
 * WhatsAppAuthController - Mengelola Otentikasi WhatsApp & Verifikasi OTP
 * Berbasis Standar Keamanan: Bcrypt hashing, anti-brute force, rate limiting, anti-account enumeration
 */
class WhatsAppAuthController extends Controller
{
    private WhatsAppService $whatsAppService;

    public function __construct()
    {
        $this->whatsAppService = new WhatsAppService();
    }

    /**
     * Tampilkan Halaman Login WhatsApp
     * GET /login atau GET /login/whatsapp
     */
    public function showLogin(Request $request): void
    {
        if (Auth::check()) {
            $this->redirect(Auth::dashboardUrl());
            return;
        }

        $activeRole = $request->input('role', User::ROLE_CUSTOMER);
        $mockUsers = [
            'customer' => ['email' => 'customer@terimajasa.id', 'role' => 'customer', 'name' => 'Budi Santoso', 'phone' => '081234567890'],
            'teknisi'  => ['email' => 'teknisi@terimajasa.id', 'role' => 'teknisi', 'name' => 'Hendra Wijaya', 'phone' => '082198765432'],
            'admin'    => ['email' => 'admin@terimajasa.id', 'role' => 'admin', 'name' => 'Super Admin Terima Jasa', 'phone' => '081100001111']
        ];

        $this->view('auth/login', [
            'pageTitle'  => 'Login ke Terima Jasa',
            'activeRole' => $activeRole,
            'mockUsers'  => $mockUsers
        ], 'auth');

        unset($_SESSION['errors'], $_SESSION['old']);
    }

    /**
     * Tampilkan Halaman Verifikasi OTP
     * GET /login/whatsapp/verify
     */
    public function showVerify(Request $request): void
    {
        if (Auth::check()) {
            $this->redirect(Auth::dashboardUrl());
            return;
        }

        $phone = $request->input('phone') ?? ($_SESSION['otp_target_phone'] ?? '');
        $normalized = PhoneHelper::normalize($phone);

        if (!$normalized) {
            $_SESSION['flash_error'] = 'Silakan masukkan nomor WhatsApp Anda terlebih dahulu.';
            $this->redirect('login');
            return;
        }

        // Ambil data transaksi aktif
        $activeRecord = OtpVerification::getActiveRecord($normalized);
        $expirySeconds = (int)config('whatsapp.otp_expiry', 300);
        $remainingSeconds = $expirySeconds;
        $resendCooldown = (int)config('whatsapp.resend_cooldown', 60);

        if ($activeRecord) {
            $now = time();
            $expTs = strtotime($activeRecord->expires_at);
            $remainingSeconds = max(0, $expTs - $now);

            $resendTs = strtotime($activeRecord->resend_available_at);
            $resendCooldown = max(0, $resendTs - $now);
        }

        $this->view('auth/verify_otp', [
            'pageTitle'        => 'Verifikasi Nomor WhatsApp — Terima Jasa',
            'phone'            => $normalized,
            'maskedPhone'      => PhoneHelper::mask($normalized),
            'formattedPhone'   => PhoneHelper::formatDisplay($normalized),
            'remainingSeconds' => $remainingSeconds,
            'resendCooldown'   => $resendCooldown,
            'activeRecord'     => $activeRecord
        ], 'auth');

        unset($_SESSION['errors'], $_SESSION['old']);
    }

    /**
     * Endpoint API: Permintaan Kode OTP Baru
     * POST /api/auth/whatsapp/request-otp
     * Request JSON / POST: { "phone": "+6281234567890" }
     */
    public function requestOtp(Request $request): void
    {
        $rawPhone = (string)$request->input('phone', '');

        // 1. Validasi Keberadaan Input
        if (empty(trim($rawPhone))) {
            Response::json([
                'success' => false,
                'message' => 'Nomor WhatsApp wajib diisi.'
            ], 422);
            return;
        }

        // 2. Normalisasi dan Validasi Format Seluler Indonesia (E.164)
        $normalizedPhone = PhoneHelper::normalize($rawPhone);
        if (!$normalizedPhone) {
            Response::json([
                'success' => false,
                'message' => 'Format nomor WhatsApp tidak valid. Gunakan format seluler Indonesia yang benar (contoh: +62 812-3456-7890 atau 081234567890).'
            ], 422);
            return;
        }

        // 3. Rate Limiting (Berdasarkan IP dan Nomor Telepon)
        $rateLimit = OtpVerification::checkRateLimit($normalizedPhone, $request->ip());
        if (!$rateLimit['allowed']) {
            Response::json([
                'success'  => false,
                'message'  => $rateLimit['message'],
                'cooldown' => $rateLimit['cooldown'] ?? 0,
                'type'     => $rateLimit['type'] ?? 'rate_limited'
            ], 429);
            return;
        }

        // 4. Periksa Keberadaan Akun & Kebijakan Status
        // Catatan Keamanan: Untuk akun nonaktif/suspended, berikan notifikasi yang sesuai.
        // Untuk akun belum terdaftar, kita TIDAK membocorkan ketiadaan akun di tahap awal
        // guna mencegah user enumeration vulnerability.
        $user = User::findByPhone($normalizedPhone);
        if ($user) {
            if ($user->status === User::STATUS_SUSPENDED) {
                Response::json([
                    'success' => false,
                    'message' => 'Akun dengan nomor ini sedang ditangguhkan. Silakan hubungi dukungan pelanggan Terima Jasa.'
                ], 403);
                return;
            }
            if ($user->status === User::STATUS_INACTIVE) {
                Response::json([
                    'success' => false,
                    'message' => 'Akun dengan nomor ini berstatus nonaktif. Silakan hubungi Administrator.'
                ], 403);
                return;
            }
        }

        // 5. Buat Kode OTP yang Aman Secara Kriptografis
        $expirySeconds = (int)config('whatsapp.otp_expiry', 300);
        $cooldownSeconds = (int)config('whatsapp.resend_cooldown', 60);

        try {
            $otpData = OtpVerification::generateOtp(
                $normalizedPhone,
                $user ? (int)$user->id : null,
                $request->ip(),
                $request->userAgent(),
                $expirySeconds,
                $cooldownSeconds
            );
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem dalam membuat kode OTP. Silakan coba sesaat lagi.'
            ], 500);
            return;
        }

        // 6. Kirimkan Kode OTP melalui Layanan WhatsApp API
        $sendResult = $this->whatsAppService->sendOtp(
            $normalizedPhone,
            $otpData['otp'],
            (int)ceil($expirySeconds / 60)
        );

        // Jika pengiriman ke gateway gagal, jangan arahkan ke halaman verifikasi
        if (!$sendResult['success']) {
            OtpVerification::invalidatePendingOtps($normalizedPhone);

            Response::json([
                'success'      => false,
                'message'      => $sendResult['message'] ?? 'Gagal mengirim kode verifikasi ke WhatsApp Anda.',
                'raw_reason'   => $sendResult['raw_reason'] ?? null,
                'error_code'   => 'GATEWAY_ERROR',
                'dev_simulated'=> false
            ], 500);
            return;
        }

        // Catat nomor tujuan di session untuk memudahkan alur verifikasi di browser
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        $_SESSION['otp_target_phone'] = $normalizedPhone;

        // 7. Kembalikan Respons JSON Aman (Tanpa membocorkan token provider atau kode OTP)
        Response::json([
            'success'         => true,
            'message'         => 'Kode verifikasi telah dikirimkan ke nomor WhatsApp Anda.',
            'phone'           => $normalizedPhone,
            'masked_phone'    => PhoneHelper::mask($normalizedPhone),
            'expires_in'      => $expirySeconds,
            'resend_cooldown' => $cooldownSeconds,
            'redirect_url'    => url('login/whatsapp/verify?phone=' . urlencode($normalizedPhone)),
            'dev_simulated'   => $sendResult['dev_simulated'] ?? false
        ], 200);
    }

    /**
     * Endpoint API: Kirim Ulang Kode OTP
     * POST /api/auth/whatsapp/resend-otp
     * Request JSON / POST: { "phone": "+6281234567890" }
     */
    public function resendOtp(Request $request): void
    {
        // Alur resend menggunakan validasi yang sama dengan requestOtp
        $this->requestOtp($request);
    }

    /**
     * Endpoint API: Verifikasi Kode OTP
     * POST /api/auth/whatsapp/verify-otp
     * Request JSON / POST: { "phone": "+6281234567890", "otp": "123456" }
     */
    public function verifyOtp(Request $request): void
    {
        $rawPhone = (string)$request->input('phone', '');
        $rawOtp   = (string)$request->input('otp', '');

        if (empty(trim($rawPhone))) {
            Response::json([
                'success' => false,
                'message' => 'Nomor WhatsApp wajib disertakan.'
            ], 422);
            return;
        }

        $normalizedPhone = PhoneHelper::normalize($rawPhone);
        if (!$normalizedPhone) {
            Response::json([
                'success' => false,
                'message' => 'Format nomor WhatsApp tidak valid.'
            ], 422);
            return;
        }

        if (empty(trim($rawOtp))) {
            Response::json([
                'success' => false,
                'message' => 'Kode OTP wajib diisi.'
            ], 422);
            return;
        }

        // 1. Eksekusi Verifikasi OTP melalui Model
        $verificationResult = OtpVerification::verify($normalizedPhone, $rawOtp);

        if (!$verificationResult['success']) {
            Response::json([
                'success'            => false,
                'message'            => $verificationResult['message'],
                'status'             => $verificationResult['status'] ?? 'failed',
                'remaining_attempts' => $verificationResult['remaining_attempts'] ?? 0
            ], 400);
            return;
        }

        // 2. OTP Valid & Belum Kedaluwarsa!
        // Cari pengguna terdaftar di sistem Terima Jasa
        $user = User::findByPhone($normalizedPhone);

        if (!$user) {
            // Nomor WhatsApp belum terdaftar:
            // Sesuai prinsip keamanan: Jangan membuat akun otomatis tanpa kebijakan registrasi yang jelas.
            // Arahkan ke form registrasi dengan nomor WhatsApp yang telah terverifikasi.
            if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
                session_start();
            }
            $_SESSION['verified_phone'] = $normalizedPhone;
            $_SESSION['phone_verified_at'] = time();

            Response::json([
                'success'      => true,
                'message'      => 'Nomor WhatsApp berhasil diverifikasi! Silakan lengkapi pendaftaran akun Anda.',
                'is_new_user'  => true,
                'redirect_url' => url('register?phone=' . urlencode($normalizedPhone) . '&verified=1')
            ], 200);
            return;
        }

        // 3. User Ditemukan: Periksa Status Akun
        if ($user->status !== User::STATUS_ACTIVE) {
            Response::json([
                'success' => false,
                'message' => 'Akun Anda tidak aktif atau sedang ditangguhkan. Silakan hubungi Administrator.'
            ], 403);
            return;
        }

        // 4. Verifikasi Role Berdasarkan Database (Bukan dari Input Frontend!)
        // Penegakan Keamanan: Role didapatkan langsung dari database entitas user
        $role = $user->role;

        // Khusus Teknisi: Periksa status verifikasi profil
        if ($role === User::ROLE_TEKNISI) {
            $techProfile = $user->technicianProfile();
            // Informasi status tetap tercatat di session
            $_SESSION['tech_verification_status'] = $techProfile ? $techProfile->verification_status : 'pending';
        }

        // 5. Buat Sesi Autentikasi yang Aman di Backend
        Auth::login($user, false);

        // Bersihkan data sementara target OTP
        unset($_SESSION['otp_target_phone']);

        $_SESSION['flash_success'] = "Selamat datang kembali, {$user->name}! Berhasil masuk via WhatsApp.";

        // 6. Tentukan URL Dashboard Berdasarkan Role Terverifikasi
        $redirectUrl = match ($role) {
            User::ROLE_ADMIN    => url('admin/dashboard'),
            User::ROLE_TEKNISI  => url('technician/dashboard'),
            default             => url('customer/dashboard')
        };

        Response::json([
            'success'      => true,
            'message'      => 'Verifikasi berhasil. Anda dialihkan ke dashboard...',
            'is_new_user'  => false,
            'role'         => $role,
            'redirect_url' => $redirectUrl
        ], 200);
    }

    /**
     * Endpoint API: Logout Pengguna
     * POST /api/auth/logout
     */
    public function logout(Request $request): void
    {
        Auth::logout();
        Response::json([
            'success'      => true,
            'message'      => 'Anda telah berhasil keluar (logout) dari sistem.',
            'redirect_url' => url('login')
        ], 200);
    }
}
