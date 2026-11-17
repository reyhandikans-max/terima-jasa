<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use App\Models\Review;
use App\Models\TechnicianProfile;
use App\Services\NotificationService;

/**
 * CustomerOrderController - Mengelola Alur Pemesanan, Riwayat, Pembatalan & Ulasan Customer
 */
class CustomerOrderController extends Controller
{
    /**
     * Tampilkan Daftar Pesanan Saya (Dengan Filter Status)
     */
    public function index(Request $request): void
    {
        $customerId = (int)Auth::id();
        $allOrders = Order::getByCustomerId($customerId);

        $statusFilter = $request->input('status', 'all');

        $filteredOrders = match ($statusFilter) {
            'active'    => array_filter($allOrders, fn($o) => in_array($o->status, [Order::STATUS_PENDING, Order::STATUS_ACCEPTED, Order::STATUS_IN_PROGRESS])),
            'pending'   => array_filter($allOrders, fn($o) => $o->status === Order::STATUS_PENDING),
            'completed' => array_filter($allOrders, fn($o) => $o->status === Order::STATUS_COMPLETED),
            'cancelled' => array_filter($allOrders, fn($o) => $o->status === Order::STATUS_CANCELLED),
            default     => $allOrders
        };

        // Sort descending by id
        usort($filteredOrders, fn($a, $b) => (int)$b->id <=> (int)$a->id);

        $stats = [
            'total'     => count($allOrders),
            'active'    => count(array_filter($allOrders, fn($o) => in_array($o->status, [Order::STATUS_PENDING, Order::STATUS_ACCEPTED, Order::STATUS_IN_PROGRESS]))),
            'pending'   => count(array_filter($allOrders, fn($o) => $o->status === Order::STATUS_PENDING)),
            'completed' => count(array_filter($allOrders, fn($o) => $o->status === Order::STATUS_COMPLETED)),
            'cancelled' => count(array_filter($allOrders, fn($o) => $o->status === Order::STATUS_CANCELLED)),
        ];

        $this->view('customer/orders/index', [
            'pageTitle'    => 'Pesanan Saya — Terima Jasa',
            'currentUser'  => Auth::user(),
            'orders'       => $filteredOrders,
            'statusFilter' => $statusFilter,
            'stats'        => $stats
        ], 'dashboard');
    }

    /**
     * Tampilkan Form Pembuatan Pesanan Baru
     */
    public function create(Request $request): void
    {
        $services = Service::all();
        $selectedServiceId = (int)$request->input('service_id', 0);
        $selectedService = $selectedServiceId ? Service::find($selectedServiceId) : null;

        // Validasi dan proteksi manipulasi service_id
        if ($selectedServiceId > 0 && (!$selectedService || $selectedService->status !== Service::STATUS_ACTIVE)) {
            $selectedService = null;
            $selectedServiceId = 0;
            $_SESSION['flash_error'] = 'Layanan jasa yang dipilih tidak ditemukan atau sedang tidak aktif.';
        }

        // Tangkap & validasi technician_id yang direkomendasikan AI (Proteksi Manipulasi ID)
        $selectedTechId = (int)$request->input('technician_id', 0);
        $selectedTech = null;

        if ($selectedTechId > 0) {
            $techUser = User::find($selectedTechId);
            $techProfile = TechnicianProfile::findByUserId($selectedTechId);

            // Validasi ketat: Harus role teknisi & status terverifikasi
            if ($techUser && $techUser->isTeknisi() && $techProfile && $techProfile->verification_status === TechnicianProfile::VERIFICATION_VERIFIED) {
                $ratingStats = Review::getRatingStatsForTechnician($selectedTechId);
                $selectedTech = [
                    'id'                  => (int)$techProfile->id,
                    'user_id'             => (int)$techUser->id,
                    'name'                => $techUser->name,
                    'expertise'           => $techProfile->expertise,
                    'experience'          => $techProfile->experience,
                    'location'            => $techProfile->location,
                    'availability'        => $techProfile->availability,
                    'rating_label'        => $ratingStats['label'],
                ];
            } else {
                // Manipulasi ID dicegah: hapus ID tidak valid
                $selectedTechId = 0;
            }
        }

        // Ambil daftar seluruh teknisi terverifikasi untuk dropdown pilihan teknisi
        $verifiedTechs = [];
        foreach (TechnicianProfile::all() as $tp) {
            if ($tp->verification_status === TechnicianProfile::VERIFICATION_VERIFIED) {
                $u = $tp->user() ?? User::find((int)$tp->user_id);
                if ($u && $u->isTeknisi()) {
                    $rStats = Review::getRatingStatsForTechnician((int)$u->id);
                    $verifiedTechs[] = [
                        'user_id'      => (int)$u->id,
                        'name'         => $u->name,
                        'expertise'    => $tp->expertise,
                        'location'     => $tp->location,
                        'availability' => $tp->availability,
                        'rating_label' => $rStats['label']
                    ];
                }
            }
        }

        $userSession = Auth::user();

        $this->view('customer/orders/create', [
            'pageTitle'         => 'Pesan Jasa Servis — Terima Jasa',
            'currentUser'       => $userSession,
            'services'          => $services,
            'selectedServiceId' => $selectedServiceId,
            'selectedService'   => $selectedService,
            'selectedTechId'    => $selectedTechId,
            'selectedTech'      => $selectedTech,
            'verifiedTechs'     => $verifiedTechs
        ], 'dashboard');

        // Bersihkan flash errors dan old input setelah render
        unset($_SESSION['errors'], $_SESSION['old']);
    }

