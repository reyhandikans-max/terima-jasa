<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use App\Models\Review;
use App\Models\TechnicianProfile;
use App\Services\NotificationService;

/**
 * TechnicianJobController - Mengelola Alur Permintaan Jasa, Pekerjaan Aktif, Riwayat & Ulasan Teknisi
 */
class TechnicianJobController extends Controller
{
    /**
     * Tampilkan Permintaan Jasa Masuk (Pending & Relevan)
     */
    public function requests(Request $request): void
    {
        $techId = (int)Auth::id();
        $allOrders = Order::all();

        // Ambil order berstatus 'pending' yang relevan (ditugaskan ke teknisi ini atau belum diambil teknisi mana pun)
        $pendingRequests = array_filter($allOrders, function ($order) use ($techId) {
            if ($order->status !== Order::STATUS_PENDING) {
                return false;
            }
            // Jika teknisi spesifik belum ditentukan ATAU order ditugaskan ke teknisi ini
            return empty($order->technician_id) || (int)$order->technician_id === $techId;
        });

        // Sort descending by id
        usort($pendingRequests, fn($a, $b) => (int)$b->id <=> (int)$a->id);

        $userSession = Auth::user();
        $userModel = User::find($techId);
        $profile = $userModel ? $userModel->technicianProfile() : null;

        $this->view('technician/jobs/requests', [
            'pageTitle'       => 'Permintaan Jasa Masuk — Mitra Teknisi',
            'currentUser'     => $userSession,
            'pendingRequests' => $pendingRequests,
            'profile'         => $profile
        ], 'dashboard');
    }

    /**
     * Terima Permintaan Jasa (Status: pending -> accepted)
     */
    public function accept(Request $request, string $id): void
    {
        $order = Order::find((int)$id);

        if (!$order) {
            $_SESSION['flash_error'] = 'Pesanan tidak ditemukan.';
            $this->redirect('technician/requests');
            return;
        }

        if ($order->status !== Order::STATUS_PENDING) {
            $_SESSION['flash_error'] = 'Pesanan tidak dapat diterima karena status sudah bukan Pending.';
            $this->redirect('technician/requests');
            return;
        }

        $techId = (int)Auth::id();

        // Update status menjadi accepted dan tetapkan technician_id ke teknisi login
        $order->status = Order::STATUS_ACCEPTED;
        $order->technician_id = $techId;
        $order->save();

        // Trigger Real-time Event Notification: Order Accepted
        NotificationService::notifyOrderAccepted($order);

        $_SESSION['flash_success'] = "Pesanan #TJ-" . str_pad($order->id, 5, '0', STR_PAD_LEFT) . " berhasil Anda terima! Silakan mulai pengerjaan pada menu Pekerjaan Aktif.";
        $this->redirect('technician/active-jobs');
    }

    /**
     * Tolak Permintaan Jasa
     */
    public function reject(Request $request, string $id): void
    {
        $order = Order::find((int)$id);

        if (!$order) {
            $_SESSION['flash_error'] = 'Pesanan tidak ditemukan.';
            $this->redirect('technician/requests');
            return;
        }

        $techId = (int)Auth::id();

        // Jika order ditugaskan ke teknisi ini, lepas penugasan agar dapat diambil teknisi lain
        if ((int)$order->technician_id === $techId) {
            $order->technician_id = null;
            $order->save();
        }

        // Trigger Real-time Event Notification: Order Rejected
        NotificationService::notifyOrderRejected($order, $techId);

        $_SESSION['flash_info'] = "Permintaan pesanan #TJ-" . str_pad($order->id, 5, '0', STR_PAD_LEFT) . " telah ditolak.";
        $this->redirect('technician/requests');
    }

    /**
     * Tampilkan Pekerjaan Aktif (Status: accepted & in_progress)
     */
    public function activeJobs(Request $request): void
    {
        $techId = (int)Auth::id();
        $allOrders = Order::all();

        // KEAMANAN KETAT: Hanya ambil order milik teknisi yang sedang login
        $activeJobs = array_filter($allOrders, function ($order) use ($techId) {
            return (int)$order->technician_id === $techId && 
                   in_array($order->status, [Order::STATUS_ACCEPTED, Order::STATUS_IN_PROGRESS]);
        });

        usort($activeJobs, fn($a, $b) => (int)$b->id <=> (int)$a->id);

        $this->view('technician/jobs/active', [
            'pageTitle'   => 'Pekerjaan Aktif — Mitra Teknisi',
            'currentUser' => Auth::user(),
            'activeJobs'  => $activeJobs
        ], 'dashboard');
    }

