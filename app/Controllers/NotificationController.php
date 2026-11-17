<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Auth;
use App\Services\NotificationService;

/**
 * NotificationController — Mengelola Tampilan & API Notifikasi Terintegrasi
 */
class NotificationController extends Controller
{
    /**
     * Halaman Utama Daftar Notifikasi Pengguna
     * GET /notifications
     */
    public function index(Request $request): void
    {
        $user = Auth::user();
        $userId = (int)($user['id'] ?? 0);

        if ($userId <= 0) {
            $this->redirect('login');
            return;
        }

        $filter = trim((string)$request->input('filter', 'all'));
        $validFilter = in_array($filter, ['unread', 'read']) ? $filter : null;

        $page = max(1, (int)$request->input('page', 1));
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        $notifications = NotificationService::getByUser($userId, $perPage, $offset, $validFilter);
        $unreadCount = NotificationService::getUnreadCount($userId);

        $this->view('notifications/index', [
            'pageTitle'     => 'Notifikasi — Terima Jasa',
            'currentUser'   => $user,
            'notifications' => $notifications,
            'unreadCount'   => $unreadCount,
            'activeFilter'  => $filter,
            'currentPage'   => $page,
        ], 'dashboard');
    }

    /**
     * Endpoint API: Ambil Daftar Notifikasi Terbaru Pengguna
     * GET /api/notifications
     */
    public function getNotificationsApi(Request $request): void
    {
        $user = Auth::user();
        $userId = (int)($user['id'] ?? 0);

        if ($userId <= 0) {
            Response::json(['status' => 'error', 'message' => 'Autentikasi diperlukan.'], 401);
            return;
        }

        $limit = max(1, min(50, (int)$request->input('limit', 10)));
        $notifications = NotificationService::getByUser($userId, $limit, 0);
        $unreadCount = NotificationService::getUnreadCount($userId);

        $items = [];
        foreach ($notifications as $n) {
            $iconConfig = $n->getIconConfig();
            $items[] = [
                'id'           => (int)$n->id,
                'type'         => $n->type,
                'title'        => $n->title,
                'message'      => $n->message,
                'related_type' => $n->related_type,
                'related_id'   => (int)$n->related_id,
                'is_read'      => $n->isRead(),
                'read_at'      => $n->read_at,
                'created_at'   => $n->created_at,
                'time_ago'     => $n->getTimeAgo(),
                'action_url'   => $n->getActionUrl(),
                'icon'         => $iconConfig['icon'],
                'icon_class'   => $iconConfig['class'],
            ];
        }

        Response::json([
            'status'       => 'success',
            'unread_count' => $unreadCount,
            'data'         => $items
        ]);
    }

    /**
     * Endpoint API: Ambil Jumlah Notifikasi Belum Dibaca
     * GET /api/notifications/unread-count
     */
    public function getUnreadCountApi(Request $request): void
    {
        $user = Auth::user();
        $userId = (int)($user['id'] ?? 0);

        if ($userId <= 0) {
            Response::json(['status' => 'error', 'message' => 'Autentikasi diperlukan.'], 401);
            return;
        }

        $count = NotificationService::getUnreadCount($userId);

        Response::json([
            'status'       => 'success',
            'unread_count' => $count
        ]);
    }

    /**
     * Endpoint API: Tandai Satu Notifikasi Sebagai Dibaca (Anti-IDOR)
     * POST /api/notifications/{id}/read
     */
    public function markAsReadApi(Request $request, $id): void
    {
        $user = Auth::user();
        $userId = (int)($user['id'] ?? 0);

        if ($userId <= 0) {
            Response::json(['status' => 'error', 'message' => 'Autentikasi diperlukan.'], 401);
            return;
        }

        $success = NotificationService::markAsRead((int)$id, $userId);
        $newUnread = NotificationService::getUnreadCount($userId);

        if (!$success) {
            Response::json([
                'status'  => 'error',
                'message' => 'Notifikasi tidak ditemukan atau bukan milik akun Anda.'
            ], 404);
            return;
        }

        Response::json([
            'status'       => 'success',
            'message'      => 'Notifikasi telah ditandai sebagai dibaca.',
            'unread_count' => $newUnread
        ]);
    }

    /**
     * Endpoint API: Tandai Semua Notifikasi Milik Pengguna Sebagai Dibaca
     * POST /api/notifications/read-all
     */
    public function markAllAsReadApi(Request $request): void
    {
        $user = Auth::user();
        $userId = (int)($user['id'] ?? 0);

        if ($userId <= 0) {
            Response::json(['status' => 'error', 'message' => 'Autentikasi diperlukan.'], 401);
            return;
        }

        $count = NotificationService::markAllAsRead($userId);

        Response::json([
            'status'       => 'success',
            'message'      => "{$count} notifikasi berhasil ditandai sebagai dibaca.",
            'marked_count' => $count,
            'unread_count' => 0
        ]);
    }
}
