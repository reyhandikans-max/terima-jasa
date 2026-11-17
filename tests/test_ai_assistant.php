<?php
/**
 * Test Suite: AI Assistant Integration, Fallback, Security & Storage
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
use App\Models\AiChat;
use App\Models\Category;
use App\Models\Service;
use App\Services\AIService;

echo "========================================================\n";
echo "   TERIMA JASA - AI ASSISTANT MODULE TEST SUITE (P7)   \n";
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
// 1. TEST USER AUTHORIZATION (Customer Only)
// ---------------------------------------------------------------
echo "\n--- 1. User Authorization & Role Access ---\n";

$customerUser = new User([
    'id'     => 1,
    'name'   => 'Budi Santoso',
    'email'  => 'customer@terimajasa.id',
    'role'   => User::ROLE_CUSTOMER,
    'status' => User::STATUS_ACTIVE,
    'phone'  => '081234567890'
]);
$customerUser->id = 1;

Auth::login($customerUser);
assertTest('User customer berhasil login ke sistem', Auth::check() && Auth::role() === User::ROLE_CUSTOMER);
assertTest('Customer user model isCustomer() bernilai true', $customerUser->isCustomer());
assertTest('Customer role diizinkan mengakses area customer', Auth::role() === 'customer');

$teknisiUser = new User([
    'id'     => 2,
    'name'   => 'Hendra Wijaya',
    'email'  => 'teknisi@terimajasa.id',
    'role'   => User::ROLE_TEKNISI,
    'status' => User::STATUS_ACTIVE,
]);
assertTest('Role teknisi bukan customer (Role boundary valid)', $teknisiUser->role !== User::ROLE_CUSTOMER);

// ---------------------------------------------------------------
// 2. TEST AI CHAT ANALYSIS & STRUCTURED INFORMATION
// ---------------------------------------------------------------
echo "\n--- 2. AI Analysis & Structured Response Format ---\n";

$aiService = new AIService();

// Test Kasus AC (Sesuai contoh user prompt)
$acComplaint = "Saya punya AC yang tidak dingin dan mengeluarkan air.";
$acResult = $aiService->analyzeComplaint($acComplaint);

assertTest('AI Response mengembalikan status success = true', !empty($acResult['success']));
assertTest('AI Response memuat diagnosis_summary', !empty($acResult['diagnosis_summary']));
assertTest('AI Response memuat recommended_category (Servis AC)', str_contains(strtolower($acResult['recommended_category']), 'ac'));
assertTest('AI Response memuat recommended_service', !empty($acResult['recommended_service']));
assertTest('AI Response memuat explanation', !empty($acResult['explanation']));
assertTest('AI Response memuat urgency (Rendah/Sedang/Tinggi/Darurat)', in_array($acResult['urgency'], ['Rendah', 'Sedang', 'Tinggi', 'Darurat']));
assertTest('AI Response memuat disclaimer keselamatan', !empty($acResult['disclaimer']));
assertTest('Backend berhasil menyertakan matched_services dari database', is_array($acResult['matched_services']) && count($acResult['matched_services']) > 0);
assertTest('Tombol URL "Cari Jasa yang Direkomendasikan" tersedia', !empty($acResult['search_url']));

// ---------------------------------------------------------------
// 3. TEST MEDICAL SAFETY GUARD (Etika & Kebijakan Keamanan)
// ---------------------------------------------------------------
echo "\n--- 3. Safety Guard: Penolakan Klaim Medis ---\n";

$medicalInquiry = "Dokter, saya merasa demam tinggi, sakit kepala parah dan batuk berdarah, apa obatnya?";
$medResult = $aiService->analyzeComplaint($medicalInquiry);

assertTest('AI mendeteksi pertanyaan medis (is_medical = true)', !empty($medResult['is_medical']));
assertTest('AI menolak memberikan diagnosa/resep medis', str_contains($medResult['explanation'], 'tidak berwenang') || str_contains($medResult['explanation'], 'medis'));
assertTest('AI menyarankan konsultasi ke dokter/fasilitas kesehatan', str_contains($medResult['explanation'], 'dokter') || str_contains($medResult['explanation'], 'kesehatan'));

// ---------------------------------------------------------------
// 4. TEST ERROR API & GRACEFUL FALLBACK
// ---------------------------------------------------------------
echo "\n--- 4. API Error Handling & Graceful Local Fallback ---\n";

// Simulasi AI Service dengan API Key palsu / rusak untuk menguji ketahanan error
$brokenAiService = new AIService('groq', 'gsk_invalid_test_key_xyz', 'non-existent-model');
$brokenResult = $brokenAiService->analyzeComplaint("MCB listrik rumah sering anjlok saat menyalakan kulkas");

assertTest('Sistem TIDAK CRASH saat API Key error / offline', !empty($brokenResult['success']));
assertTest('Sistem beralih ke local fallback engine secara aman', $brokenResult['source'] === 'local_fallback');
assertTest('Fallback lokal mendeteksi kategori Kelistrikan Rumah', str_contains(strtolower($brokenResult['recommended_category']), 'listrik'));
assertTest('Fallback lokal menyertakan rekomendasi jasa kelistrikan', !empty($brokenResult['recommended_service']));
assertTest('Fallback lokal tetap menyertakan tombol cari jasa', !empty($brokenResult['search_url']));

// ---------------------------------------------------------------
// 5. TEST PENYIMPANAN CHAT KE DATABASE (ai_chats)
// ---------------------------------------------------------------
echo "\n--- 5. Chat History Database Storage (ai_chats) ---\n";

$customerId = (int)$customerUser->id;
$testQuestion = "Pipa air wastafel di dapur saya bocor dan mampet.";
$testAnswer   = "Berdasarkan keluhan Anda, kemungkinan terdapat sumbatan lemak pada pipa leher angsa wastafel. Disarankan memanggil teknisi plumbing.";

$recordedChat = AiChat::recordChat($customerId, $testQuestion, $testAnswer);

assertTest('Chat berhasil disimpan ke tabel ai_chats', !empty($recordedChat->id));
assertTest('Record menyimpan customer_id yang sesuai', (int)$recordedChat->customer_id === $customerId);
assertTest('Record menyimpan question yang sesuai', $recordedChat->question === $testQuestion);
assertTest('Record menyimpan answer yang sesuai', $recordedChat->answer === $testAnswer);
assertTest('Record menyimpan created_at timestamp', !empty($recordedChat->created_at));

// Uji Pengambilan Riwayat Chat
$history = AiChat::getByCustomerId($customerId);
assertTest('AiChat::getByCustomerId() berhasil mengambil riwayat', is_array($history) && count($history) > 0);

$lastChat = end($history);
assertTest('Data chat terakhir cocok dengan yang baru disimpan', $lastChat->question === $testQuestion);

// ---------------------------------------------------------------
// 6. TEST ENVIRONMENT VARIABLE SECURITY
// ---------------------------------------------------------------
echo "\n--- 6. Environment Variable Security Verification ---\n";

assertTest('Fungsi helper env() aktif', function_exists('env'));
assertTest('.env template atau file .env ada di server', file_exists(ROOT_PATH . '/.env') || file_exists(ROOT_PATH . '/.env.example'));
assertTest('API Key tidak terekspos di frontend template', !str_contains(file_get_contents(APP_PATH . '/Views/customer/ai_assistant/index.php'), 'gsk_'));

// ---------------------------------------------------------------
// TEST SUMMARY
// ---------------------------------------------------------------
echo "\n========================================================\n";
echo ">>> TEST EXECUTION SUMMARY:\n";
echo "    Passed: {$passCount}\n";
echo "    Failed: {$failCount}\n";
echo "========================================================\n";

if ($failCount === 0) {
    echo ">>> ALL PROMPT 7 AI ASSISTANT REQUIREMENTS MET (100% SUCCESS)\n\n";
    exit(0);
} else {
    echo ">>> SOME TESTS FAILED!\n\n";
    exit(1);
}
