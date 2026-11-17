<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\ServiceCategory;

/**
 * HomeController - Mengelola halaman depan / Landing Page
 */
class HomeController extends Controller
{
    public function index(Request $request): void
    {
        $categories = ServiceCategory::all();

        $stats = [
            'total_technicians' => '15.400+',
            'completed_orders'  => '98.500+',
            'customer_rating'   => '4.9 / 5.0',
            'cities_covered'    => '45+ Kota di Indonesia'
        ];

        $steps = [
            [
                'step'        => '01',
                'title'       => 'Pilih Jasa / Konsultasi AI',
                'description' => 'Jelaskan kerusakan perangkat Anda atau konsultasikan gejala awal secara cepat dengan AI Assistant.',
                'icon'        => 'bi-chat-dots-fill',
                'color'       => '#2563eb'
            ],
            [
                'step'        => '02',
                'title'       => 'Teknisi Terverifikasi Ditetapkan',
                'description' => 'Sistem mencocokkan Anda dengan mitra teknisi terdekat yang memiliki sertifikasi dan ulasan teruji.',
                'icon'        => 'bi-person-check-fill',
                'color'       => '#0284c7'
            ],
            [
                'step'        => '03',
                'title'       => 'Pengerjaan Transparan di Lokasi',
                'description' => 'Teknisi datang tepat waktu sesuai jadwal dengan transparansi estimasi biaya pengerjaan tanpa biaya tersembunyi.',
                'icon'        => 'bi-tools',
                'color'       => '#059669'
            ],
            [
                'step'        => '04',
                'title'       => 'Selesai & Bergaransi',
                'description' => 'Pekerjaan selesai diuji coba bersama Anda. Nikmati garansi resmi servis untuk ketenangan pikiran.',
                'icon'        => 'bi-shield-check',
                'color'       => '#7c3aed'
            ]
        ];

        $testimonials = [
            [
                'name'    => 'Anisa Maharani',
                'city'    => 'Jakarta Selatan',
                'comment' => 'AC kantor tiba-tiba mati saat meeting. Pesan teknisi lewat Terima Jasa, 40 menit langsung datang dan selesai rapi. Sangat terbantu!',
                'rating'  => 5,
                'service' => 'Servis AC'
            ],
            [
                'name'    => 'Rian Firmansyah',
                'city'    => 'Surabaya Timur',
                'comment' => 'Estimasi biaya dari awal sangat jelas. Nggak ada tawar-menawar aneh atau biaya siluman. Teknisi ramah dan profesional.',
                'rating'  => 5,
                'service' => 'Kelistrikan Rumah'
            ],
            [
                'name'    => 'Dewi Sartika',
                'city'    => 'Bandung',
                'comment' => 'Fitur konsep AI konsultasinya keren banget! Bisa tahu perkiraan komponen apa yang rusak sebelum teknisi datang ke rumah.',
                'rating'  => 5,
                'service' => 'Mesin Cuci & TV'
            ]
        ];

        $this->view('home/index', [
            'pageTitle'    => 'Terima Jasa — Platform Solusi Teknisi & Servis Terpercaya #1',
            'categories'   => $categories,
            'stats'        => $stats,
            'steps'        => $steps,
            'testimonials' => $testimonials
        ], 'main');
    }
}
