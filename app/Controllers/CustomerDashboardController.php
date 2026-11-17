<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Models\User;
use App\Models\Order;
use App\Models\Category;

/**
 * CustomerDashboardController - Dashboard Khusus Pengguna (Customer)
 */
class CustomerDashboardController extends Controller
{
    public function index(Request $request): void
    {
        $userSession = Auth::user();
        $userModel = User::find($userSession['id']);

        // Data spesifik Customer
        $orders = $userModel ? $userModel->customerOrders() : [];
        $aiChats = $userModel ? $userModel->aiChats() : [];
        $categories = Category::all();

        $stats = [
            'total_orders'    => count($orders),
            'active_orders'   => count(array_filter($orders, fn($o) => in_array($o->status, ['pending', 'accepted', 'in_progress']))),
            'completed_orders'=> count(array_filter($orders, fn($o) => $o->status === 'completed')),
            'total_ai_chats'  => count($aiChats)
        ];

        $this->view('dashboard/customer', [
            'pageTitle'   => 'Dashboard Customer — Terima Jasa',
            'currentUser' => $userSession,
            'userModel'   => $userModel,
            'stats'       => $stats,
            'orders'      => $orders,
            'aiChats'     => $aiChats,
            'categories'  => $categories
        ], 'dashboard');
    }
}
