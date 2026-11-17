<?php
namespace App\Models;

use App\Core\Model;

/**
 * Service Category Model
 */
class ServiceCategory extends Model
{
    protected string $table = 'service_categories';

    /**
     * Data mock kategori jasa untuk tampilan awal dan landing page
     */
    public static function all(): array
    {
        return [
            [
                'id'          => 1,
                'slug'        => 'servis-ac',
                'name'        => 'Servis AC & Pendingin',
                'description' => 'Cuci AC berkala, isi freon, perbaikan kompresor berisik atau AC bocor air.',
                'icon'        => 'bi-snow',
                'color'       => '#0284c7',
                'bg_light'    => '#e0f2fe',
                'technicians' => '450+ Teknisi',
                'is_popular'  => true
            ],
            [
                'id'          => 2,
                'slug'        => 'elektronik-tv',
                'name'        => 'Elektronik & Smart TV',
                'description' => 'Perbaikan Smart TV LED/OLED, audio speaker, kulkas, microwave, dan mesin cuci.',
                'icon'        => 'bi-tv',
                'color'       => '#7c3aed',
                'bg_light'    => '#ede9fe',
                'technicians' => '320+ Teknisi',
                'is_popular'  => true
            ],
            [
                'id'          => 3,
                'slug'        => 'komputer-laptop',
                'name'        => 'Komputer & Laptop',
                'description' => 'Servis laptop mati total, ganti keyboard/layar, instalasi OS, basmi virus, & rakit PC.',
                'icon'        => 'bi-laptop',
                'color'       => '#2563eb',
                'bg_light'    => '#dbeafe',
                'technicians' => '280+ Teknisi',
                'is_popular'  => true
            ],
            [
                'id'          => 4,
                'slug'        => 'kelistrikan-rumah',
                'name'        => 'Instalasi & Listrik Rumah',
                'description' => 'Perbaikan korsleting listrik, pasang instalasi baru, ganti MCB, dan saklar lampu.',
                'icon'        => 'bi-lightning-charge',
                'color'       => '#d97706',
                'bg_light'    => '#fef3c7',
                'technicians' => '190+ Teknisi',
                'is_popular'  => false
            ],
            [
                'id'          => 5,
                'slug'        => 'plumbing-pipa',
                'name'        => 'Plumbing & Pompa Air',
                'description' => 'Mengatasi pipa tersumbat, kran bocor, perbaikan pompa air Jet Pump dan tandon air.',
                'icon'        => 'bi-droplet-half',
                'color'       => '#059669',
                'bg_light'    => '#d1fae5',
                'technicians' => '210+ Teknisi',
                'is_popular'  => false
            ],
            [
                'id'          => 6,
                'slug'        => 'gadget-hp',
                'name'        => 'Smartphone & Gadget',
                'description' => 'Ganti LCD pecah, ganti baterai drop, perbaikan port charger, dan IC mesin.',
                'icon'        => 'bi-phone',
                'color'       => '#db2777',
                'bg_light'    => '#fce7f3',
                'technicians' => '360+ Teknisi',
                'is_popular'  => true
            ],
            [
                'id'          => 7,
                'slug'        => 'otomotif-darurat',
                'name'        => 'Otomotif & Montir Panggilan',
                'description' => 'Jumper aki mobil mogok, tambal ban darurat, tune up ringan, dan servis rutin berkala.',
                'icon'        => 'bi-car-front',
                'color'       => '#ea580c',
                'bg_light'    => '#ffedd5',
                'technicians' => '175+ Teknisi',
                'is_popular'  => false
            ],
            [
                'id'          => 8,
                'slug'        => 'renovasi-tukang',
                'name'        => 'Tukang & Renovasi Ringan',
                'description' => 'Perbaikan plafon retak, atap genteng bocor, kusen pintu, dan pengecatan dinding.',
                'icon'        => 'bi-tools',
                'color'       => '#4b5563',
                'bg_light'    => '#f3f4f6',
                'technicians' => '230+ Teknisi',
                'is_popular'  => false
            ]
        ];
    }
}
