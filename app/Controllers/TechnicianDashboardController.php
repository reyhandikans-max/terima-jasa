<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Models\User;
use App\Models\Order;
use App\Models\TechnicianProfile;

/**
 * TechnicianDashboardController - Dashboard Khusus Mitra Teknisi
 */
class TechnicianDashboardController extends Controller
{
    public function index(Request $request): void
    {
        $userSession = Auth::user();
        $techId = (int)$userSession['id'];
        $userModel = User::find($techId);
        $profile = $userModel ? $userModel->technicianProfile() : null;

        // Data spesifik Teknisi login
        $orders = $userModel ? $userModel->technicianOrders() : [];
        $reviews = $userModel ? $userModel->reviewsReceived() : [];

        // Hitung rata-rata rating
        $avgRating = 5.0;
        if (!empty($reviews)) {
            $totalStars = array_sum(array_map(fn($r) => (int)$r->rating, $reviews));
            $avgRating = round($totalStars / count($reviews), 1);
        }

        $activeJobs = array_filter($orders, fn($o) => in_array($o->status, [Order::STATUS_ACCEPTED, Order::STATUS_IN_PROGRESS]));
        $completedJobs = array_filter($orders, fn($o) => $o->status === Order::STATUS_COMPLETED);

        // Permintaan jasa baru (pending yang relevan)
        $allOrders = Order::all();
        $pendingRequests = array_filter($allOrders, fn($o) => $o->status === Order::STATUS_PENDING && (empty($o->technician_id) || (int)$o->technician_id === $techId));

        // Sort descending by id
        usort($orders, fn($a, $b) => (int)$b->id <=> (int)$a->id);
        usort($activeJobs, fn($a, $b) => (int)$b->id <=> (int)$a->id);
        usort($pendingRequests, fn($a, $b) => (int)$b->id <=> (int)$a->id);

        $stats = [
            'total_jobs'       => count($orders),
            'active_jobs'      => count($activeJobs),
            'completed_jobs'   => count($completedJobs),
            'pending_requests' => count($pendingRequests),
            'rating'           => $avgRating,
            'reviews_count'    => count($reviews),
            'availability'     => $profile ? $profile->availability : 'available'
        ];

        $this->view('dashboard/technician', [
            'pageTitle'        => 'Dashboard Mitra Teknisi — Terima Jasa',
            'currentUser'      => $userSession,
            'userModel'        => $userModel,
            'profile'          => $profile,
            'stats'            => $stats,
            'orders'           => $orders,
            'activeJobs'       => $activeJobs,
            'pendingRequests'  => $pendingRequests,
            'reviews'          => $reviews
        ], 'dashboard');
    }
}
