<?php
namespace App\Models;

use App\Core\Model;

/**
 * Service Model
 * Katalog Layanan Servis Spesifik
 */
class Service extends Model
{
    public const STATUS_ACTIVE   = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected string $table = 'services';

    protected array $fillable = [
        'category_id',
        'name',
        'description',
        'estimated_price',
        'status',
        'created_at',
        'updated_at'
    ];

    /**
     * Aturan Validasi untuk Service
     */
    public static function validationRules(): array
    {
        return [
            'category_id'     => 'required|numeric',
            'name'            => 'required|min:3|max:150',
            'estimated_price' => 'required|numeric|min:0',
            'status'          => 'in:active,inactive'
        ];
    }

    // ========================================================
    // RELATIONSHIPS
    // ========================================================

    /**
     * Service N : 1 Category (BelongsTo)
     */
    public function category(): ?Category
    {
        $cat = $this->belongsTo(Category::class, 'category_id');
        if ($cat) {
            return $cat;
        }

        return Category::find((int)$this->category_id);
    }

    /**
     * Service 1 : N Orders
     */
    public function orders(): array
    {
        return $this->hasMany(Order::class, 'service_id');
    }

    /**
     * Dapatkan daftar teknisi yang tersedia untuk layanan ini
     */
    public function availableTechnicians(): array
    {
        $allTechs = TechnicianProfile::all();
        $available = [];

        foreach ($allTechs as $tech) {
            if ($tech->availability === TechnicianProfile::AVAILABILITY_AVAILABLE &&
                $tech->verification_status === TechnicianProfile::VERIFICATION_VERIFIED) {
                $available[] = $tech;
            }
        }

        if (empty($available)) {
            // Fallback available technicians
            return array_slice($allTechs, 0, 2);
        }

        return $available;
    }

    /**
     * Override all() with graceful mock fallback
     */
    public static function all(): array
    {
        $dbResults = parent::all();
        if (!empty($dbResults)) {
            $existingIds = array_map(fn($s) => (int)$s->id, $dbResults);
            foreach (self::getMockServices() as $mock) {
                if (!in_array((int)$mock->id, $existingIds)) {
                    $dbResults[] = $mock;
                }
            }
            return $dbResults;
        }

        return self::getMockServices();
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

        foreach (self::getMockServices() as $mock) {
            if ((int)$mock->id === (int)$id) {
                return $mock;
            }
        }

        return null;
    }

    /**
     * Mock Services
     */
    public static function getMockServices(): array
    {
        $data = [
            [
                'id'              => 1,
                'category_id'     => 1,
                'name'            => 'Cuci AC Standard 0.5 - 2 PK',
                'description'     => 'Pembersihan evaporator, talang air, filter dan kondensor luar. Mengatasi hembusan bau tidak sedap dan meningkatkan efisiensi pendinginan.',
                'estimated_price' => 75000.00,
                'status'          => 'active'
            ],
            [
                'id'              => 2,
                'category_id'     => 1,
                'name'            => 'Isi & Tambah Freon R32 / R410A',
                'description'     => 'Pengecekan manifold, pengetesan kebocoran pipa nepel, dan penambahan freon ramah lingkungan tipe R32/R410A.',
                'estimated_price' => 150000.00,
                'status'          => 'active'
            ],
            [
                'id'              => 3,
                'category_id'     => 1,
                'name'            => 'Bongkar Pasang & Relokasi AC',
                'description'     => 'Pelepasan unit lama dan instalasi di lokasi baru, vacuum jalur pipa freon, serta testing dingin maksimal.',
                'estimated_price' => 250000.00,
                'status'          => 'active'
            ],
            [
                'id'              => 4,
                'category_id'     => 2,
                'name'            => 'Perbaikan Kulkas 1 & 2 Pintu',
                'description'     => 'Diagnosa timer defrost, kompresor macet, kipas tidak berputar, dan kebocoran sistem pendingin kulkas.',
                'estimated_price' => 180000.00,
                'status'          => 'active'
            ],
            [
                'id'              => 5,
                'category_id'     => 2,
                'name'            => 'Servis TV LED Layar Gelap / Mati Total',
                'description'     => 'Perbaikan lampu strip backlight TV LED, perbaikan power supply board, modul T-Con, dan suara tanpa gambar.',
                'estimated_price' => 200000.00,
                'status'          => 'active'
            ],
            [
                'id'              => 6,
                'category_id'     => 3,
                'name'            => 'Instalasi Ulang OS & Optimalisasi Laptop',
                'description'     => 'Instalasi bersih Windows 10/11 atau Linux, pembersihan virus/malware, backup data aman, dan instalasi driver lengkap.',
                'estimated_price' => 100000.00,
                'status'          => 'active'
            ],
            [
                'id'              => 7,
                'category_id'     => 3,
                'name'            => 'Ganti Keyboard & Baterai Laptop',
                'description'     => 'Penggantian suku cadang keyboard macet/rusak dan baterai drop/kembung untuk berbagai merk (Asus, Lenovo, Acer, HP, Dell).',
                'estimated_price' => 120000.00,
                'status'          => 'active'
            ],
            [
                'id'              => 8,
                'category_id'     => 4,
                'name'            => 'Perbaikan Korsleting Listrik Darurat',
                'description'     => 'Pelacakan kabel konslet dalam dinding, penanganan MCB anjlok tiba-tiba, dan perbaikan sekring utama rumah.',
                'estimated_price' => 120000.00,
                'status'          => 'active'
            ],
            [
                'id'              => 9,
                'category_id'     => 4,
                'name'            => 'Pemasangan Titik Lampu & Stop Kontak',
                'description'     => 'Penarikan jalur kabel baru standar SNI menggunakan pipa pelindung conduit, instalasi saklar dan stop kontak grounding.',
                'estimated_price' => 65000.00,
                'status'          => 'active'
            ],
            [
                'id'              => 10,
                'category_id'     => 5,
                'name'            => 'Servis Pompa Air Mati / Cetak-Cetek',
                'description'     => 'Perbaikan otomatis pressure switch, penggantian kapasitor dinamo pompa, dan pengecekan bearing dinamo.',
                'estimated_price' => 110000.00,
                'status'          => 'active'
            ],
            [
                'id'              => 11,
                'category_id'     => 5,
                'name'            => 'Pelancaran Pipa Saluran Air Mampet',
                'description'     => 'Pembersihan sumbatan lemak dapur atau kotoran wastafel tanpa bahan kimia berbahaya menggunakan drain cleaner profesional.',
                'estimated_price' => 135000.00,
                'status'          => 'active'
            ],
            [
                'id'              => 12,
                'category_id'     => 3,
                'name'            => 'Perbaikan Hardware Laptop / Komputer Mati Total',
                'description'     => 'Pemeriksaan jalur power motherboard, perbaikan IC power / charging, short circuit kelistrikan, dan penanganan laptop yang tidak mau menyala sama sekali.',
                'estimated_price' => 180000.00,
                'status'          => 'active'
            ]
        ];

        $models = [];
        foreach ($data as $d) {
            $srv = new static($d);
            $srv->id = $d['id'];
            $models[] = $srv;
        }
        return $models;
    }
}
