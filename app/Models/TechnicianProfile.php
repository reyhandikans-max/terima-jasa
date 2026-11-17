<?php
namespace App\Models;

use App\Core\Model;

/**
 * TechnicianProfile Model
 * Profil Mitra Teknisi (Relasi 1 : 1 dengan User)
 */
class TechnicianProfile extends Model
{
    public const AVAILABILITY_AVAILABLE   = 'available';
    public const AVAILABILITY_UNAVAILABLE = 'unavailable';
    public const AVAILABILITY_BUSY        = 'busy';
    public const AVAILABILITY_OFFLINE     = 'offline';

    public const VERIFICATION_PENDING  = 'pending';
    public const VERIFICATION_VERIFIED = 'verified';
    public const VERIFICATION_REJECTED = 'rejected';

    protected string $table = 'technician_profiles';

    protected array $fillable = [
        'user_id',
        'expertise',
        'experience',
        'phone',
        'address',
        'location',
        'availability',
        'verification_status',
        'created_at',
        'updated_at'
    ];

    /**
     * Aturan Validasi untuk TechnicianProfile
     */
    public static function validationRules(?int $id = null): array
    {
        $uniqueUser = $id ? "unique:technician_profiles,user_id,{$id}" : "unique:technician_profiles,user_id";
        return [
            'user_id'             => "required|numeric|{$uniqueUser}",
            'expertise'           => 'required|min:3|max:255',
            'experience'          => 'required|max:100',
            'phone'               => 'required|min:9|max:25',
            'location'            => 'required|min:3|max:150',
            'availability'        => 'in:available,unavailable,busy,offline',
            'verification_status' => 'in:pending,verified,rejected'
        ];
    }

    // ========================================================
    // RELATIONSHIPS
    // ========================================================

    /**
     * TechnicianProfile 1 : 1 User (Inverse)
     */
    public function user(): ?User
    {
        $u = $this->belongsTo(User::class, 'user_id');
        if ($u) {
            return $u;
        }

        return User::find((int)$this->user_id);
    }

    /**
     * Override all() with fallback
     */
    public static function all(): array
    {
        $dbResults = parent::all();
        if (!empty($dbResults)) {
            $existingUserIds = array_map(fn($p) => (int)$p->user_id, $dbResults);
            foreach (self::getMockProfiles() as $mock) {
                if (!in_array((int)$mock->user_id, $existingUserIds)) {
                    $dbResults[] = $mock;
                }
            }
            return $dbResults;
        }

        return self::getMockProfiles();
    }

    /**
     * Override find() with fallback
     */
    public static function find($id): ?static
    {
        $item = parent::find($id);
        if ($item) {
            return $item;
        }

        foreach (self::getMockProfiles() as $mock) {
            if ((int)$mock->id === (int)$id || (int)$mock->user_id === (int)$id) {
                return $mock;
            }
        }

        return null;
    }

    /**
     * Cari profil teknisi berdasarkan user_id
     */
    public static function findByUserId(int $userId): ?static
    {
        $profile = static::firstWhere('user_id', $userId);
        if ($profile) {
            return $profile;
        }

        foreach (self::getMockProfiles() as $mock) {
            if ((int)$mock->user_id === $userId) {
                return $mock;
            }
        }

        return null;
    }

    public static function getMockProfiles(): array
    {
        static $store = null;
        if ($store !== null) {
            return $store;
        }

        $data = [
            [
                'id'                  => 1,
                'user_id'             => 2,
                'expertise'           => 'Servis AC, Kulkas & Chiller',
                'experience'          => '6 Tahun Pengalaman',
                'phone'               => '082198765432',
                'address'             => 'Jl. Tebet Raya No. 45',
                'location'            => 'Jakarta Selatan',
                'availability'        => self::AVAILABILITY_AVAILABLE,
                'verification_status' => self::VERIFICATION_VERIFIED
            ],
            [
                'id'                  => 2,
                'user_id'             => 5,
                'expertise'           => 'Instalasi Listrik, Stop Kontak & Panel MCB',
                'experience'          => '4 Tahun Pengalaman',
                'phone'               => '081287654321',
                'address'             => 'Jl. Sukajadi No. 12',
                'location'            => 'Bandung & Jabodetabek',
                'availability'        => self::AVAILABILITY_AVAILABLE,
                'verification_status' => self::VERIFICATION_VERIFIED
            ],
            [
                'id'                  => 3,
                'user_id'             => 6,
                'expertise'           => 'Reparasi Mesin Cuci & Pompa Air',
                'experience'          => '3 Tahun Pengalaman',
                'phone'               => '085211223344',
                'address'             => 'Jl. Kalimalang Raya No. 18',
                'location'            => 'Jakarta Timur',
                'availability'        => self::AVAILABILITY_OFFLINE,
                'verification_status' => self::VERIFICATION_PENDING
            ],
            [
                'id'                  => 4,
                'user_id'             => 7,
                'expertise'           => 'Perbaikan Komputer & Jaringan LAN',
                'experience'          => '2 Tahun Pengalaman',
                'phone'               => '087799001122',
                'address'             => 'Jl. Ahmad Yani No. 5',
                'location'            => 'Bekasi',
                'availability'        => self::AVAILABILITY_BUSY,
                'verification_status' => self::VERIFICATION_REJECTED
            ],
            [
                'id'                  => 5,
                'user_id'             => 10,
                'expertise'           => 'Spesialis Servis Laptop Mati Total, Motherboard & Komputer PC',
                'experience'          => '7 Tahun Pengalaman',
                'phone'               => '081377889900',
                'address'             => 'Jl. Fatmawati No. 22',
                'location'            => 'Jakarta Selatan',
                'availability'        => self::AVAILABILITY_AVAILABLE,
                'verification_status' => self::VERIFICATION_VERIFIED
            ],
            [
                'id'                  => 6,
                'user_id'             => 11,
                'expertise'           => 'Servis Laptop, Ganti LCD & Keyboard, Komputer PC',
                'experience'          => '3 Tahun Pengalaman',
                'phone'               => '085711223344',
                'address'             => 'Jl. Panjang No. 8',
                'location'            => 'Jakarta Barat',
                'availability'        => self::AVAILABILITY_AVAILABLE,
                'verification_status' => self::VERIFICATION_VERIFIED
            ],
            [
                'id'                  => 7,
                'user_id'             => 12,
                'expertise'           => 'Plumbing, Pipa Bocor, Saluran Mampet & Pompa Air',
                'experience'          => '5 Tahun Pengalaman',
                'phone'               => '081822334455',
                'address'             => 'Jl. Bangka Raya No. 15',
                'location'            => 'Jakarta Selatan',
                'availability'        => self::AVAILABILITY_AVAILABLE,
                'verification_status' => self::VERIFICATION_VERIFIED
            ]
        ];

        $models = [];
        foreach ($data as $d) {
            $m = new static($d);
            $m->id = $d['id'];
            $models[] = $m;
        }
        $store = $models;
        return $store;
    }
}
