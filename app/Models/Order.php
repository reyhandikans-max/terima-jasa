<?php
namespace App\Models;

use App\Core\Model;

/**
 * Order Model
 * Transaksi Pemesanan Servis antara Customer & Teknisi
 */
class Order extends Model
{
    public const STATUS_PENDING     = 'pending';
    public const STATUS_ACCEPTED    = 'accepted';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED   = 'completed';
    public const STATUS_CANCELLED   = 'cancelled';

    protected string $table = 'orders';

    protected array $fillable = [
        'customer_id',
        'technician_id',
        'service_id',
        'description',
        'location',
        'schedule',
        'problem_image',
        'status',
        'created_at',
        'updated_at'
    ];

    /**
     * Aturan Validasi untuk Order
     */
    public static function validationRules(): array
    {
        return [
            'customer_id' => 'required|numeric',
            'service_id'  => 'required|numeric',
            'description' => 'required|min:5',
            'location'    => 'required|min:5|max:255',
            'schedule'    => 'required',
            'status'      => 'in:pending,accepted,in_progress,completed,cancelled'
        ];
    }

    // ========================================================
    // RELATIONSHIPS
    // ========================================================

    /**
     * Customer/User 1 : N Orders (Inverse)
     */
    public function customer(): ?User
    {
        $u = $this->belongsTo(User::class, 'customer_id');
        if ($u) {
            return $u;
        }

        return User::find((int)$this->customer_id);
    }

    /**
     * Technician/User 1 : N Orders (Inverse)
     */
    public function technician(): ?User
    {
        if (empty($this->technician_id)) {
            return null;
        }

        $u = $this->belongsTo(User::class, 'technician_id');
        if ($u) {
            return $u;
        }

        return User::find((int)$this->technician_id);
    }

    /**
     * Service 1 : N Orders (Inverse)
     */
    public function service(): ?Service
    {
        $s = $this->belongsTo(Service::class, 'service_id');
        if ($s) {
            return $s;
        }

        return Service::find((int)$this->service_id);
    }

    /**
     * Order 1 : 1 Review
     * Ulasan balik dari pelanggan setelah order selesai
     */
    public function review(): ?Review
    {
        $r = $this->hasOne(Review::class, 'order_id');
        if ($r) {
            return $r;
        }

        return Review::findByOrderId((int)$this->id);
    }

