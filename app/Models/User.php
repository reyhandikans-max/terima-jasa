<?php
namespace App\Models;

use App\Core\Model;
use App\Helpers\PhoneHelper;

/**
 * User Model
 * Entitas Pengguna (Admin, Customer, Teknisi)
 */
class User extends Model
{
    public const ROLE_ADMIN    = 'admin';
    public const ROLE_CUSTOMER = 'customer';
    public const ROLE_TEKNISI  = 'teknisi';

    public const STATUS_ACTIVE    = 'active';
    public const STATUS_INACTIVE  = 'inactive';
    public const STATUS_SUSPENDED = 'suspended';

    protected string $table = 'users';

    protected array $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'phone',
        'remember_token',
        'created_at',
        'updated_at'
    ];

    /**
     * Aturan Validasi untuk User
     */
    public static function validationRules(?int $id = null): array
    {
        $uniqueRule = $id ? "unique:users,email,{$id}" : "unique:users,email";
        return [
            'name'     => 'required|min:3|max:150',
            'email'    => "required|email|{$uniqueRule}",
            'password' => 'required|min:6',
            'role'     => 'required|in:admin,customer,teknisi',
            'status'   => 'in:active,inactive,suspended'
        ];
    }

    // ========================================================
    // RELATIONSHIPS
    // ========================================================

    /**
     * User 1 : 1 TechnicianProfile
     * Profil spesifik jika peran adalah Teknisi
     */
    public function technicianProfile(): ?TechnicianProfile
    {
        $profile = $this->hasOne(TechnicianProfile::class, 'user_id');
        if ($profile) {
            return $profile;
        }
        return TechnicianProfile::find((int)$this->id);
    }

    /**
     * Customer/User 1 : N Orders
     * Seluruh pesanan yang dibuat oleh Customer ini
     */
    public function customerOrders(): array
    {
        return Order::getByCustomerId((int)$this->id);
    }

    /**
     * Technician/User 1 : N Orders
     * Seluruh pesanan/pekerjaan yang ditugaskan ke Teknisi ini
     */
    public function technicianOrders(): array
    {
        $orders = $this->hasMany(Order::class, 'technician_id');
        if (!empty($orders)) {
            return $orders;
        }
        return array_values(array_filter(Order::all(), fn($o) => (int)$o->technician_id === (int)$this->id));
    }

    /**
     * Customer/User 1 : N AI Chats
     * Seluruh riwayat konsultasi chat AI milik Customer ini
     */
    public function aiChats(): array
    {
        return $this->hasMany(AiChat::class, 'customer_id');
    }

    /**
     * Ulasan yang ditulis oleh Customer
     */
    public function reviewsGiven(): array
    {
        return $this->hasMany(Review::class, 'customer_id');
    }

    /**
     * Ulasan yang diterima oleh Teknisi
     */
    public function reviewsReceived(): array
    {
        $reviews = $this->hasMany(Review::class, 'technician_id');
        if (!empty($reviews)) {
            return $reviews;
        }
        return array_values(array_filter(Review::all(), fn($r) => (int)$r->technician_id === (int)$this->id));
    }

    // ========================================================
    // HELPER & AUTH METHODS
    // ========================================================

    public function isCustomer(): bool
    {
        return $this->role === self::ROLE_CUSTOMER;
    }

    public function isTeknisi(): bool
    {
        return $this->role === self::ROLE_TEKNISI;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Override all() with graceful mock fallback
     */
    public static function all(): array
    {
        $dbResults = parent::all();
        if (!empty($dbResults)) {
            $existingIds = array_map(fn($u) => (int)$u->id, $dbResults);
            foreach (self::getMockUsers() as $mock) {
                if (!in_array((int)$mock['id'], $existingIds)) {
                    $m = new static($mock);
                    $m->id = $mock['id'];
                    $dbResults[] = $m;
                }
            }
            return $dbResults;
        }

        $models = [];
        foreach (self::getMockUsers() as $mock) {
            $model = new static($mock);
            $model->id = $mock['id'];
            $models[] = $model;
        }
        return $models;
    }

    /**
     * Override find() with graceful mock fallback
     */
    public static function find($id): ?static
    {
        $item = parent::find($id);
        if ($item) {
            return $item;
        }

        foreach (self::getMockUsers() as $mock) {
            if ((int)$mock['id'] === (int)$id) {
                $model = new static($mock);
                $model->id = $mock['id'];
                return $model;
            }
        }

        return null;
    }

    /**
     * Find user by email (Database first with fallback)
     */
    public static function findByEmail(string $email): ?array
    {
        $user = static::firstWhere('email', strtolower(trim($email)));
        if ($user) {
            $data = $user->toArray();
            $data['avatar'] = 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150';
            $data['badge'] = ucfirst($data['role']);
            return $data;
        }

        foreach (self::getMockUsers() as $mock) {
            if (strtolower($mock['email']) === strtolower($email)) {
                return $mock;
            }
        }
        return null;
    }

    /**
     * Find user by role (Database first with fallback)
     */
    public static function findByRole(string $role): ?array
    {
        $user = static::firstWhere('role', $role);
        if ($user) {
            $data = $user->toArray();
            $data['avatar'] = 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150';
            $data['badge'] = ucfirst($data['role']);
            return $data;
        }

        foreach (self::getMockUsers() as $mock) {
            if ($mock['role'] === $role) {
                return $mock;
            }
        }
        return null;
    }

    /**
     * Cari entitas User berdasarkan nomor telepon (Database first dengan fallback Mock)
     * Mendukung multi-format: +628xxx, 08xxx, 628xxx
     */
    public static function findByPhone(string $phone): ?static
    {
        $variants = PhoneHelper::getLookupVariants($phone);
        if (empty($variants)) {
            return null;
        }

        $db = static::getDb();
        if ($db) {
            $placeholders = implode(',', array_fill(0, count($variants), '?'));
            $stmt = $db->prepare("SELECT * FROM `users` WHERE `phone` IN ({$placeholders}) LIMIT 1");
            $stmt->execute($variants);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($row) {
                $user = new static();
                $user->attributes = $row;
                return $user;
            }
        }

        // Mock users fallback
        foreach (self::getMockUsers() as $mock) {
            if (!empty($mock['phone'])) {
                $mockVariants = PhoneHelper::getLookupVariants($mock['phone']);
                if (!empty(array_intersect($variants, $mockVariants))) {
                    $user = new static($mock);
                    $user->id = $mock['id'];
                    return $user;
                }
            }
        }

        return null;
    }

    /**
     * Data Pengguna Mock untuk Fallback & Cepat Uji Coba Demo
     */
    public static function getMockUsers(): array
    {
        return [
            [
                'id'         => 1,
                'name'       => 'Budi Santoso',
                'email'      => 'customer@terimajasa.id',
                'role'       => self::ROLE_CUSTOMER,
                'phone'      => '081234567890',
                'avatar'     => 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150&auto=format&fit=crop&q=80',
                'status'     => 'active',
                'badge'      => 'Member Prioritas',
                'created_at' => '2026-01-10 10:15:00'
            ],
            [
                'id'         => 2,
                'name'       => 'Hendra Wijaya',
                'email'      => 'teknisi@terimajasa.id',
                'role'       => self::ROLE_TEKNISI,
                'phone'      => '082198765432',
                'avatar'     => 'https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?w=150&auto=format&fit=crop&q=80',
                'status'     => 'active',
                'badge'      => 'Mitra Pro Berbintang 4.9',
                'created_at' => '2026-01-15 11:30:00'
            ],
            [
                'id'         => 3,
                'name'       => 'Super Admin Terima Jasa',
                'email'      => 'admin@terimajasa.id',
                'role'       => self::ROLE_ADMIN,
                'phone'      => '081100001111',
                'avatar'     => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150&auto=format&fit=crop&q=80',
                'status'     => 'active',
                'badge'      => 'System Administrator',
                'created_at' => '2026-01-01 08:00:00'
            ],
            [
                'id'         => 4,
                'name'       => 'Siti Rahmawati',
                'email'      => 'siti@customer.id',
                'role'       => self::ROLE_CUSTOMER,
                'phone'      => '081399887766',
                'avatar'     => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&auto=format&fit=crop&q=80',
                'status'     => 'active',
                'badge'      => 'Customer Baru',
                'created_at' => '2026-02-05 14:20:00'
            ],
            [
                'id'         => 5,
                'name'       => 'Ahmad Fauzi',
                'email'      => 'ahmad@teknisi.id',
                'role'       => self::ROLE_TEKNISI,
                'phone'      => '081287654321',
                'avatar'     => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&auto=format&fit=crop&q=80',
                'status'     => 'active',
                'badge'      => 'Teknisi Listrik & Panel',
                'created_at' => '2026-02-10 09:15:00'
            ],
            [
                'id'         => 6,
                'name'       => 'Dimas Pratama',
                'email'      => 'dimas@teknisi.id',
                'role'       => self::ROLE_TEKNISI,
                'phone'      => '085211223344',
                'avatar'     => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&auto=format&fit=crop&q=80',
                'status'     => 'active',
                'badge'      => 'Menunggu Verifikasi',
                'created_at' => '2026-03-01 16:45:00'
            ],
            [
                'id'         => 7,
                'name'       => 'Rian Syahputra',
                'email'      => 'rian@teknisi.id',
                'role'       => self::ROLE_TEKNISI,
                'phone'      => '087799001122',
                'avatar'     => 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=150&auto=format&fit=crop&q=80',
                'status'     => 'inactive',
                'badge'      => 'Verifikasi Ditolak',
                'created_at' => '2026-03-12 13:10:00'
            ],
            [
                'id'         => 8,
                'name'       => 'Dewi Lestari',
                'email'      => 'dewi@customer.id',
                'role'       => self::ROLE_CUSTOMER,
                'phone'      => '081900112233',
                'avatar'     => 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=150&auto=format&fit=crop&q=80',
                'status'     => 'active',
                'badge'      => 'Customer',
                'created_at' => '2026-03-20 17:00:00'
            ],
            [
                'id'         => 9,
                'name'       => 'Anton Kusuma',
                'email'      => 'anton@customer.id',
                'role'       => self::ROLE_CUSTOMER,
                'phone'      => '082233445566',
                'avatar'     => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150&auto=format&fit=crop&q=80',
                'status'     => 'inactive',
                'badge'      => 'Akun Nonaktif',
                'created_at' => '2026-04-02 10:30:00'
            ],
            [
                'id'         => 10,
                'name'       => 'Bambang Irawan, S.Kom',
                'email'      => 'bambang@teknisi.id',
                'role'       => self::ROLE_TEKNISI,
                'phone'      => '081377889900',
                'avatar'     => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80',
                'status'     => 'active',
                'badge'      => 'Spesialis Hardware & Laptop',
                'created_at' => '2026-02-15 10:00:00'
            ],
            [
                'id'         => 11,
                'name'       => 'Dedi Setiawan',
                'email'      => 'dedi@teknisi.id',
                'role'       => self::ROLE_TEKNISI,
                'phone'      => '085711223344',
                'avatar'     => 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=150&auto=format&fit=crop&q=80',
                'status'     => 'active',
                'badge'      => 'Teknisi Komputer & Laptop',
                'created_at' => '2026-02-20 11:00:00'
            ],
            [
                'id'         => 12,
                'name'       => 'Joko Susilo',
                'email'      => 'joko@teknisi.id',
                'role'       => self::ROLE_TEKNISI,
                'phone'      => '081822334455',
                'avatar'     => 'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?w=150&auto=format&fit=crop&q=80',
                'status'     => 'active',
                'badge'      => 'Spesialis Pompa & Plumbing',
                'created_at' => '2026-02-22 12:00:00'
            ],
            [
                'id'         => 13,
                'name'       => 'Reyhan',
                'email'      => 'rey@terimajasa.id',
                'role'       => self::ROLE_ADMIN,
                'phone'      => '085213595836',
                'avatar'     => 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150&auto=format&fit=crop&q=80',
                'status'     => 'active',
                'badge'      => 'System Administrator',
                'created_at' => '2026-01-01 08:00:00'
            ]
        ];
    }
}
