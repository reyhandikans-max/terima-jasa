<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Models\User;
use App\Helpers\PhoneHelper;

/**
 * AdminProfileController — Mengelola Profil dan Pembaruan Identitas Administrator Sistem
 */
class AdminProfileController extends Controller
{
    /**
     * Tampilkan formulir profil Administrator
     * GET /admin/profile
     */
    public function edit(Request $request): void
    {
        $sessionUser = Auth::user();
        $adminId = (int)($sessionUser['id'] ?? 0);
        $userModel = User::find($adminId);

        if (!$userModel || $userModel->role !== User::ROLE_ADMIN) {
            $_SESSION['flash_error'] = 'Akun administrator tidak ditemukan.';
            $this->redirect('admin/dashboard');
            return;
        }

        $this->view('admin/profile/edit', [
            'pageTitle'   => 'Profil Administrator — Terima Jasa',
            'currentUser' => $sessionUser,
            'admin'       => $userModel,
        ], 'dashboard');

        // Bersihkan flash errors dan old inputs setelah ditampilkan
        unset($_SESSION['errors'], $_SESSION['old']);
    }

    /**
     * Simpan pembaruan identitas Administrator
     * POST /admin/profile
     */
    public function update(Request $request): void
    {
        $sessionUser = Auth::user();
        $adminId = (int)($sessionUser['id'] ?? 0);
        $admin = User::find($adminId);

        if (!$admin || $admin->role !== User::ROLE_ADMIN) {
            $_SESSION['flash_error'] = 'Akses ditolak atau akun administrator tidak valid.';
            $this->redirect('admin/dashboard');
            return;
        }

        $name                    = trim((string)$request->input('name', ''));
        $email                   = strtolower(trim((string)$request->input('email', '')));
        $phone                   = trim((string)$request->input('phone', ''));
        $currentPassword         = (string)$request->input('current_password', '');
        $newPassword             = (string)$request->input('new_password', '');
        $newPasswordConfirmation = (string)$request->input('new_password_confirmation', '');

        $errors = [];

        // 1. Validasi Nama
        if (empty($name) || mb_strlen($name) < 3) {
            $errors['name'][] = 'Nama administrator minimal 3 karakter.';
        } elseif (mb_strlen($name) > 150) {
            $errors['name'][] = 'Nama administrator maksimal 150 karakter.';
        }

        // 2. Validasi Email
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'Format alamat email tidak valid.';
        } else {
            // Cek keunikan email (tidak boleh sama dengan user lain)
            $existing = User::firstWhere('email', $email);
            if ($existing && (int)$existing->id !== $adminId) {
                $errors['email'][] = 'Alamat email sudah digunakan oleh pengguna lain.';
            }
        }

        // 3. Validasi Nomor Telepon / WhatsApp
        $normalizedPhone = null;
        if (!empty($phone)) {
            $normalizedPhone = PhoneHelper::normalize($phone);
            if (!$normalizedPhone && strlen(preg_replace('/[^\d]/', '', $phone)) < 9) {
                $errors['phone'][] = 'Nomor telepon minimal 9 digit angka.';
            }
        }

        // 4. Validasi Ganti Password (Opsional)
        $isChangingPassword = !empty($newPassword) || !empty($currentPassword) || !empty($newPasswordConfirmation);
        if ($isChangingPassword) {
            if (empty($currentPassword)) {
                $errors['current_password'][] = 'Masukkan kata sandi saat ini untuk konfirmasi perubahan.';
            } elseif (!password_verify($currentPassword, $admin->password)) {
                $errors['current_password'][] = 'Kata sandi saat ini tidak cocok.';
            }

            if (empty($newPassword) || strlen($newPassword) < 6) {
                $errors['new_password'][] = 'Kata sandi baru minimal 6 karakter.';
            }

            if ($newPassword !== $newPasswordConfirmation) {
                $errors['new_password_confirmation'][] = 'Konfirmasi kata sandi baru tidak cocok.';
            }
        }

        // Jika terdapat error validasi
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = [
                'name'  => $name,
                'email' => $email,
                'phone' => $phone
            ];
            $_SESSION['flash_error'] = 'Gagal memperbarui profil. Mohon periksa kembali kolom formulir.';
            $this->redirect('admin/profile');
            return;
        }

        // Simpan perubahan ke Database
        $admin->name  = $name;
        $admin->email = $email;
        $admin->phone = $normalizedPhone ?? $phone;

        if ($isChangingPassword && !empty($newPassword)) {
            $admin->password = password_hash($newPassword, PASSWORD_BCRYPT);
        }

        $admin->updated_at = date('Y-m-d H:i:s');
        $admin->save();

        // Segarkan data pada session aktif agar langsung terlihat di navbar & dropdown
        if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
            $_SESSION['user']['name']  = $admin->name;
            $_SESSION['user']['email'] = $admin->email;
            $_SESSION['user']['phone'] = $admin->phone;
        }

        $_SESSION['flash_success'] = 'Profil identitas Administrator berhasil diperbarui!';
        $this->redirect('admin/profile');
    }
}

