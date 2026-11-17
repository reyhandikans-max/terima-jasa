<?php
namespace App\Models;

use App\Core\Model;

/**
 * Notification Model
 * Notifikasi Multirole Terintegrasi dengan Aktivitas Nyata Platform
 */
class Notification extends Model
{
    public const TYPE_ORDER_CREATED         = 'order_created';
    public const TYPE_ORDER_REQUEST         = 'order_request';
    public const TYPE_ORDER_ACCEPTED        = 'order_accepted';
    public const TYPE_ORDER_REJECTED        = 'order_rejected';
    public const TYPE_ORDER_IN_PROGRESS     = 'order_in_progress';
    public const TYPE_ORDER_COMPLETED       = 'order_completed';
    public const TYPE_ORDER_CANCELLED       = 'order_cancelled';
    public const TYPE_TECHNICIAN_REGISTERED = 'technician_registered';
    public const TYPE_TECHNICIAN_VERIFIED   = 'technician_verified';

    protected string $table = 'notifications';

    protected array $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'related_type',
        'related_id',
        'read_at',
        'created_at',
        'updated_at'
    ];

    /**
     * User Penerima Notifikasi (Relasi N : 1)
     */
    public function user(): ?User
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Cek apakah notifikasi sudah dibaca
     */
    public function isRead(): bool
    {
        return !empty($this->read_at);
    }

    /**
     * Tandai notifikasi sebagai dibaca
     */
    public function markAsRead(): bool
    {
        $this->read_at = date('Y-m-d H:i:s');
        return $this->save();
    }

    /**
     * Dapatkan URL aksi detail berdasarkan related_type dan related_id
     */
    public function getActionUrl(): string
    {
        $relType = strtolower((string)$this->related_type);
        $relId   = (int)$this->related_id;

        if ($relType === 'order' && $relId > 0) {
            $user = $this->user();
            $role = $user ? $user->role : 'customer';

            return match ($role) {
                'teknisi' => url('technician/active-jobs'),
                'admin'   => url("admin/orders/{$relId}"),
                default   => url("customer/orders/{$relId}"),
            };
        }

        if ($relType === 'technician' && $relId > 0) {
            return url("admin/technicians/{$relId}");
        }

        return url('notifications');
    }

    /**
     * Dapatkan representasi waktu yang ramah pengguna (contoh: 5 menit lalu)
     */
    public function getTimeAgo(): string
    {
        if (empty($this->created_at)) {
            return 'Baru saja';
        }

        $timestamp = strtotime($this->created_at);
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return 'Baru saja';
        }

        if ($diff < 3600) {
            $mins = max(1, (int)floor($diff / 60));
            return "{$mins} menit lalu";
        }

        if ($diff < 86400) {
            $hours = (int)floor($diff / 3600);
            return "{$hours} jam lalu";
        }

        if ($diff < 172800) {
            return 'Kemarin, ' . date('H:i', $timestamp);
        }

        return date('d M Y, H:i', $timestamp);
    }

    /**
     * Dapatkan konfigurasi icon dan styling warna berdasarkan tipe notifikasi
     */
    public function getIconConfig(): array
    {
        return match ($this->type) {
            'order_created'         => ['icon' => 'bi-plus-circle-fill', 'class' => 'text-primary bg-primary-subtle'],
            'order_accepted'        => ['icon' => 'bi-check-circle-fill', 'class' => 'text-success bg-success-subtle'],
            'order_rejected'        => ['icon' => 'bi-x-circle-fill', 'class' => 'text-warning bg-warning-subtle'],
            'order_in_progress'     => ['icon' => 'bi-gear-wide-connected', 'class' => 'text-info bg-info-subtle'],
            'order_completed'       => ['icon' => 'bi-patch-check-fill', 'class' => 'text-success bg-success-subtle'],
            'order_cancelled'       => ['icon' => 'bi-slash-circle-fill', 'class' => 'text-danger bg-danger-subtle'],
            'technician_registered' => ['icon' => 'bi-person-badge-fill', 'class' => 'text-warning bg-warning-subtle'],
            'technician_verified'   => ['icon' => 'bi-shield-fill-check', 'class' => 'text-success bg-success-subtle'],
            default                 => ['icon' => 'bi-bell-fill', 'class' => 'text-secondary bg-secondary-subtle']
        };
    }
}
