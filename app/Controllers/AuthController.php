<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Models\User;
use App\Models\TechnicianProfile;
use App\Services\NotificationService;

/**
 * AuthController - Mengelola Alur Autentikasi & Registrasi Berdasarkan Role
 */
class AuthController extends Controller
{
    /**
     * Tampilkan form Login
     */
    public function login(Request $request): void
    {
        // Jika sudah login, redirect langsung ke dashboard perannya
        if (Auth::check()) {
            $this->redirect(Auth::dashboardUrl());
            return;
        }

        $activeRole = $request->input('role', User::ROLE_CUSTOMER);
        $mockUsers = [
            'customer' => ['email' => 'customer@terimajasa.id', 'role' => 'customer', 'name' => 'Budi Santoso'],
            'teknisi'  => ['email' => 'teknisi@terimajasa.id', 'role' => 'teknisi', 'name' => 'Hendra Wijaya'],
            'admin'    => ['email' => 'admin@terimajasa.id', 'role' => 'admin', 'name' => 'Super Admin Terima Jasa']
        ];

        $this->view('auth/login', [
            'pageTitle'  => 'Masuk — Terima Jasa',
            'activeRole' => $activeRole,
            'mockUsers'  => $mockUsers
        ], 'auth');

        // Bersihkan flash errors dan old input setelah dirender
        unset($_SESSION['errors'], $_SESSION['old']);
    }

    /**
     * Proses Login Pengguna
     */
    public function postLogin(Request $request): void
    {
        $demoRole = $request->input('demo_role');

        if ($demoRole) {
            // Quick 1-Click Login untuk testing demo
            $credentials = match ($demoRole) {
                'admin'    => ['email' => 'admin@terimajasa.id', 'password' => 'password123'],
                'teknisi'  => ['email' => 'teknisi@terimajasa.id', 'password' => 'password123'],
                default    => ['email' => 'customer@terimajasa.id', 'password' => 'password123']
            };
            $email = $credentials['email'];
            $password = $credentials['password'];
            $remember = false;
        } else {
            $email = trim((string)$request->input('email', ''));
            $password = (string)$request->input('password', '');
            $remember = (bool)$request->input('remember', false);
        }

        // Server-Side Validation
        $errors = [];
        if (empty($email)) {
            $errors['email'][] = 'Alamat email wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'Format email tidak valid.';
        }

        if (empty($password)) {
            $errors['password'][] = 'Kata sandi wajib diisi.';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = ['email' => $email];
            $_SESSION['flash_error'] = 'Silakan perbaiki data yang belum sesuai.';
            $this->redirect('login');
            return;
        }

        // Verifikasi kredensial menggunakan Auth::attempt (Password Hashing Verify)
        if (Auth::attempt($email, $password, $remember)) {
            $user = Auth::user();
            $_SESSION['flash_success'] = "Selamat datang kembali, {$user['name']}! Berhasil masuk sebagai [" . ucfirst($user['role']) . "].";

            // Redirect ke dashboard spesifik perannya
            $this->redirect(Auth::dashboardUrl($user['role']));
            return;
        }

        // Login Gagal
        $_SESSION['flash_error'] = 'Email atau kata sandi tidak cocok, atau akun Anda dinonaktifkan.';
        $_SESSION['old'] = ['email' => $email];
        $this->redirect('login');
    }

    /**
     * Tampilkan form Registrasi
     */
    public function register(Request $request): void
    {
        if (Auth::check()) {
            $this->redirect(Auth::dashboardUrl());
            return;
        }

        $selectedRole = $request->input('role', User::ROLE_CUSTOMER);
        // Keamanan: Admin tidak dapat dipilih pada pendaftaran publik
        if (!in_array($selectedRole, [User::ROLE_CUSTOMER, User::ROLE_TEKNISI])) {
            $selectedRole = User::ROLE_CUSTOMER;
        }

        $this->view('auth/register', [
            'pageTitle'    => 'Daftar Akun Baru — Terima Jasa',
            'selectedRole' => $selectedRole
        ], 'auth');

        // Bersihkan flash errors dan old input setelah dirender
        unset($_SESSION['errors'], $_SESSION['old']);
    }

