<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Models\User;
use App\Models\Order;
use App\Models\Category;
use App\Models\TechnicianProfile;
use App\Models\Review;
use App\Models\Service;

/**
 * AdminDashboardController - Dashboard Khusus Administrator Sistem
 */
class AdminDashboardController extends Controller
{
    public function index(Request $request): void
    {
        $userSession = Auth::user();
        $userModel   = !empty($userSession['id']) ? User::find((int)$userSession['id']) : null;

        // Fetch all system data
        $allUsers      = User::all();
        $allOrders     = Order::all();
        $allTechs      = TechnicianProfile::all();
        $allCategories = Category::all();
        $allServices   = Service::all();
        $allReviews    = Review::all();

        // Customer & Technician collections
        $customers   = array_values(array_filter($allUsers, fn($u) => $u->role === 'customer'));
        $technicians = array_values(array_filter($allUsers, fn($u) => $u->role === 'teknisi'));

        // Count pending verifications by checking profiles
        $pendingVerifs = 0;
        foreach ($allTechs as $t) {
            if ($t->verification_status === 'pending') {
                $pendingVerifs++;
            }
        }

        // Exact required stats
        $stats = [
            'total_customers'      => count($customers),
            'total_technicians'    => count($technicians),
            'pending_verifs'       => $pendingVerifs,
            'total_services'       => count($allServices),
            'total_orders'         => count($allOrders),
            'pending_orders'       => count(array_filter($allOrders, fn($o) => $o->status === 'pending')),
            'active_orders'        => count(array_filter($allOrders, fn($o) => in_array($o->status, ['accepted', 'in_progress']))),
            'completed_orders'     => count(array_filter($allOrders, fn($o) => $o->status === 'completed')),
            'total_categories'     => count($allCategories),
            'total_reviews'        => count($allReviews),
        ];

        // Sort desc for "latest" tables
        usort($allOrders, fn($a, $b) => (int)$b->id <=> (int)$a->id);
        usort($technicians, fn($a, $b) => (int)$b->id <=> (int)$a->id);
        usort($customers, fn($a, $b) => (int)$b->id <=> (int)$a->id);

        $this->view('dashboard/admin', [
            'pageTitle'         => 'Dashboard Administrator — Terima Jasa',
            'currentUser'       => $userSession,
            'userModel'         => $userModel,
            'stats'             => $stats,
            'pendingVerifs'     => $pendingVerifs,
            'latestOrders'      => array_slice($allOrders, 0, 6),
            'latestTechnicians' => array_slice($technicians, 0, 6),
            'latestCustomers'   => array_slice($customers, 0, 6),
        ], 'dashboard');
    }
}
