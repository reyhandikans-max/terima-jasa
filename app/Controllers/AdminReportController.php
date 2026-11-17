<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Models\Order;
use App\Models\User;
use App\Models\Service;
use App\Models\Category;
use App\Models\TechnicianProfile;

/**
 * AdminReportController — Laporan & Analitik Platform Terima Jasa
 */
class AdminReportController extends Controller
{
    /**
     * Tampilkan Laporan Sederhana & Statistik Lengkap
     */
    public function index(Request $request): void
    {
        $allOrders    = Order::all();
        $allServices  = Service::all();
        $allUsers     = User::all();
        $technicians  = array_values(array_filter($allUsers, fn($u) => $u->role === User::ROLE_TEKNISI));

        $totalOrders = count($allOrders);

        // 1. Order berdasarkan status
        $statusCounts = [
            'pending'     => 0,
            'accepted'    => 0,
            'in_progress' => 0,
            'completed'   => 0,
            'cancelled'   => 0,
        ];

        $totalRevenue = 0.0;

        foreach ($allOrders as $o) {
            $st = $o->status ?? 'pending';
            if (isset($statusCounts[$st])) {
                $statusCounts[$st]++;
            } else {
                $statusCounts[$st] = 1;
            }

            if ($st === 'completed') {
                $srv = $o->service();
                if ($srv) {
                    $totalRevenue += (float)$srv->estimated_price;
                }
            }
        }

        // 2. Teknisi dengan order terbanyak
        $techOrderMap = [];
        foreach ($technicians as $t) {
            $techOrders = array_filter($allOrders, fn($o) => (int)$o->technician_id === (int)$t->id);
            $completed  = array_filter($techOrders, fn($o) => $o->status === 'completed');

            $profile = $t->technicianProfile();
            $reviews = $t->reviewsReceived();
            $avgRating = 0;
            if (!empty($reviews)) {
                $avgRating = round(array_reduce($reviews, fn($a, $r) => $a + (int)$r->rating, 0) / count($reviews), 1);
            }

            $techOrderMap[] = [
                'user'            => $t,
                'profile'         => $profile,
                'total_orders'    => count($techOrders),
                'completed_count' => count($completed),
                'avg_rating'      => $avgRating,
            ];
        }

        // Sort desc by total orders
        usort($techOrderMap, fn($a, $b) => $b['total_orders'] <=> $a['total_orders']);

        // 3. Jasa paling banyak dipesan
        $serviceOrderMap = [];
        foreach ($allServices as $s) {
            $sOrders = array_filter($allOrders, fn($o) => (int)$o->service_id === (int)$s->id);
            $count   = count($sOrders);
            $serviceOrderMap[] = [
                'service'      => $s,
                'category'     => $s->category(),
                'order_count'  => $count,
                'gross_value'  => $count * (float)$s->estimated_price,
            ];
        }

        // Sort desc by order count
        usort($serviceOrderMap, fn($a, $b) => $b['order_count'] <=> $a['order_count']);

        // Chart Data format JSON
        $statusChartData = [
            'labels' => ['Pending', 'Accepted', 'In Progress', 'Completed', 'Cancelled'],
            'data'   => [
                $statusCounts['pending'],
                $statusCounts['accepted'],
                $statusCounts['in_progress'],
                $statusCounts['completed'],
                $statusCounts['cancelled'],
            ],
            'colors' => ['#f59e0b', '#06b6d4', '#3b82f6', '#10b981', '#ef4444'],
        ];

        // Top 5 Technicians Chart
        $top5Techs = array_slice($techOrderMap, 0, 5);
        $techChartData = [
            'labels' => array_map(fn($item) => $item['user']->name, $top5Techs),
            'data'   => array_map(fn($item) => $item['total_orders'], $top5Techs),
        ];

        // Top 5 Services Chart
        $top5Services = array_slice($serviceOrderMap, 0, 5);
        $serviceChartData = [
            'labels' => array_map(fn($item) => $item['service']->name, $top5Services),
            'data'   => array_map(fn($item) => $item['order_count'], $top5Services),
        ];

        $this->view('admin/reports/index', [
            'pageTitle'        => 'Laporan & Statistik Sistem — Panel Admin',
            'currentUser'      => Auth::user(),
            'totalOrders'      => $totalOrders,
            'totalRevenue'     => $totalRevenue,
            'statusCounts'     => $statusCounts,
            'techOrderMap'     => $techOrderMap,
            'serviceOrderMap'  => $serviceOrderMap,
            'statusChartData'  => $statusChartData,
            'techChartData'    => $techChartData,
            'serviceChartData' => $serviceChartData,
        ], 'dashboard');
    }
}
