<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Models\Order;
use App\Models\User;
use App\Models\Service;
use App\Models\Review;

/**
 * AdminOrderController — Manajemen & Monitoring Seluruh Order Sistem
 */
class AdminOrderController extends Controller
{
    /**
     * List Semua Order dengan Filter: Status, Tanggal, Jasa, Teknisi
     */
    public function index(Request $request): void
    {
        $allOrders    = Order::all();
        $allServices  = Service::all();
        $allUsers     = User::all();
        $technicians  = array_values(array_filter($allUsers, fn($u) => $u->role === User::ROLE_TEKNISI));

        // Filters
        $statusFilter = trim((string)$request->input('status', ''));
        $dateFilter   = trim((string)$request->input('date', ''));
        $serviceId    = $request->input('service_id');
        $techId       = $request->input('technician_id');
        $search       = trim((string)$request->input('search', $request->input('q', '')));

        $filteredOrders = [];
        foreach ($allOrders as $order) {
            // 1. Filter Status
            if (!empty($statusFilter) && $order->status !== $statusFilter) {
                continue;
            }

            // 2. Filter Tanggal (mencocokkan tanggal jadwal atau tanggal dibuat Y-m-d)
            if (!empty($dateFilter)) {
                $schedDate   = !empty($order->schedule) ? date('Y-m-d', strtotime($order->schedule)) : '';
                $createdDate = !empty($order->created_at) ? date('Y-m-d', strtotime($order->created_at)) : '';
                if ($schedDate !== $dateFilter && $createdDate !== $dateFilter) {
                    continue;
                }
            }

            // 3. Filter Jasa
            if (!empty($serviceId) && (int)$order->service_id !== (int)$serviceId) {
                continue;
            }

            // 4. Filter Teknisi
            if (!empty($techId)) {
                if ((string)$techId === 'unassigned') {
                    if (!empty($order->technician_id)) continue;
                } else {
                    if ((int)$order->technician_id !== (int)$techId) continue;
                }
            }

            // 5. Search Bar (ID, lokasi, nama customer)
            if (!empty($search)) {
                $cust = $order->customer();
                $custName = $cust ? $cust->name : '';
                $haystack = strtolower($order->id . ' ' . ($order->location ?? '') . ' ' . $custName . ' ' . ($order->description ?? ''));
                if (!str_contains($haystack, strtolower($search))) {
                    continue;
                }
            }

            $filteredOrders[] = $order;
        }

        // Sort descending by id
        usort($filteredOrders, fn($a, $b) => (int)$b->id <=> (int)$a->id);

        $this->view('admin/orders/index', [
            'pageTitle'      => 'Kelola Pesanan Servis — Panel Admin',
            'currentUser'    => Auth::user(),
            'orders'         => $filteredOrders,
            'services'       => $allServices,
            'technicians'    => $technicians,
            'statusFilter'   => $statusFilter,
            'dateFilter'     => $dateFilter,
            'serviceId'      => $serviceId,
            'techId'         => $techId,
            'search'         => $search,
            'totalCount'     => count($allOrders),
        ], 'dashboard');
    }

    /**
     * Detail Order
     */
    public function show(Request $request, string $id): void
    {
        $order = Order::find((int)$id);

        if (!$order) {
            $_SESSION['flash_error'] = 'Pesanan tidak ditemukan.';
            $this->redirect('admin/orders');
            return;
        }

        $customer   = $order->customer();
        $technician = $order->technician();
        $service    = $order->service();
        $review     = $order->review();

        $this->view('admin/orders/show', [
            'pageTitle'   => 'Detail Order #' . str_pad($order->id, 5, '0', STR_PAD_LEFT) . ' — Panel Admin',
            'currentUser' => Auth::user(),
            'order'       => $order,
            'customer'    => $customer,
            'technician'  => $technician,
            'service'     => $service,
            'review'      => $review,
        ], 'dashboard');
    }
}
