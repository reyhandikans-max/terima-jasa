<?php
namespace App\Models;

use App\Core\Model;

/**
 * Category Model
 * Kategori Layanan Jasa (Relasi 1 : N dengan Service)
 */
class Category extends Model
{
    public const STATUS_ACTIVE   = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected string $table = 'categories';

    protected array $fillable = [
        'name',
        'description',
        'status',
        'created_at',
        'updated_at'
    ];

    /**
     * Aturan Validasi untuk Category
     */
    public static function validationRules(?int $id = null): array
    {
        $uniqueName = $id ? "unique:categories,name,{$id}" : "unique:categories,name";
        return [
            'name'        => "required|min:3|max:100|{$uniqueName}",
            'description' => 'max:1000',
            'status'      => 'in:active,inactive'
        ];
    }

    // ========================================================
    // RELATIONSHIPS
    // ========================================================

    /**
     * Category 1 : N Services
     * Seluruh layanan servis spesifik dalam kategori ini
     */
    public function services(): array
    {
        $services = $this->hasMany(Service::class, 'category_id');
        if (!empty($services)) {
            return $services;
        }

        // Fallback filter from mock services
        return array_values(array_filter(Service::all(), fn($s) => (int)$s->category_id === (int)$this->id));
    }

    /**
     * Override all() with graceful mock fallback
     */
    public static function all(): array
    {
        $dbResults = parent::all();
        if (!empty($dbResults)) {
            return $dbResults;
        }

        return self::getMockCategories();
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

        foreach (self::getMockCategories() as $mock) {
            if ((int)$mock->id === (int)$id) {
                return $mock;
            }
        }

        return null;
    }

    /**
     * Mock Categories
     */
    public static function getMockCategories(): array
    {
        $data = [
            [
                'id'          => 1,
                'name'        => 'Servis AC & Pendingin',
                'description' => 'Layanan cuci AC, perbaikan freon, kompresor dan instalasi AC baru bergaransi.',
                'status'      => 'active',
                'icon'        => 'bi-snow',
                'color'       => '#0284c7',
                'bg_light'    => '#e0f2fe'
            ],
            [
                'id'          => 2,
                'name'        => 'Elektronik & TV',
                'description' => 'Perbaikan TV LED/Smart TV, kulkas, microwave, mesin cuci, dan audio sound system.',
                'status'      => 'active',
                'icon'        => 'bi-tv',
                'color'       => '#7c3aed',
                'bg_light'    => '#ede9fe'
            ],
            [
                'id'          => 3,
                'name'        => 'Komputer & Laptop',
                'description' => 'Servis laptop mati, ganti LCD & keyboard, upgrade SSD/RAM, instalasi OS, dan perbaikan jaringan.',
                'status'      => 'active',
                'icon'        => 'bi-laptop',
                'color'       => '#059669',
                'bg_light'    => '#d1fae5'
            ],
            [
                'id'          => 4,
                'name'        => 'Kelistrikan Rumah',
                'description' => 'Perbaikan korsleting darurat, instalasi stop kontak, panel MCB, dan penataan kabel.',
                'status'      => 'active',
                'icon'        => 'bi-lightning-charge',
                'color'       => '#d97706',
                'bg_light'    => '#fef3c7'
            ],
            [
                'id'          => 5,
                'name'        => 'Plumbing & Pompa Air',
                'description' => 'Perbaikan pipa bocor, instalasi toren, perbaikan pompa air mati/bunyi berisik, dan saluran mampet.',
                'status'      => 'active',
                'icon'        => 'bi-droplet-half',
                'color'       => '#0891b2',
                'bg_light'    => '#cffafe'
            ]
        ];

        $models = [];
        foreach ($data as $d) {
            $cat = new static($d);
            $cat->id = $d['id'];
            $cat->icon = $d['icon'];
            $cat->color = $d['color'];
            $cat->bg_light = $d['bg_light'];
            $models[] = $cat;
        }
        return $models;
    }
}