    /**
     * Simpan Pesanan Baru
     */
    public function store(Request $request): void
    {
        $serviceId    = (int)$request->input('service_id');
        $technicianId = (int)$request->input('technician_id', 0);
        $description  = trim((string)$request->input('description', ''));
        $location     = trim((string)$request->input('location', ''));
        $schedule     = trim((string)$request->input('schedule', ''));

        // Server-Side Security Validation
        $errors = [];

        // 1. Validasi Keamanan service_id
        if (empty($serviceId)) {
            $errors['service_id'][] = 'Silakan pilih jenis jasa servis.';
        } else {
            $service = Service::find($serviceId);
            if (!$service || $service->status !== Service::STATUS_ACTIVE) {
                $errors['service_id'][] = 'Layanan jasa tidak valid atau sedang tidak aktif.';
            }
        }

        // 2. Validasi Keamanan technician_id (Mencegah Manipulasi Customer)
        $assignedTechId = null;
        if (!empty($technicianId)) {
            $techUser = User::find($technicianId);
            $techProfile = TechnicianProfile::findByUserId($technicianId);

            if (!$techUser || !$techUser->isTeknisi()) {
                $errors['technician_id'][] = 'ID teknisi tidak valid atau bukan mitra teknisi terdaftar.';
            } elseif (!$techProfile || $techProfile->verification_status !== TechnicianProfile::VERIFICATION_VERIFIED) {
                $errors['technician_id'][] = 'Teknisi yang dipilih belum berstatus terverifikasi.';
            } else {
                $assignedTechId = $technicianId;
            }
        }

        // 3. Validasi Deskripsi Masalah
        if (empty($description)) {
            $errors['description'][] = 'Deskripsi masalah wajib diisi.';
        } elseif (mb_strlen($description) < 5) {
            $errors['description'][] = 'Deskripsi masalah minimal 5 karakter.';
        }

        // 4. Validasi Lokasi Pengerjaan
        if (empty($location)) {
            $errors['location'][] = 'Lokasi / alamat pengerjaan wajib diisi.';
        } elseif (mb_strlen($location) < 5) {
            $errors['location'][] = 'Alamat pengerjaan minimal 5 karakter.';
        }

        // 5. Validasi Waktu Jadwal
        if (empty($schedule)) {
            $errors['schedule'][] = 'Jadwal waktu servis wajib ditentukan.';
        }

        // Upload Foto Masalah (Jika ada)
        $problemImagePath = null;
        if (!empty($_FILES['problem_image']['name']) && $_FILES['problem_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['problem_image'];
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
            $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($fileExt, $allowedExts)) {
                $errors['problem_image'][] = 'Format gambar tidak didukung. Gunakan JPG, PNG, atau WEBP.';
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $errors['problem_image'][] = 'Ukuran gambar maksimal 5 MB.';
            } else {
                $targetDir = ROOT_PATH . '/public/uploads/orders/';
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                $fileName = 'order_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
                $targetFilePath = $targetDir . $fileName;

                if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
                    $problemImagePath = 'uploads/orders/' . $fileName;
                }
            }
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $request->all();
            $_SESSION['flash_error'] = 'Mohon periksa formulir pesanan Anda. Data yang tidak valid tidak dapat diproses.';
            $redirectQuery = http_build_query(array_filter([
                'service_id'    => $serviceId,
                'technician_id' => $technicianId
            ]));
            $this->redirect('customer/orders/create?' . $redirectQuery);
            return;
        }

