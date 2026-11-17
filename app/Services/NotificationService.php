<?php
namespace App\Services;

use App\Core\Model;
use App\Models\Notification;
use App\Models\Order;
use App\Models\User;
use PDO;

/**
 * NotificationService — Layanan Terpusat Manajemen Notifikasi Aplikasi Terima Jasa
 * Menangani pembuatan notifikasi, penyimpanan database, otorisasi penerima, dan integrasi event bisnis nyata.
 */
class NotificationService
{
    /**
     * Pastikan tabel notifications tersedia di database secara otomatis
     */
    public static function ensureTableExists(): void
    {
        static $checked = false;
        if ($checked) return;

        $db = Model::getDb();
        if (!$db) return;

        try {
            $db->exec("
                CREATE TABLE IF NOT EXISTS `notifications` (
                    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `user_id` BIGINT UNSIGNED NOT NULL,
                    `type` VARCHAR(50) NOT NULL,
                    `title` VARCHAR(150) NOT NULL,
                    `message` TEXT NOT NULL,
                    `related_type` VARCHAR(50) NULL,
                    `related_id` BIGINT UNSIGNED NULL,
                    `read_at` TIMESTAMP NULL DEFAULT NULL,
                    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    CONSTRAINT `fk_notifications_user` 
                        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) 
                        ON DELETE CASCADE ON UPDATE CASCADE,
                    INDEX `idx_notif_user_id` (`user_id`),
                    INDEX `idx_notif_read_at` (`read_at`),
                    INDEX `idx_notif_user_unread` (`user_id`, `read_at`),
                    INDEX `idx_notif_related` (`related_type`, `related_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
            $checked = true;
        } catch (\Throwable $e) {
            // Abaikan jika tabel sudah dibuat
        }
    }

    /**
     * Buat dan simpan notifikasi baru dengan proteksi anti-duplikasi
     */
    public static function create(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?string $relatedType = null,
        ?int $relatedId = null
    ): ?Notification {
        self::ensureTableExists();

        if ($userId <= 0) {
            return null;
        }

        $db = Model::getDb();

        // 1. Proteksi Anti-Duplikasi: Cegah pembuatan notifikasi ganda dalam jendela 10 detik
        if ($db) {
            try {
                $timeThreshold = date('Y-m-d H:i:s', time() - 10);
                $checkStmt = $db->prepare("
                    SELECT id FROM `notifications`
                    WHERE `user_id` = ? AND `type` = ? AND `related_type` <=> ? AND `related_id` <=> ?
                      AND `created_at` >= ?
                    LIMIT 1
                ");
                $checkStmt->execute([$userId, $type, $relatedType, $relatedId, $timeThreshold]);
                if ($checkStmt->fetchColumn()) {
                    return null; // Notifikasi identik baru saja dibuat, hindari duplikasi
                }
            } catch (\Throwable $e) {
                // Lanjut bila ada masalah query deduplikasi
            }
        }

        // 2. Simpan notifikasi ke database
        try {
            $notif = Notification::create([
                'user_id'      => $userId,
                'type'         => $type,
                'title'        => $title,
                'message'      => $message,
                'related_type' => $relatedType,
                'related_id'   => $relatedId,
                'read_at'      => null,
                'created_at'   => date('Y-m-d H:i:s')
            ]);

            return $notif;
        } catch (\Throwable $e) {
            // Fallback penyimpanan session bila database bermasalah
            if (!isset($_SESSION['mock_notifications'][$userId])) {
                $_SESSION['mock_notifications'][$userId] = [];
            }
            $mockNotif = new Notification([
                'id'           => time() + rand(100, 999),
                'user_id'      => $userId,
                'type'         => $type,
                'title'        => $title,
                'message'      => $message,
                'related_type' => $relatedType,
                'related_id'   => $relatedId,
                'read_at'      => null,
                'created_at'   => date('Y-m-d H:i:s')
            ]);
            $_SESSION['mock_notifications'][$userId][] = $mockNotif;
            return $mockNotif;
        }
    }

    /**
     * Dapatkan daftar notifikasi untuk pengguna tertentu (dengan pagination & filter)
     */
    public static function getByUser(int $userId, int $limit = 20, int $offset = 0, ?string $filter = null): array
    {
        self::ensureTableExists();
        $db = Model::getDb();

        if ($db) {
            try {
                $sql = "SELECT * FROM `notifications` WHERE `user_id` = ?";
                $params = [$userId];

                if ($filter === 'unread') {
                    $sql .= " AND `read_at` IS NULL";
                } elseif ($filter === 'read') {
                    $sql .= " AND `read_at` IS NOT NULL";
                }

                $sql .= " ORDER BY `id` DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $results = [];
                foreach ($rows as $row) {
                    $n = new Notification();
                    $n->fill($row);
                    $results[] = $n;
                }
                return $results;
            } catch (\Throwable $e) {
                // Lanjut ke fallback session
            }
        }

        // Session fallback
        $list = $_SESSION['mock_notifications'][$userId] ?? [];
        if ($filter === 'unread') {
            $list = array_filter($list, fn($n) => !$n->isRead());
        } elseif ($filter === 'read') {
            $list = array_filter($list, fn($n) => $n->isRead());
        }

        usort($list, fn($a, $b) => (int)$b->id <=> (int)$a->id);
        return array_slice($list, $offset, $limit);
    }

    /**
     * Dapatkan jumlah notifikasi yang belum dibaca untuk pengguna
     */
    public static function getUnreadCount(int $userId): int
    {
        self::ensureTableExists();
        $db = Model::getDb();

        if ($db) {
            try {
                $stmt = $db->prepare("SELECT COUNT(*) FROM `notifications` WHERE `user_id` = ? AND `read_at` IS NULL");
                $stmt->execute([$userId]);
                return (int)$stmt->fetchColumn();
            } catch (\Throwable $e) {
                // Fallback
            }
        }

        $list = $_SESSION['mock_notifications'][$userId] ?? [];
        $unread = array_filter($list, fn($n) => !$n->isRead());
        return count($unread);
    }

    /**
     * Tandai satu notifikasi sebagai dibaca (Anti-IDOR: validasi kepemilikan ketat)
     */
    public static function markAsRead(int $notificationId, int $userId): bool
    {
        self::ensureTableExists();
        $db = Model::getDb();

        if ($db) {
            try {
                $stmt = $db->prepare("
                    UPDATE `notifications` 
                    SET `read_at` = NOW() 
                    WHERE `id` = ? AND `user_id` = ? AND `read_at` IS NULL
                ");
                $stmt->execute([$notificationId, $userId]);
                return $stmt->rowCount() > 0;
            } catch (\Throwable $e) {
                // Fallback
            }
        }

        if (isset($_SESSION['mock_notifications'][$userId])) {
            foreach ($_SESSION['mock_notifications'][$userId] as $notif) {
                if ((int)$notif->id === $notificationId) {
                    $notif->read_at = date('Y-m-d H:i:s');
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Tandai semua notifikasi milik pengguna sebagai dibaca
     */
    public static function markAllAsRead(int $userId): int
    {
        self::ensureTableExists();
        $db = Model::getDb();

        if ($db) {
            try {
                $stmt = $db->prepare("
                    UPDATE `notifications` 
                    SET `read_at` = NOW() 
                    WHERE `user_id` = ? AND `read_at` IS NULL
                ");
                $stmt->execute([$userId]);
                return $stmt->rowCount();
            } catch (\Throwable $e) {
                // Fallback
            }
        }

        $count = 0;
        if (isset($_SESSION['mock_notifications'][$userId])) {
            foreach ($_SESSION['mock_notifications'][$userId] as $notif) {
                if (!$notif->isRead()) {
                    $notif->read_at = date('Y-m-d H:i:s');
                    $count++;
                }
            }
        }
        return $count;
    }

    // ================================================================
    // EVENT BISNIS NYATA (DISPATCHERS)
    // ================================================================

    /**
     * 1. Event: Pesanan Berhasil Dibuat
     * Penerima: Customer (Konfirmasi pesanan) & Teknisi terkait (Permintaan baru masuk)
     */
    public static function notifyOrderCreated(Order $order): array
    {
        $orderCode = "#TJ-" . str_pad($order->id, 5, '0', STR_PAD_LEFT);
        $service = $order->service();
        $serviceName = $service ? $service->name : 'Layanan Servis';
        $createdNotifs = [];

        // 1. Notifikasi untuk Customer
        $notifCustomer = self::create(
            (int)$order->customer_id,
            Notification::TYPE_ORDER_CREATED,
            "Pesanan {$orderCode} Berhasil Dibuat",
            "Pesanan Anda untuk layanan {$serviceName} telah berhasil dicatat. Menunggu konfirmasi dari mitra teknisi.",
            'order',
            (int)$order->id
        );
        if ($notifCustomer) {
            $createdNotifs[] = $notifCustomer;
        }

        // 2. Notifikasi untuk Teknisi yang ditugaskan (Permintaan Baru)
        if (!empty($order->technician_id)) {
            $notifTech = self::create(
                (int)$order->technician_id,
                Notification::TYPE_ORDER_REQUEST,
                "Permintaan Pesanan Baru Masuk ({$orderCode})",
                "Ada pesanan baru untuk {$serviceName} di lokasi {$order->location}. Silakan tinjau dan konfirmasi segera.",
                'order',
                (int)$order->id
            );
            if ($notifTech) {
                $createdNotifs[] = $notifTech;
            }
        }

        return $createdNotifs;
    }

    /**
     * 2. Event: Teknisi Menerima Pesanan (accepted)
     * Penerima: Customer
     */
    public static function notifyOrderAccepted(Order $order): ?Notification
    {
        $orderCode = "#TJ-" . str_pad($order->id, 5, '0', STR_PAD_LEFT);
        $tech = $order->technician();
        $techName = $tech ? $tech->name : 'Mitra Teknisi';

        return self::create(
            (int)$order->customer_id,
            Notification::TYPE_ORDER_ACCEPTED,
            "Teknisi Menerima Pesanan Anda ({$orderCode})",
            "Kabar baik! Mitra teknisi {$techName} telah menyetujui pesanan Anda dan siap berkunjung sesuai jadwal yang ditentukan.",
            'order',
            (int)$order->id
        );
    }

    /**
     * 3. Event: Teknisi Menolak Pesanan (rejected)
     * Penerima: Customer
     */
    public static function notifyOrderRejected(Order $order, int $techId): void
    {
        $orderCode = "#TJ-" . str_pad($order->id, 5, '0', STR_PAD_LEFT);

        self::create(
            (int)$order->customer_id,
            'order_rejected',
            "Pembaruan Pencarian Teknisi ({$orderCode})",
            "Teknisi sebelumnya sedang berhalangan. Sistem kami sedang mengalihkan pesanan Anda ke mitra teknisi terverifikasi lainnya di area Anda.",
            'order',
            (int)$order->id
        );
    }

    /**
     * 4. Event: Pengerjaan Servis Dimulai (in_progress)
     * Penerima: Customer
     */
    public static function notifyOrderInProgress(Order $order): ?Notification
    {
        $orderCode = "#TJ-" . str_pad($order->id, 5, '0', STR_PAD_LEFT);
        $tech = $order->technician();
        $techName = $tech ? $tech->name : 'Mitra Teknisi';

        return self::create(
            (int)$order->customer_id,
            Notification::TYPE_ORDER_IN_PROGRESS,
            "Pekerjaan Servis Dimulai ({$orderCode})",
            "Teknisi {$techName} telah berada di lokasi dan mulai melakukan diagnosa serta pengerjaan servis unit Anda.",
            'order',
            (int)$order->id
        );
    }

    /**
     * 5. Event: Pengerjaan Servis Selesai (completed)
     * Penerima: Customer (Klaim garansi & beri ulasan) & Teknisi (Pekerjaan sukses)
     */
    public static function notifyOrderCompleted(Order $order): ?Notification
    {
        $orderCode = "#TJ-" . str_pad($order->id, 5, '0', STR_PAD_LEFT);

        // Notifikasi ke Customer
        $customerNotif = self::create(
            (int)$order->customer_id,
            Notification::TYPE_ORDER_COMPLETED,
            "Pekerjaan Selesai & Garansi Aktif ({$orderCode})",
            "Servis telah berhasil diselesaikan oleh teknisi. Pekerjaan Anda terlindungi Garansi 14 Hari. Mohon berikan rating dan ulasan Anda!",
            'order',
            (int)$order->id
        );

        // Notifikasi ke Teknisi
        if (!empty($order->technician_id)) {
            self::create(
                (int)$order->technician_id,
                Notification::TYPE_ORDER_COMPLETED,
                "Pekerjaan Berhasil Diselesaikan ({$orderCode})",
                "Selamat! Anda telah menyelesaikan pekerjaan {$orderCode} dengan sukses. Terima kasih atas kerja profesional Anda.",
                'order',
                (int)$order->id
            );
        }

        return $customerNotif;
    }

    /**
     * 6. Event: Pesanan Dibatalkan (cancelled)
     * Penerima: Pihak sebaliknya (Customer atau Teknisi)
     */
    public static function notifyOrderCancelled(Order $order, string $cancelledBy = 'customer'): void
    {
        $orderCode = "#TJ-" . str_pad($order->id, 5, '0', STR_PAD_LEFT);

        if ($cancelledBy === 'customer') {
            // Notifikasi ke Teknisi jika pesanan sudah memiliki teknisi
            if (!empty($order->technician_id)) {
                self::create(
                    (int)$order->technician_id,
                    'order_cancelled',
                    "Pesanan Dibatalkan oleh Pelanggan ({$orderCode})",
                    "Pelanggan telah membatalkan pesanan {$orderCode}. Anda tidak perlu melakukan kunjungan untuk jadwal ini.",
                    'order',
                    (int)$order->id
                );
            }
        } else {
            // Notifikasi ke Customer jika dibatalkan oleh sistem / admin / teknisi
            self::create(
                (int)$order->customer_id,
                'order_cancelled',
                "Pesanan Dibatalkan ({$orderCode})",
                "Pesanan {$orderCode} telah dibatalkan. Jika ada pertanyaan, hubungi tim dukungan Terima Jasa.",
                'order',
                (int)$order->id
            );
        }
    }

    /**
     * 7. Event: Pendaftaran Teknisi Baru (Menunggu Verifikasi)
     * Penerima: Seluruh Administrator platform
     */
    public static function notifyTechnicianRegistered(User $techUser): void
    {
        $admins = User::where('role', User::ROLE_ADMIN);
        foreach ($admins as $admin) {
            self::create(
                (int)$admin->id,
                'technician_registered',
                "Pendaftaran Mitra Teknisi Baru: {$techUser->name}",
                "Mitra teknisi baru {$techUser->name} ({$techUser->email}) telah mendaftar dan membutuhkan peninjauan berkas verifikasi.",
                'technician',
                (int)$techUser->id
            );
        }
    }

    /**
     * 8. Event: Teknisi Berhasil Diverifikasi oleh Admin
     * Penerima: Teknisi yang diverifikasi
     */
    public static function notifyTechnicianVerified(User $techUser): void
    {
        self::create(
            (int)$techUser->id,
            'technician_verified',
            "Selamat! Akun Mitra Anda Telah Terverifikasi",
            "Administrator telah menyetujui berkas kompetensi Anda. Status akun Anda kini Terverifikasi dan siap menerima pesanan dari pelanggan.",
            'technician',
            (int)$techUser->id
        );
    }
}
