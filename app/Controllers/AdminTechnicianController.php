<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Models\User;
use App\Models\TechnicianProfile;
use App\Models\Order;
use App\Models\Review;
use App\Services\NotificationService;

/**
 * AdminTechnicianController — Manajemen Mitra Teknisi
 */
class AdminTechnicianController extends Controller
{
    /**
     * List, Search & Filter Teknisi
     */
    public function index(Request $request): void
    {
        $allUsers = User::all();
        $technicians = array_values(array_filter($allUsers, fn($u) => $u->role === User::ROLE_TEKNISI));

        $search = trim((string)$request->input('search', $request->input('q', '')));
        $verifFilter = trim((string)$request->input('verification', $request->input('status', '')));

        $techItems = [];
        $pendingCount = 0;

        foreach ($technicians as $techUser) {
            $profile = $techUser->technicianProfile();
            $verifStatus = $profile ? ($profile->verification_status ?? 'pending') : 'pending';

            if ($verifStatus === 'pending') {
                $pendingCount++;
            }

            // Filter status verifikasi
            if (!empty($verifFilter) && $verifStatus !== $verifFilter) {
                continue;
            }

            // Filter search (nama, email, no telp, keahlian, lokasi)
            if (!empty($search)) {
                $expertise = $profile ? ($profile->expertise ?? '') : '';
                $location  = $profile ? ($profile->location ?? '') : '';
                $haystack  = strtolower(($techUser->name ?? '') . ' ' . ($techUser->email ?? '') . ' ' . ($techUser->phone ?? '') . ' ' . $expertise . ' ' . $location);
                if (!str_contains($haystack, strtolower($search))) {
                    continue;
                }
            }

            $techItems[] = [
                'user'    => $techUser,
                'profile' => $profile,
                'status'  => $verifStatus,
            ];
        }

        // Sort: pending verification first, then by id desc
        usort($techItems, function ($a, $b) {
            if ($a['status'] === 'pending' && $b['status'] !== 'pending') return -1;
            if ($b['status'] === 'pending' && $a['status'] !== 'pending') return 1;
            return (int)$b['user']->id <=> (int)$a['user']->id;
        });

        $this->view('admin/technicians/index', [
            'pageTitle'    => 'Kelola Teknisi — Panel Admin Terima Jasa',
            'currentUser'  => Auth::user(),
            'techItems'    => $techItems,
            'search'       => $search,
            'verifFilter'  => $verifFilter,
            'totalCount'   => count($technicians),
            'pendingCount' => $pendingCount,
        ], 'dashboard');
    }

    /**
     * Detail Profil Teknisi, Riwayat Pekerjaan & Rating
     */
    public function show(Request $request, string $id): void
    {
        $techUser = User::find((int)$id);

        if (!$techUser || $techUser->role !== User::ROLE_TEKNISI) {
            $_SESSION['flash_error'] = 'Data teknisi tidak ditemukan.';
            $this->redirect('admin/technicians');
            return;
        }

        $profile = $techUser->technicianProfile();
        if (!$profile) {
            // Lazy create or fallback profile
            $profile = TechnicianProfile::findByUserId((int)$techUser->id);
        }

        // Orders assigned to this technician
        $allOrders = Order::all();
        $orders = array_values(array_filter($allOrders, fn($o) => (int)$o->technician_id === (int)$techUser->id));
        usort($orders, fn($a, $b) => (int)$b->id <=> (int)$a->id);

        $completedJobs = array_filter($orders, fn($o) => $o->status === Order::STATUS_COMPLETED);
        $activeJobs    = array_filter($orders, fn($o) => in_array($o->status, [Order::STATUS_ACCEPTED, Order::STATUS_IN_PROGRESS]));

        // Reviews
        $reviews = $techUser->reviewsReceived();
        $avgRating = 0;
        if (!empty($reviews)) {
            $totalRating = array_reduce($reviews, fn($acc, $r) => $acc + (int)$r->rating, 0);
            $avgRating = round($totalRating / count($reviews), 1);
        }

        $this->view('admin/technicians/show', [
            'pageTitle'      => 'Detail Teknisi: ' . e($techUser->name) . ' — Panel Admin',
            'currentUser'    => Auth::user(),
            'techUser'       => $techUser,
            'profile'        => $profile,
            'orders'         => $orders,
            'totalJobs'      => count($orders),
            'completedJobs'  => count($completedJobs),
            'activeJobs'     => count($activeJobs),
            'reviews'        => $reviews,
            'avgRating'      => $avgRating,
        ], 'dashboard');
    }

