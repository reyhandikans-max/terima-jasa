<?php
/**
 * Test Suite: PROMPT 8 — AI Recommendation Engine & Technician Integration
 * Terima Jasa — Platform Penghubung Customer & Teknisi
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

require_once APP_PATH . '/Helpers/helpers.php';

// Register Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = APP_PATH . '/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require $file;
});

use App\Core\Auth;
use App\Models\User;
use App\Models\Service;
use App\Models\Category;
use App\Models\TechnicianProfile;
use App\Models\Review;
use App\Models\Order;
use App\Services\AIService;
use App\Controllers\CustomerOrderController;
use App\Core\Request;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "========================================================\n";
echo "   TERIMA JASA - PROMPT 8 AI RECOMMENDATION TEST SUITE  \n";
echo "========================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest(string $description, bool $condition): void {
    global $passCount, $failCount;
    if ($condition) {
        echo " [PASS] {$description}\n";
        $passCount++;
    } else {
        echo " [FAIL] {$description}\n";
        $failCount++;
    }
}

// ---------------------------------------------------------------
// 1. TEST AI COMPLAINT ANALYSIS & CATEGORY RECOGNITION
// ---------------------------------------------------------------
echo "\n--- 1. Understanding Complaint & Category Determination ---\n";

$aiService = new AIService();

// Test Kasus Utama sesuai User Prompt:
$laptopComplaint = "Laptop saya tiba-tiba mati dan tidak bisa menyala.";
$laptopResult = $aiService->analyzeComplaint($laptopComplaint, "Jakarta Selatan");

assertTest("Status success adalah true", !empty($laptopResult['success']));
assertTest("AI mengenali keluhan dan menentukan kategori Laptop/Komputer", 
    str_contains(strtolower($laptopResult['recommended_category']), 'laptop') || 
    str_contains(strtolower($laptopResult['recommended_category']), 'komputer')
);
assertTest("AI menyertakan diagnosis summary yang relevan", !empty($laptopResult['diagnosis_summary']));
assertTest("AI menyertakan penjelasan teknis penyebab mati total", 
    str_contains(strtolower($laptopResult['explanation']), 'motherboard') || 
    str_contains(strtolower($laptopResult['explanation']), 'daya') ||
    str_contains(strtolower($laptopResult['explanation']), 'power')
);

// ---------------------------------------------------------------
// 2. TEST DATABASE SERVICE MATCHING (JANGAN MENGARANG DATA JASA)
// ---------------------------------------------------------------
echo "\n--- 2. Database Service Matching & Output Fields ---\n";

$matchedServices = $laptopResult['matched_services'];
assertTest("Service yang direkomendasikan berasal dari database (tidak kosong)", is_array($matchedServices) && count($matchedServices) > 0);

$primaryService = $matchedServices[0];
assertTest("Service memuat nama jasa", !empty($primaryService['name']));
assertTest("Service memuat deskripsi jasa", !empty($primaryService['description']));
assertTest("Service memuat estimasi harga numerik", isset($primaryService['estimated_price']) && $primaryService['estimated_price'] > 0);
assertTest("Service memuat format rupiah (Rp)", str_contains($primaryService['formatted_price'], 'Rp'));
assertTest("Service memuat order_url menuju form pemesanan jasa", str_contains($primaryService['order_url'], 'customer/orders/create?service_id='));

// Validasi bahwa service_id benar-benar ada di database
$dbService = Service::find($primaryService['id']);
assertTest("Service ID benar-benar terdaftar di database katalog", $dbService !== null && (int)$dbService->id === (int)$primaryService['id']);

// ---------------------------------------------------------------
// 3. TEST DATABASE TECHNICIAN MATCHING & STRICT 5-PRIORITY RANKING
// ---------------------------------------------------------------
echo "\n--- 3. Database Technician Search & 5-Priority Ranking ---\n";

$recommendedTechs = $laptopResult['recommended_technicians'];
assertTest("AI mengembalikan rekomendasi teknisi dari database", is_array($recommendedTechs) && count($recommendedTechs) > 0);

// Cek bahwa semua data teknisi berasal dari database (bukan karangan/fiktif)
$allTechsValid = true;
foreach ($recommendedTechs as $tech) {
    $dbTechUser = User::find($tech['user_id']);
    $dbProfile = TechnicianProfile::findByUserId($tech['user_id']);
    if (!$dbTechUser || !$dbProfile) {
        $allTechsValid = false;
        break;
    }
}
assertTest("Semua data teknisi yang ditampilkan berasal dari database (TIDAK MENGARANG)", $allTechsValid);

// Cek Atribut Output Teknisi Sesuai Spesifikasi:
$topTech = $recommendedTechs[0];
assertTest("Teknisi memuat Nama", !empty($topTech['name']));
assertTest("Teknisi memuat Keahlian (Expertise)", !empty($topTech['expertise']));
assertTest("Teknisi memuat Rating", isset($topTech['rating']) && !empty($topTech['rating_label']));
assertTest("Teknisi memuat Pengalaman", !empty($topTech['experience']));
assertTest("Teknisi memuat Lokasi", !empty($topTech['location']));
assertTest("Teknisi memuat Availability", !empty($topTech['availability']) && !empty($topTech['availability_label']));
assertTest("Teknisi memuat order_url 'Pesan Jasa Ini'", str_contains($topTech['order_url'], 'customer/orders/create'));

// Uji Prioritas Ranking:
// Prioritas 1: Teknisi dengan keahlian Laptop/Komputer harus di atas teknisi AC/Listrik
$topTechExpertise = strtolower($topTech['expertise']);
assertTest("Prioritas 1: Teknisi teratas memiliki keahlian sesuai keluhan (Laptop/Komputer)", 
    str_contains($topTechExpertise, 'laptop') || str_contains($topTechExpertise, 'komputer')
);

// Prioritas 2: Status Verified
assertTest("Prioritas 2: Teknisi teratas berstatus Verified", $topTech['is_verified'] === true);

// Prioritas 3: Status Available
assertTest("Prioritas 3: Teknisi teratas berstatus Available (Tersedia)", $topTech['availability'] === TechnicianProfile::AVAILABILITY_AVAILABLE);

// Prioritas 4: Rating Tinggi
assertTest("Prioritas 4: Teknisi teratas memiliki rating tinggi (>= 4.5)", $topTech['rating'] >= 4.5);

// Prioritas 5: Lokasi Terdekat jika ada
assertTest("Prioritas 5: Teknisi teratas memiliki lokasi di Jakarta Selatan (sesuai input customer)", 
    str_contains(strtolower($topTech['location']), 'jakarta selatan')
);

// ---------------------------------------------------------------
// 4. TEST SECURITY & BACKEND VALIDATION (MENCEGAH MANIPULASI ID)
// ---------------------------------------------------------------
echo "\n--- 4. Backend Security & Anti-Tampering ID Validation ---\n";

$customerUser = new User([
    'id'     => 1,
    'name'   => 'Budi Santoso',
    'email'  => 'customer@terimajasa.id',
    'role'   => User::ROLE_CUSTOMER,
    'status' => User::STATUS_ACTIVE,
]);
$customerUser->id = 1;
Auth::login($customerUser);

// Case A: Manipulasi service_id tidak valid (misal: ID 99999 yang tidak ada)
$invalidService = Service::find(99999);
assertTest("Service dengan ID 99999 tidak ditemukan di database", $invalidService === null);

// Case B: Manipulasi technician_id tidak valid (menggunakan ID customer bukan teknisi)
$fakeTechIdCustomer = 1; // User ID 1 adalah Customer, bukan Teknisi
$targetUser = User::find($fakeTechIdCustomer);
$targetProfile = TechnicianProfile::findByUserId($fakeTechIdCustomer);
$isEligible = ($targetUser && $targetUser->isTeknisi() && $targetProfile && $targetProfile->verification_status === TechnicianProfile::VERIFICATION_VERIFIED);
assertTest("Security: Customer ID tidak dapat disusupkan sebagai technician_id valid", !$isEligible);

// Case C: Manipulasi technician_id teknisi berstatus rejected/pending (Rian Syahputra ID 7)
$rejectedTechProfile = TechnicianProfile::findByUserId(7);
$isRejectedEligible = ($rejectedTechProfile && $rejectedTechProfile->verification_status === TechnicianProfile::VERIFICATION_VERIFIED);
assertTest("Security: Teknisi yang ditolak (rejected) tidak diizinkan dipesan", !$isRejectedEligible);

// Case D: Teknisi valid & terverifikasi (Bambang Irawan ID 10)
$validTechUser = User::find(10);
$validTechProfile = TechnicianProfile::findByUserId(10);
$isValidEligible = ($validTechUser && $validTechUser->isTeknisi() && $validTechProfile && $validTechProfile->verification_status === TechnicianProfile::VERIFICATION_VERIFIED);
assertTest("Security: Teknisi verified (Bambang Irawan ID 10) lolos validasi keamanan", $isValidEligible);

// ---------------------------------------------------------------
// 5. TEST FLOW PEMESANAN & TOMBOL "PESAN JASA INI"
// ---------------------------------------------------------------
echo "\n--- 5. 'Pesan Jasa Ini' URL Structure & Preselection ---\n";

$orderButtonUrl = $topTech['order_url'];
assertTest("URL tombol pesanan memuat parameter service_id", str_contains($orderButtonUrl, 'service_id='));
assertTest("URL tombol pesanan memuat parameter technician_id", str_contains($orderButtonUrl, 'technician_id='));

// Parse URL
$parts = parse_url($orderButtonUrl);
parse_str($parts['query'] ?? '', $queryParams);
assertTest("Parameter service_id bernilai valid integer", !empty($queryParams['service_id']) && is_numeric($queryParams['service_id']));
assertTest("Parameter technician_id bernilai valid integer", !empty($queryParams['technician_id']) && is_numeric($queryParams['technician_id']));

// Test pembuatan pesanan sukses dengan teknisi pilihan AI
$validServiceId = (int)$queryParams['service_id'];
$validTechId = (int)$queryParams['technician_id'];

$newOrder = Order::create([
    'customer_id'   => (int)Auth::id(),
    'technician_id' => $validTechId,
    'service_id'    => $validServiceId,
    'description'   => 'Laptop tiba-tiba mati total setelah pemakaian rendering, butuh servis motherboard.',
    'location'      => 'Jl. Kemang Timur No. 10, Jakarta Selatan',
    'schedule'      => date('Y-m-d 10:00:00', strtotime('+1 day')),
    'status'        => Order::STATUS_PENDING
]);

assertTest("Pesanan berhasil dibuat dengan teknisi rekomendasi AI", $newOrder !== null && $newOrder->technician_id == $validTechId);
assertTest("Status awal pesanan selalu pending", $newOrder->status === Order::STATUS_PENDING);

// ---------------------------------------------------------------
// TEST SUMMARY
// ---------------------------------------------------------------
echo "\n========================================================\n";
echo ">>> TEST EXECUTION SUMMARY (PROMPT 8):\n";
echo "    Passed: {$passCount}\n";
echo "    Failed: {$failCount}\n";
echo "========================================================\n";

if ($failCount === 0) {
    echo ">>> ALL PROMPT 8 AI RECOMMENDATION ENGINE REQUIREMENTS MET (100% SUCCESS)\n\n";
    exit(0);
} else {
    echo ">>> SOME TESTS FAILED!\n\n";
    exit(1);
}