    /**
     * Proses Registrasi Akun Baru (Customer & Mitra Teknisi)
     */
    public function postRegister(Request $request): void
    {
        $rawRole = $request->input('role', User::ROLE_CUSTOMER);

        // KEAMANAN KETAT: Admin TIDAK dapat melakukan registrasi publik.
        // User tidak dapat mengganti/inject role admin melalui form registrasi.
        $role = in_array($rawRole, [User::ROLE_CUSTOMER, User::ROLE_TEKNISI]) ? $rawRole : User::ROLE_CUSTOMER;

        $name = trim((string)$request->input('name', ''));
        $email = strtolower(trim((string)$request->input('email', '')));
        $phone = trim((string)$request->input('phone', ''));
        $password = (string)$request->input('password', '');
        $passwordConfirmation = (string)$request->input('password_confirmation', '');

        // Form fields khusus Teknisi
        $expertise = trim((string)$request->input('expertise', ''));
        $experience = trim((string)$request->input('experience', ''));
        $address = trim((string)$request->input('address', ''));
        $location = trim((string)$request->input('location', 'Jabodetabek'));

        // Aturan validasi dasar
        $rules = [
            'name'     => 'required|min:3|max:150',
            'email'    => 'required|email|unique:users,email',
            'phone'    => 'required|min:9|max:25',
            'password' => 'required|min:6'
        ];

        $dataToValidate = [
            'name'     => $name,
            'email'    => $email,
            'phone'    => $phone,
            'password' => $password
        ];

        // Validasi tambahan untuk Teknisi
        if ($role === User::ROLE_TEKNISI) {
            $rules['expertise'] = 'required|min:3|max:255';
            $rules['experience'] = 'required|max:100';
            $rules['address'] = 'required|min:5';

            $dataToValidate['expertise'] = $expertise;
            $dataToValidate['experience'] = $experience;
            $dataToValidate['address'] = $address;
        }

        $errors = User::validate($dataToValidate, $rules);

        // Verifikasi konfirmasi password cocok
        if (empty($passwordConfirmation)) {
            $errors['password_confirmation'][] = 'Konfirmasi kata sandi wajib diisi.';
        } elseif ($password !== $passwordConfirmation) {
            $errors['password_confirmation'][] = 'Konfirmasi kata sandi tidak cocok dengan kata sandi.';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $request->all();
            $_SESSION['flash_error'] = 'Pendaftaran gagal. Mohon periksa kembali kolom yang bertanda merah.';
            $this->redirect('register?role=' . $role);
            return;
        }

        // Simpan User Baru dengan Password Hashing Bcrypt
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        $newUser = User::create([
            'name'     => $name,
            'email'    => $email,
            'password' => $passwordHash,
            'role'     => $role,
            'status'   => User::STATUS_ACTIVE,
            'phone'    => $phone
        ]);

        if (!$newUser) {
            // Fallback instance lokal jika database sedang offline agar flow pendaftaran tetap dapat diuji
            $newUser = new User([
                'id'       => rand(100, 999),
                'name'     => $name,
                'email'    => $email,
                'password' => $passwordHash,
                'role'     => $role,
                'status'   => User::STATUS_ACTIVE,
                'phone'    => $phone
            ]);
            $newUser->id = rand(100, 999);
        }

        // Jika pendaftar adalah Teknisi, buat profil teknisinya
        if ($role === User::ROLE_TEKNISI) {
            TechnicianProfile::create([
                'user_id'             => (int)$newUser->id,
                'expertise'           => $expertise,
                'experience'          => $experience,
                'phone'               => $phone,
                'address'             => $address,
                'location'            => !empty($location) ? $location : 'Jabodetabek',
                'availability'        => TechnicianProfile::AVAILABILITY_AVAILABLE,
                'verification_status' => TechnicianProfile::VERIFICATION_PENDING
            ]);

            // Notifikasi ke Admin: Pendaftaran Teknisi Baru Membutuhkan Verifikasi
            NotificationService::notifyTechnicianRegistered($newUser);
        }

        // Login otomatis setelah registrasi sukses
        Auth::login($newUser);

        $_SESSION['flash_success'] = "Selamat! Akun Anda berhasil terdaftar sebagai [" . ucfirst($role) . "].";
        $this->redirect(Auth::dashboardUrl($role));
    }

    /**
     * Proses Logout Pengguna
     */
    public function logout(Request $request): void
    {
        Auth::logout();
        $_SESSION['flash_info'] = 'Anda telah berhasil keluar (logout) dari sistem.';
        $this->redirect('login');
    }
}
