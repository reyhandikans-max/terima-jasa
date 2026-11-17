<?php
/**
 * TEST: Out-of-Scope Guard — Chatbot harus menolak pertanyaan di luar database Terima Jasa
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', ROOT_PATH . '/public');

require_once APP_PATH . '/Helpers/helpers.php';

// Register Autoloader
spl_autoload_register(function ($class) {
    $prefix  = 'App\\';
    $baseDir = APP_PATH . '/';
    $len     = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require $file;
});

use App\Services\ChatbotService;

$service = new ChatbotService();

echo "========================================================\n";
echo "   TERIMA JASA — OUT-OF-SCOPE GUARD TEST SUITE         \n";
echo "========================================================\n\n";

$passed = 0;
$failed = 0;

function assertOutOfScope(ChatbotService $svc, string $msg, bool $expected): void {
    global $passed, $failed;
    $actual = $svc->isOutOfScope($msg);
    $status = ($actual === $expected) ? '[PASS]' : '[FAIL]';
    if ($actual === $expected) {
        $passed++;
    } else {
        $failed++;
    }
    $label = $expected ? 'HARUS DITOLAK' : 'HARUS DIJAWAB';
    echo " {$status} [{$label}] \"{$msg}\"\n";
}

echo "--- 1. Pertanyaan PEMROGRAMAN harus DITOLAK ---\n";
assertOutOfScope($service, 'buatkan kode python untuk menampilkan nama saya', true);
assertOutOfScope($service, 'contoh script python sederhana', true);
assertOutOfScope($service, 'cara membuat fungsi di JavaScript', true);
assertOutOfScope($service, 'jelaskan tentang algoritma sorting', true);
assertOutOfScope($service, 'apa itu machine learning', true);
assertOutOfScope($service, 'tulis kode HTML CSS untuk website', true);
assertOutOfScope($service, 'bagaimana cara install Laravel', true);
assertOutOfScope($service, 'def main python print name', true);

echo "\n--- 2. Pertanyaan LUAR TOPIK lainnya harus DITOLAK ---\n";
assertOutOfScope($service, 'resep nasi goreng spesial', true);
assertOutOfScope($service, 'cara membuat kue brownies', true);
assertOutOfScope($service, 'rumus matematika integral', true);
assertOutOfScope($service, 'soal ujian fisika SMA', true);
assertOutOfScope($service, 'siapa presiden Indonesia saat ini', true);
assertOutOfScope($service, 'berita hari ini', true);

echo "\n--- 3. Pertanyaan SEPUTAR PLATFORM harus DIJAWAB ---\n";
assertOutOfScope($service, 'AC saya bocor', false);
assertOutOfScope($service, 'harga cuci AC berapa', false);
assertOutOfScope($service, 'laptop saya mati total', false);
assertOutOfScope($service, 'listrik sering anjlok MCB', false);
assertOutOfScope($service, 'pipa mampet', false);
assertOutOfScope($service, 'teknisi AC di Jakarta', false);
assertOutOfScope($service, 'cara pesan jasa di terima jasa', false);
assertOutOfScope($service, 'status pesanan saya', false);
assertOutOfScope($service, 'berapa biaya servis kulkas', false);
assertOutOfScope($service, 'TV saya rusak tidak nyala', false);

echo "\n--- 4. Uji Respon Penolakan Out-of-Scope ---\n";
// Simulasi handleChat untuk pertanyaan Python
$messages = [['role' => 'user', 'content' => 'buatkan kode python untuk hello world']];
$userCtx  = ['user' => ['id' => 1, 'name' => 'Test User', 'role' => 'customer']];

$response = $service->handleChat($messages, $userCtx);
if ($response['success'] === true && $response['source'] === 'scope_guard') {
    echo " [PASS] handleChat mengembalikan source='scope_guard' untuk pertanyaan Python\n";
    $passed++;
} else {
    echo " [FAIL] handleChat seharusnya mengembalikan source='scope_guard'\n";
    echo "        Actual source: " . ($response['source'] ?? 'N/A') . "\n";
    $failed++;
}

if (str_contains($response['reply'], '❌') || str_contains($response['reply'], 'Luar Cakupan') || str_contains($response['reply'], 'luar cakupan')) {
    echo " [PASS] Reply mengandung pesan penolakan yang sesuai\n";
    $passed++;
} else {
    echo " [FAIL] Reply tidak mengandung pesan penolakan yang diharapkan\n";
    echo "        Actual reply: " . substr($response['reply'], 0, 100) . "...\n";
    $failed++;
}

// Pastikan pertanyaan AC tetap dijawab normal (tidak ter-block)
$messages2 = [['role' => 'user', 'content' => 'AC saya bocor, berapa biaya servisnya?']];
$response2 = $service->handleChat($messages2, $userCtx);
if ($response2['success'] === true && $response2['source'] !== 'scope_guard') {
    echo " [PASS] Pertanyaan AC tetap diproses normal (source: {$response2['source']})\n";
    $passed++;
} else {
    echo " [FAIL] Pertanyaan AC seharusnya tidak di-block oleh scope_guard\n";
    $failed++;
}

echo "\n--- 5. Uji pada AIService (Customer Diagnosa) ---\n";
use App\Services\AIService;
$aiService = new AIService();

// Uji keluhan python di AIService
$aiRes = $aiService->analyzeComplaint('buatkan kode python sederhana yang menampilkan nama saya');
if (($aiRes['is_out_of_scope'] ?? false) === true && str_contains($aiRes['chat_formatted_text'] ?? '', '❌')) {
    echo " [PASS] AIService mendeteksi pertanyaan python sebagai is_out_of_scope = true\n";
    $passed++;
} else {
    echo " [FAIL] AIService seharusnya menolak pertanyaan python\n";
    $failed++;
}

// Uji keluhan valid di AIService
$aiResValid = $aiService->analyzeComplaint('AC kamar saya netes air dan tidak dingin');
if (($aiResValid['is_out_of_scope'] ?? false) === false && ($aiResValid['success'] ?? false) === true) {
    echo " [PASS] AIService memproses keluhan AC valid secara normal\n";
    $passed++;
} else {
    echo " [FAIL] AIService gagal memproses keluhan AC normal\n";
    $failed++;
}

echo "\n--- 6. Uji pada ChatbotController::generateRoleFallbackReply ---\n";
use App\Controllers\ChatbotController;
$cbController = new ChatbotController();
$fallbackPython = $cbController->generateRoleFallbackReply('admin', 'buatkan kode python print nama', 'Admin');
if (str_contains($fallbackPython, '❌') && str_contains($fallbackPython, 'Luar Cakupan')) {
    echo " [PASS] Fallback reply menolak permintaan kode python dengan pesan penolakan sopan\n";
    $passed++;
} else {
    echo " [FAIL] Fallback reply seharusnya menolak permintaan kode python\n";
    $failed++;
}

$fallbackValid = $cbController->generateRoleFallbackReply('admin', 'berapa total teknisi', 'Admin');
if (!str_contains($fallbackValid, '❌') && str_contains($fallbackValid, 'teknisi')) {
    echo " [PASS] Fallback reply memproses pertanyaan total teknisi secara normal dari database\n";
    $passed++;
} else {
    echo " [FAIL] Fallback reply gagal menjawab pertanyaan total teknisi\n";
    $failed++;
}

echo "\n========================================================\n";
echo ">>> HASIL PENGUJIAN OUT-OF-SCOPE GUARD:\n";
echo "    Passed: {$passed}\n";
echo "    Failed: {$failed}\n";
echo "========================================================\n";
if ($failed === 0) {
    echo ">>> SEMUA TEST LULUS! Chatbot akan menolak pertanyaan di luar database.\n";
} else {
    echo ">>> ADA {$failed} TEST GAGAL! Perlu perbaikan lebih lanjut.\n";
}
echo "\n";
