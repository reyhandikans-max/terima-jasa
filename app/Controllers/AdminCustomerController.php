<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Models\User;
use App\Models\Order;

/**
 * AdminCustomerController — Manajemen Data Customer
 */
class AdminCustomerController extends Controller
{
    /**
     * List & Search Customer
     */
    public function index(Request $request): void
    {
        $allUsers = User::all();
        $customers = array_values(array_filter($allUsers, fn($u) => $u->role === User::ROLE_CUSTOMER));

        $search = trim((string)$request->input('search', $request->input('q', '')));
        $statusFilter = trim((string)$request->input('status', ''));

        $filtered = [];
        foreach ($customers as $c) {
            // Filter Status
            if (!empty($statusFilter) && ($c->status ?? 'active') !== $statusFilter) {
                continue;
            }

            // Filter Search (nama, email, no telp)
            if (!empty($search)) {
                $haystack = strtolower(($c->name ?? '') . ' ' . ($c->email ?? '') . ' ' . ($c->phone ?? ''));
                if (!str_contains($haystack, strtolower($search))) {
                    continue;
                }
            }

            $filtered[] = $c;
        }

        // Sort desc by id
        usort($filtered, fn($a, $b) => (int)$b->id <=> (int)$a->id);

        $this->view('admin/customers/index', [
            'pageTitle'    => 'Kelola Customer — Panel Admin Terima Jasa',
            'currentUser'  => Auth::user(),
            'customers'    => $filtered,
            'search'       => $search,
            'statusFilter' => $statusFilter,
            'totalCount'   => count($customers),
        ], 'dashboard');
    }

    /**
     * Detail Customer & Riwayat Pemesanan
     */
    public function show(Request $request, string $id): void
    {
        $customer = User::find((int)$id);

        if (!$customer || $customer->role !== User::ROLE_CUSTOMER) {
            $_SESSION['flash_error'] = 'Data customer tidak ditemukan.';
            $this->redirect('admin/customers');
            return;
        }

        $allOrders = Order::all();
        $orders = array_values(array_filter($allOrders, fn($o) => (int)$o->customer_id === (int)$customer->id));
        usort($orders, fn($a, $b) => (int)$b->id <=> (int)$a->id);

        $completedOrders = array_filter($orders, fn($o) => $o->status === Order::STATUS_COMPLETED);
        $activeOrders    = array_filter($orders, fn($o) => in_array($o->status, [Order::STATUS_ACCEPTED, Order::STATUS_IN_PROGRESS]));

        $totalSpent = 0;
        foreach ($completedOrders as $co) {
            $srv = $co->service();
            if ($srv) {
                $totalSpent += (float)$srv->estimated_price;
            }
        }

        $this->view('admin/customers/show', [
            'pageTitle'       => 'Detail Customer: ' . e($customer->name) . ' — Panel Admin',
            'currentUser'     => Auth::user(),
            'customer'        => $customer,
            'orders'          => $orders,
            'totalOrders'     => count($orders),
            'completedCount'  => count($completedOrders),
            'activeCount'     => count($activeOrders),
            'totalSpent'      => $totalSpent,
        ], 'dashboard');
    }

    /**
     * Form Edit Customer
     */
    public function edit(Request $request, string $id): void
    {
        $customer = User::find((int)$id);

        if (!$customer || $customer->role !== User::ROLE_CUSTOMER) {
            $_SESSION['flash_error'] = 'Customer tidak ditemukan.';
            $this->redirect('admin/customers');
            return;
        }

        $this->view('admin/customers/edit', [
            'pageTitle'   => 'Edit Customer: ' . e($customer->name) . ' — Panel Admin',
            'currentUser' => Auth::user(),
            'customer'    => $customer,
        ], 'dashboard');
    }

    /**
     * Simpan Perubahan Customer
     */
    public function update(Request $request, string $id): void
    {
        $customer = User::find((int)$id);

        if (!$customer || $customer->role !== User::ROLE_CUSTOMER) {
            $_SESSION['flash_error'] = 'Customer tidak ditemukan.';
            $this->redirect('admin/customers');
            return;
        }

        $name   = trim((string)$request->input('name'));
        $email  = trim((string)$request->input('email'));
        $phone  = trim((string)$request->input('phone'));
        $status = trim((string)$request->input('status', 'active'));

        if (empty($name) || empty($email)) {
            $_SESSION['flash_error'] = 'Nama dan email wajib diisi.';
            $this->redirect("admin/customers/{$id}/edit");
            return;
        }

        $customer->name   = $name;
        $customer->email  = $email;
        $customer->phone  = $phone;
        $customer->status = in_array($status, ['active', 'inactive', 'suspended']) ? $status : 'active';

        $customer->save();

        $_SESSION['flash_success'] = "Data pelanggan '{$customer->name}' berhasil diperbarui.";
        $this->redirect("admin/customers/{$id}");
    }

    /**
     * Toggle Aktif / Nonaktif Customer
     */
    public function toggleStatus(Request $request, string $id): void
    {
        $customer = User::find((int)$id);

        if (!$customer || $customer->role !== User::ROLE_CUSTOMER) {
            $_SESSION['flash_error'] = 'Customer tidak ditemukan.';
            $this->redirect('admin/customers');
            return;
        }

        $newStatus = ($customer->status === 'active') ? 'inactive' : 'active';
        $customer->status = $newStatus;
        $customer->save();

        $statusLabel = ($newStatus === 'active') ? 'diaktifkan' : 'dinonaktifkan';
        $_SESSION['flash_success'] = "Status akun customer '{$customer->name}' berhasil {$statusLabel}.";

        $referer = $_SERVER['HTTP_REFERER'] ?? url('admin/customers');
        header("Location: {$referer}");
        exit;
    }

    /**
     * Hapus Customer jika Diperlukan
     */
    public function delete(Request $request, string $id): void
    {
        $customer = User::find((int)$id);

        if (!$customer || $customer->role !== User::ROLE_CUSTOMER) {
            $_SESSION['flash_error'] = 'Customer tidak ditemukan.';
            $this->redirect('admin/customers');
            return;
        }

        $name = $customer->name;
        $customer->delete();

        $_SESSION['flash_success'] = "Customer '{$name}' berhasil dihapus dari sistem.";
        $this->redirect('admin/customers');
    }
}
