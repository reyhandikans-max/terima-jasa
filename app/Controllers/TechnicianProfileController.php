<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Models\User;
use App\Models\TechnicianProfile;

/**
 * TechnicianProfileController - Mengelola Pengubahan Data Diri & Status Ketersediaan Teknisi
 */
class TechnicianProfileController extends Controller
{
    /**
     * Tampilkan Form Edit Profil Teknisi & Ketersediaan
     */
    public function edit(Request $request): void
    {
        $userSession = Auth::user();
        $userModel = User::find($userSession['id']);
        $profile = $userModel ? $userModel->technicianProfile() : null;

        $this->view('technician/profile/edit', [
            'pageTitle'   => 'Profil & Status Ketersediaan — Mitra Teknisi',
            'currentUser' => $userSession,
            'userModel'   => $userModel,
            'profile'     => $profile
        ], 'dashboard');

        // Bersihkan flash errors dan old input
        unset($_SESSION['errors'], $_SESSION['old']);
    }

    /**
     * Simpan Perubahan Profil & Ketersediaan Teknisi
     */
    public function update(Request $request): void
    {
        $techId = (int)Auth::id();
        $user = User::find($techId);

        if (!$user) {
            $_SESSION['flash_error'] = 'Akun teknisi tidak ditemukan.';
            $this->redirect('technician/dashboard');
            return;
        }

        $name         = trim((string)$request->input('name', ''));
        $phone        = trim((string)$request->input('phone', ''));
        $expertise    = trim((string)$request->input('expertise', ''));
        $experience   = trim((string)$request->input('experience', ''));
        $address      = trim((string)$request->input('address', ''));
        $location     = trim((string)$request->input('location', ''));
        $availability = trim((string)$request->input('availability', 'available'));

        // Normalisasi availability (available / unavailable)
        if (!in_array($availability, ['available', 'unavailable'])) {
            $availability = 'available';
        }

        // Server-Side Validation
        $errors = [];
        if (empty($name) || mb_strlen($name) < 3) {
            $errors['name'][] = 'Nama lengkap minimal 3 karakter.';
        }
        if (empty($phone) || mb_strlen($phone) < 9) {
            $errors['phone'][] = 'Nomor telepon minimal 9 digit.';
        }
        if (empty($expertise) || mb_strlen($expertise) < 3) {
            $errors['expertise'][] = 'Bidang keahlian wajib diisi.';
        }
        if (empty($experience)) {
            $errors['experience'][] = 'Pengalaman kerja wajib diisi.';
        }
        if (empty($address) || mb_strlen($address) < 5) {
            $errors['address'][] = 'Alamat lengkap minimal 5 karakter.';
        }
        if (empty($location)) {
            $errors['location'][] = 'Wilayah / kota lokasi kerja wajib diisi.';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $request->all();
            $_SESSION['flash_error'] = 'Mohon periksa data formulir profil yang belum lengkap.';
            $this->redirect('technician/profile');
            return;
        }

        // 1. Update data akun User
        $user->name = $name;
        $user->phone = $phone;
        $user->save();

        // 2. Update atau Buat data TechnicianProfile
        $profile = $user->technicianProfile();
        if ($profile) {
            $profile->expertise    = $expertise;
            $profile->experience   = $experience;
            $profile->phone        = $phone;
            $profile->address      = $address;
            $profile->location     = $location;
            $profile->availability = $availability;
            $profile->save();
        } else {
            TechnicianProfile::create([
                'user_id'             => $techId,
                'expertise'           => $expertise,
                'experience'          => $experience,
                'phone'               => $phone,
                'address'             => $address,
                'location'            => $location,
                'availability'        => $availability,
                'verification_status' => TechnicianProfile::VERIFICATION_VERIFIED
            ]);
        }

        // Perbarui data nama pada session aktif
        if (isset($_SESSION['user'])) {
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['phone'] = $phone;
        }

        $_SESSION['flash_success'] = 'Profil teknisi dan status ketersediaan berhasil diperbarui!';
        $this->redirect('technician/profile');
    }

    /**
     * Quick 1-Click Toggle Availability (Available <-> Unavailable)
     */
    public function toggleAvailability(Request $request): void
    {
        $techId = (int)Auth::id();
        $user = User::find($techId);
        $profile = $user ? $user->technicianProfile() : null;

        if ($profile) {
            $newStatus = ($profile->availability === 'available') ? 'unavailable' : 'available';
            $profile->availability = $newStatus;
            $profile->save();

            $statusLabel = ($newStatus === 'available') ? 'Tersedia (Available) — Siap Menerima Order' : 'Tidak Tersedia (Unavailable) — Istirahat';
            $_SESSION['flash_success'] = "Status ketersediaan berhasil diubah menjadi: [{$statusLabel}].";
        } else {
            $_SESSION['flash_error'] = 'Profil teknisi belum ditemukan.';
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? url('technician/dashboard');
        header("Location: {$referer}");
        exit;
    }
}