        // KEAMANAN KETAT: customer_id diambil langsung dari Auth Session, BUKAN dari form body
        $customerId = (int)Auth::id();

        // Jika teknisi belum dipilih, cari teknisi yang tersedia secara otomatis dari service
        if (!$assignedTechId) {
            $service = Service::find($serviceId);
            $availableTechs = $service ? $service->availableTechnicians() : [];
            if (!empty($availableTechs)) {
                $assignedTechId = (int)$availableTechs[0]->user_id;
            } else {
                $assignedTechId = 2; // Default verified technician fallback
            }
        }

        // Simpan Order Baru (Status Awal Selalu PENDING)
        $orderData = [
            'customer_id'   => $customerId,
            'technician_id' => $assignedTechId,
            'service_id'    => $serviceId,
            'description'   => $description,
            'location'      => $location,
            'schedule'      => date('Y-m-d H:i:s', strtotime($schedule)),
            'problem_image' => $problemImagePath,
            'status'        => Order::STATUS_PENDING,
            'created_at'    => date('Y-m-d H:i:s')
        ];

        $newOrder = Order::create($orderData);

        // Fallback jika database offline / local instance
        if (!$newOrder) {
            $generatedId = rand(100, 999);
            $orderData['id'] = $generatedId;
            $newOrder = new Order($orderData);
            $newOrder->id = $generatedId;
            
            // Simpan di mock store
            $mockList = &Order::getMockOrders();
            $mockList[] = $newOrder;
        }

        // Trigger Real-time Event Notification: Customer & Teknisi
        NotificationService::notifyOrderCreated($newOrder);