    /**
     * Mulai Pengerjaan (Status: accepted -> in_progress)
     */
    public function start(Request $request, string $id): void
    {
        $order = Order::find((int)$id);

        if (!$order) {
            $_SESSION['flash_error'] = 'Pesanan tidak ditemukan.';
            $this->redirect('technician/active-jobs');
            return;
        }

        // KEAMANAN KETAT: Cegah teknisi memodifikasi order milik teknisi lain
        $techId = (int)Auth::id();
        if ((int)$order->technician_id !== $techId) {
            $_SESSION['flash_error'] = 'Akses Ditolak: Anda tidak memiliki izin untuk mengubah pekerjaan milik teknisi lain!';
            $this->redirect('technician/active-jobs');
            return;
        }

        if ($order->status !== Order::STATUS_ACCEPTED) {
            $_SESSION['flash_error'] = "Status order saat ini adalah [{$order->status}]. Pengerjaan hanya dapat dimulai dari status Diterima (Accepted).";
            $this->redirect('technician/active-jobs');
            return;
        }

        $order->status = Order::STATUS_IN_PROGRESS;
        $order->save();

        // Trigger Real-time Event Notification: Order In Progress
        NotificationService::notifyOrderInProgress($order);

        $_SESSION['flash_success'] = "Pengerjaan servis #TJ-" . str_pad($order->id, 5, '0', STR_PAD_LEFT) . " dimulai! Status pesanan kini [Sedang Dikerjakan].";
        $this->redirect('technician/active-jobs');
    }

    /**
     * Selesaikan Pekerjaan (Status: in_progress -> completed)
     */
    public function complete(Request $request, string $id): void
    {
        $order = Order::find((int)$id);

        if (!$order) {
            $_SESSION['flash_error'] = 'Pesanan tidak ditemukan.';
            $this->redirect('technician/active-jobs');
            return;
        }

        // KEAMANAN KETAT: Cegah teknisi memodifikasi order milik teknisi lain
        $techId = (int)Auth::id();
        if ((int)$order->technician_id !== $techId) {
            $_SESSION['flash_error'] = 'Akses Ditolak: Anda tidak memiliki izin untuk menyelesaikan pekerjaan milik teknisi lain!';
            $this->redirect('technician/active-jobs');
            return;
        }

        if ($order->status !== Order::STATUS_IN_PROGRESS) {
            $_SESSION['flash_error'] = "Pekerjaan hanya dapat diselesaikan dari status Sedang Dikerjakan (In Progress).";
            $this->redirect('technician/active-jobs');
            return;
        }

        $order->status = Order::STATUS_COMPLETED;
        $order->save();

        // Trigger Real-time Event Notification: Order Completed
        NotificationService::notifyOrderCompleted($order);

        $_SESSION['flash_success'] = "Selamat! Pekerjaan servis #TJ-" . str_pad($order->id, 5, '0', STR_PAD_LEFT) . " berhasil diselesaikan. Customer kini dapat memberikan ulasan.";
        $this->redirect('technician/history');
    }

    /**
     * Tampilkan Riwayat Pekerjaan Selesai
     */
    public function history(Request $request): void
    {
        $techId = (int)Auth::id();
        $allOrders = Order::all();

        // KEAMANAN KETAT: Hanya order completed milik teknisi yang login
        $completedJobs = array_filter($allOrders, function ($order) use ($techId) {
            return (int)$order->technician_id === $techId && $order->status === Order::STATUS_COMPLETED;
        });

        usort($completedJobs, fn($a, $b) => (int)$b->id <=> (int)$a->id);

        $this->view('technician/jobs/history', [
            'pageTitle'     => 'Riwayat Pekerjaan Selesai — Mitra Teknisi',
            'currentUser'   => Auth::user(),
            'completedJobs' => $completedJobs
        ], 'dashboard');
    }

    /**
     * Tampilkan Halaman Rating & Ulasan dari Customer
     */
    public function reviews(Request $request): void
    {
        $techId = (int)Auth::id();
        $user = User::find($techId);
        $reviews = $user ? $user->reviewsReceived() : [];

        // Hitung rata-rata dan breakdown bintang
        $totalReviews = count($reviews);
        $avgRating = 5.0;
        $starCounts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];

        if ($totalReviews > 0) {
            $totalPoints = 0;
            foreach ($reviews as $rev) {
                $r = (int)$rev->rating;
                $totalPoints += $r;
                if (isset($starCounts[$r])) {
                    $starCounts[$r]++;
                }
            }
            $avgRating = round($totalPoints / $totalReviews, 1);
        }

        $this->view('technician/jobs/reviews', [
            'pageTitle'    => 'Ulasan & Reputasi Teknisi — Terima Jasa',
            'currentUser'  => Auth::user(),
            'reviews'      => $reviews,
            'totalReviews' => $totalReviews,
            'avgRating'    => $avgRating,
            'starCounts'   => $starCounts
        ], 'dashboard');
    }
}