    // ========================================================
    // STATUS HELPER METHODS
    // ========================================================

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Customer dapat membatalkan pesanan hanya jika status masih pending
     */
    public function canCancel(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Customer dapat memberi review hanya jika status completed dan belum pernah direview
     */
    public function canReview(): bool
    {
        return $this->status === self::STATUS_COMPLETED && $this->review() === null;
    }

    /**
     * Get order status badge classes & icons
     */
    public function getStatusBadge(): array
    {
        return match ($this->status) {
            self::STATUS_COMPLETED   => ['class' => 'bg-success text-white', 'icon' => 'bi-check-circle-fill', 'label' => 'Selesai'],
            self::STATUS_IN_PROGRESS => ['class' => 'bg-primary text-white', 'icon' => 'bi-gear-wide-connected', 'label' => 'Sedang Dikerjakan'],
            self::STATUS_ACCEPTED    => ['class' => 'bg-info text-dark', 'icon' => 'bi-person-check-fill', 'label' => 'Teknisi Ditugaskan'],
            self::STATUS_CANCELLED   => ['class' => 'bg-danger text-white', 'icon' => 'bi-x-circle-fill', 'label' => 'Dibatalkan'],
            default                  => ['class' => 'bg-warning text-dark', 'icon' => 'bi-hourglass-split', 'label' => 'Menunggu Konfirmasi']
        };
    }

    // ========================================================
    // QUERY & FALLBACK ENGINE
    // ========================================================

    public static function getByCustomerId(int $customerId): array
    {
        $orders = static::where('customer_id', $customerId);
        if (!empty($orders)) {
            return $orders;
        }

        // Check mock orders
        return array_values(array_filter(self::getMockOrders(), fn($o) => (int)$o->customer_id === (int)$customerId));
    }

    public static function all(): array
    {
        $orders = parent::all();
        if (!empty($orders)) {
            return $orders;
        }

        return self::getMockOrders();
    }

    public static function find($id): ?static
    {
        $order = parent::find($id);
        if ($order) {
            return $order;
        }

        foreach (self::getMockOrders() as $mock) {
            if ((int)$mock->id === (int)$id) {
                return $mock;
            }
        }

        return null;
    }

    public static function getMockOrders(): array
    {
        static $mockStore = null;
        if ($mockStore !== null) {
            return $mockStore;
        }

        $data = [
            [
                'id'            => 1,
                'customer_id'   => 1,
                'technician_id' => 2,
                'service_id'    => 1,
                'description'   => 'AC kamar utama kurang dingin dan mengeluarkan hembusan bau apek.',
                'location'      => 'Jl. Kemang Timur No. 10, Jakarta Selatan',
                'schedule'      => '2026-10-10 10:00:00',
                'problem_image' => null,
                'status'        => self::STATUS_COMPLETED,
                'created_at'    => '2026-10-08 09:00:00'
            ],
            [
                'id'            => 2,
                'customer_id'   => 4,
                'technician_id' => 5,
                'service_id'    => 8,
                'description'   => 'MCB sering anjlok saat menyalakan dispenser dan mesin cuci secara bersamaan.',
                'location'      => 'Jl. Cihampelas No. 88, Bandung',
                'schedule'      => '2026-10-11 14:00:00',
                'problem_image' => null,
                'status'        => self::STATUS_IN_PROGRESS,
                'created_at'    => '2026-10-09 11:30:00'
            ],
            [
                'id'            => 3,
                'customer_id'   => 1,
                'technician_id' => 2,
                'service_id'    => 2,
                'description'   => 'Tambah freon untuk 2 unit AC di ruang tamu.',
                'location'      => 'Jl. Kemang Timur No. 10, Jakarta Selatan',
                'schedule'      => '2026-10-12 09:30:00',
                'problem_image' => null,
                'status'        => self::STATUS_PENDING,
                'created_at'    => '2026-10-09 14:15:00'
            ],
            [
                'id'            => 4,
                'customer_id'   => 8,
                'technician_id' => 2,
                'service_id'    => 1,
                'description'   => 'Pembersihan AC 1 PK dan pengecekan kebocoran air di indoor unit.',
                'location'      => 'Jl. Fatmawati Raya No. 25, Jakarta Selatan',
                'schedule'      => '2026-10-13 11:00:00',
                'problem_image' => null,
                'status'        => self::STATUS_ACCEPTED,
                'created_at'    => '2026-10-11 16:20:00'
            ],
            [
                'id'            => 5,
                'customer_id'   => 4,
                'technician_id' => 5,
                'service_id'    => 7,
                'description'   => 'Pemasangan instalasi kabel grounding dan penggantian stop kontak utama.',
                'location'      => 'Jl. Dago Asri No. 14, Bandung',
                'schedule'      => '2026-10-05 13:00:00',
                'problem_image' => null,
                'status'        => self::STATUS_COMPLETED,
                'created_at'    => '2026-10-03 10:00:00'
            ],
            [
                'id'            => 6,
                'customer_id'   => 8,
                'technician_id' => 5,
                'service_id'    => 8,
                'description'   => 'Pemeriksaan jalur kabel panel korslet setelah hujan deras.',
                'location'      => 'Jl. Surya Sumantri No. 7, Bandung',
                'schedule'      => '2026-10-07 15:30:00',
                'problem_image' => null,
                'status'        => self::STATUS_COMPLETED,
                'created_at'    => '2026-10-06 08:45:00'
            ],
            [
                'id'            => 7,
                'customer_id'   => 1,
                'technician_id' => null,
                'service_id'    => 3,
                'description'   => 'Relokasi AC kamar anak dipindahkan ke ruang kerja lantai 2.',
                'location'      => 'Jl. Kemang Timur No. 10, Jakarta Selatan',
                'schedule'      => '2026-10-14 16:00:00',
                'problem_image' => null,
                'status'        => self::STATUS_PENDING,
                'created_at'    => '2026-10-12 11:00:00'
            ],
            [
                'id'            => 8,
                'customer_id'   => 4,
                'technician_id' => 2,
                'service_id'    => 2,
                'description'   => 'Isi freon dibatalkan karena AC sudah diganti unit baru.',
                'location'      => 'Jl. Cihampelas No. 88, Bandung',
                'schedule'      => '2026-10-04 10:00:00',
                'problem_image' => null,
                'status'        => self::STATUS_CANCELLED,
                'created_at'    => '2026-10-02 14:00:00'
            ]
        ];

        $models = [];
        foreach ($data as $d) {
            $m = new static($d);
            $m->id = $d['id'];
            $models[] = $m;
        }
        $mockStore = $models;
        return $mockStore;
    }
}