        $_SESSION['flash_success'] = 'Pesanan servis Anda berhasil dibuat! Tim teknisi akan segera mengonfirmasi jadwal.';
        $this->redirect('customer/orders/' . $newOrder->id);
    }

    /**
     * Tampilkan Detail Pesanan
     */
    public function show(Request $request, string $id): void
    {
        $order = Order::find((int)$id);

        if (!$order) {
            $_SESSION['flash_error'] = 'Pesanan dengan ID tersebut tidak ditemukan.';
            $this->redirect('customer/orders');
            return;
        }

        // KEAMANAN KETAT: Customer HANYA boleh mengakses pesanan miliknya sendiri
        if ((int)$order->customer_id !== (int)Auth::id()) {
            $_SESSION['flash_error'] = 'Akses Ditolak: Anda tidak memiliki izin untuk melihat detail pesanan ini.';
            $this->redirect('customer/orders');
            return;
        }

        $service    = $order->service();
        $technician = $order->technician();
        $category   = $service ? $service->category() : null;
        $review     = $order->review();

        $this->view('customer/orders/show', [
            'pageTitle'   => 'Detail Pesanan #TJ-' . str_pad($order->id, 5, '0', STR_PAD_LEFT) . ' — Terima Jasa',
            'currentUser' => Auth::user(),
            'order'       => $order,
            'service'     => $service,
            'technician'  => $technician,
            'category'    => $category,
            'review'      => $review
        ], 'dashboard');

        // Bersihkan flash errors setelah render
        unset($_SESSION['errors'], $_SESSION['old']);
    }

    /**
     * Batalkan Pesanan (Hanya Diizinkan jika Status Masih 'pending')
     */
    public function cancel(Request $request, string $id): void
    {
        $order = Order::find((int)$id);

        if (!$order) {
            $_SESSION['flash_error'] = 'Pesanan tidak ditemukan.';
            $this->redirect('customer/orders');
            return;
        }

        // KEAMANAN KETAT: Otorisasi Kepemilikan Pesanan
        if ((int)$order->customer_id !== (int)Auth::id()) {
            $_SESSION['flash_error'] = 'Akses Ditolak: Anda tidak memiliki izin membatalkan pesanan milik customer lain.';
            $this->redirect('customer/orders');
            return;
        }

        // Validasi Status: Hanya status pending yang dapat dibatalkan
        if (!$order->canCancel()) {
            $_SESSION['flash_error'] = "Pesanan tidak dapat dibatalkan karena saat ini sedang dalam status [" . ucfirst($order->status) . "].";
            $this->redirect('customer/orders/' . $id);
            return;
        }

        $order->status = Order::STATUS_CANCELLED;
        $order->save();

        // Trigger Real-time Event Notification: Order Cancelled
        NotificationService::notifyOrderCancelled($order, 'customer');

        $_SESSION['flash_success'] = 'Pesanan #TJ-' . str_pad($order->id, 5, '0', STR_PAD_LEFT) . ' berhasil dibatalkan.';
        $this->redirect('customer/orders/' . $id);
    }

    /**
     * Berikan Rating & Ulasan untuk Pesanan yang Selesai
     */
    public function storeReview(Request $request, string $id): void
    {
        $order = Order::find((int)$id);

        if (!$order) {
            $_SESSION['flash_error'] = 'Pesanan tidak ditemukan.';
            $this->redirect('customer/orders');
            return;
        }

        // KEAMANAN KETAT: Otorisasi Kepemilikan Pesanan
        if ((int)$order->customer_id !== (int)Auth::id()) {
            $_SESSION['flash_error'] = 'Akses Ditolak: Anda tidak dapat memberikan review untuk pesanan customer lain.';
            $this->redirect('customer/orders');
            return;
        }

        // Validasi Status: Hanya pesanan completed yang dapat direview
        if (!$order->isCompleted()) {
            $_SESSION['flash_error'] = 'Ulasan hanya dapat diberikan untuk pesanan yang telah berstatus Selesai (Completed).';
            $this->redirect('customer/orders/' . $id);
            return;
        }

        // Validasi: 1 Order Hanya Boleh Direview Sekali
        if ($order->review() !== null) {
            $_SESSION['flash_error'] = 'Anda sudah pernah memberikan ulasan untuk pesanan ini. Satu pesanan hanya dapat diulas satu kali.';
            $this->redirect('customer/orders/' . $id);
            return;
        }

        $rating = (int)$request->input('rating', 5);
        $comment = trim((string)$request->input('comment', ''));

        if ($rating < 1 || $rating > 5) {
            $_SESSION['flash_error'] = 'Nilai rating bintang harus antara 1 sampai 5.';
            $this->redirect('customer/orders/' . $id);
            return;
        }

        // Simpan Review Baru
        $techId = (int)($order->technician_id ?? 2);

        $newReview = Review::create([
            'order_id'      => (int)$order->id,
            'customer_id'   => (int)Auth::id(),
            'technician_id' => $techId,
            'rating'        => $rating,
            'comment'       => $comment,
            'created_at'    => date('Y-m-d H:i:s')
        ]);

        // Fallback untuk mock mode
        if (!$newReview) {
            $mockRev = new Review([
                'id'            => rand(100, 999),
                'order_id'      => (int)$order->id,
                'customer_id'   => (int)Auth::id(),
                'technician_id' => $techId,
                'rating'        => $rating,
                'comment'       => $comment,
                'created_at'    => date('Y-m-d H:i:s')
            ]);
            $mockRev->id = rand(100, 999);
            $mockList = &Review::getMockReviews();
            $mockList[] = $mockRev;
        }

        $_SESSION['flash_success'] = "Terima kasih! Ulasan bintang {$rating} Anda telah berhasil dipublikasikan.";
        $this->redirect('customer/orders/' . $id);
    }
}