    /**
     * Verifikasi Teknisi (Status: verified)
     */
    public function verify(Request $request, string $id): void
    {
        $techUser = User::find((int)$id);

        if (!$techUser || $techUser->role !== User::ROLE_TEKNISI) {
            $_SESSION['flash_error'] = 'Teknisi tidak ditemukan.';
            $this->redirect('admin/technicians');
            return;
        }

        $profile = $techUser->technicianProfile() ?: TechnicianProfile::findByUserId((int)$techUser->id);

        if (!$profile) {
            $profile = new TechnicianProfile([
                'user_id'             => $techUser->id,
                'expertise'           => 'Spesialis Servis',
                'experience'          => '1 Tahun',
                'phone'               => $techUser->phone ?? '',
                'location'            => 'Indonesia',
                'availability'        => 'available',
                'verification_status' => 'verified',
            ]);
        } else {
            $profile->verification_status = 'verified';
        }

        $profile->save();

        // Notifikasi ke Teknisi: Akun Berhasil Diverifikasi oleh Admin
        NotificationService::notifyTechnicianVerified($techUser);

        $_SESSION['flash_success'] = "Mitra teknisi '{$techUser->name}' berhasil diverifikasi dan kini berstatus VERIFIED.";
        $referer = $_SERVER['HTTP_REFERER'] ?? url("admin/technicians/{$id}");
        header("Location: {$referer}");
        exit;
    }

    /**
     * Tolak Verifikasi Teknisi (Status: rejected)
     */
    public function reject(Request $request, string $id): void
    {
        $techUser = User::find((int)$id);

        if (!$techUser || $techUser->role !== User::ROLE_TEKNISI) {
            $_SESSION['flash_error'] = 'Teknisi tidak ditemukan.';
            $this->redirect('admin/technicians');
            return;
        }

        $profile = $techUser->technicianProfile() ?: TechnicianProfile::findByUserId((int)$techUser->id);

        if ($profile) {
            $profile->verification_status = 'rejected';
            $profile->save();
        }

        $_SESSION['flash_error'] = "Verifikasi mitra teknisi '{$techUser->name}' telah DITOLAK (REJECTED).";
        $referer = $_SERVER['HTTP_REFERER'] ?? url("admin/technicians/{$id}");
        header("Location: {$referer}");
        exit;
    }

    /**
     * Toggle Aktif / Nonaktif Akun Teknisi
     */
    public function toggleStatus(Request $request, string $id): void
    {
        $techUser = User::find((int)$id);

        if (!$techUser || $techUser->role !== User::ROLE_TEKNISI) {
            $_SESSION['flash_error'] = 'Teknisi tidak ditemukan.';
            $this->redirect('admin/technicians');
            return;
        }

        $newStatus = ($techUser->status === 'active') ? 'inactive' : 'active';
        $techUser->status = $newStatus;
        $techUser->save();

        $statusLabel = ($newStatus === 'active') ? 'diaktifkan' : 'dinonaktifkan';
        $_SESSION['flash_success'] = "Akun teknisi '{$techUser->name}' berhasil {$statusLabel}.";

        $referer = $_SERVER['HTTP_REFERER'] ?? url("admin/technicians/{$id}");
        header("Location: {$referer}");
        exit;
    }
}
