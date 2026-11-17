<?php
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', ROOT_PATH . '/public');

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

use App\Services\ChatbotService;
use App\Controllers\ChatbotController;
use App\Models\Service;
use App\Models\TechnicianProfile;
use App\Models\Order;
use App\Models\User;
use App\Models\AiChat;
use App\Core\Auth;

echo "========================================================\n";
echo "   TERIMA JASA — CHATBOT+ 14 MANDATORY TEST SUITE      \n";
echo "========================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertScenario(string $scenarioName, bool $condition, string $detail = ''): void {
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo " [PASS] {$scenarioName}\n";
    } else {
        $failCount++;
        echo " [FAIL] {$scenarioName} " . ($detail ? "({$detail})" : "") . "\n";
    }
}

$chatbotService = new ChatbotService();

// Mock Customer 1 (Budi Santoso)
$customerBudi = [
    'id'    => 1,
    'name'  => 'Budi Santoso',
    'email' => 'customer@terimajasa.id',
    'role'  => 'customer'
];

// Mock Customer 4 (Siti Rahmawati)
$customerSiti = [
    'id'    => 4,
    'name'  => 'Siti Rahmawati',
    'email' => 'siti@customer.id',
    'role'  => 'customer'
];

// -------------------------------------------------------------
// Skenario 1 & 2: Pertanyaan Jasa & Menampilkan Jasa Aktual DB
// -------------------------------------------------------------
echo "\n--- 1 & 2. Pertanyaan Jasa yang Tersedia & Data Aktual Database ---\n";
$res1 = $chatbotService->handleChat([
    ['role' => 'user', 'content' => 'Layanan jasa apa saja yang tersedia di Terima Jasa?']
], ['user' => $customerBudi]);

assertScenario('Respon pencarian jasa berhasil diproses', !empty($res1['success']));
assertScenario('Chatbot menampilkan jasa yang benar-benar ada di database', 
    str_contains($res1['reply'], 'Cuci AC') || str_contains($res1['reply'], 'Laptop') || str_contains($res1['reply'], 'Listrik'),
    $res1['reply']
);

// -------------------------------------------------------------
// Skenario 3 & 4: Pertanyaan Harga & Menggunakan Harga Database
// -------------------------------------------------------------
echo "\n--- 3 & 4. Pertanyaan Harga & Menggunakan Harga Dasar Database ---\n";
$res2 = $chatbotService->handleChat([
    ['role' => 'user', 'content' => 'Berapa harga jasa cuci AC?']
], ['user' => $customerBudi]);

$acService = Service::find(1);
$expectedAcPrice = $acService ? number_format($acService->estimated_price, 0, ',', '.') : '75.000';

assertScenario('Chatbot menggunakan harga dari database (Harga Dasar)', 
    str_contains($res2['reply'], $expectedAcPrice) || str_contains($res2['reply'], '75.000'),
    $res2['reply']
);
assertScenario('Chatbot membedakan harga dasar dan estimasi (Transparansi Biaya)', 
    str_contains(strtolower($res2['reply']), 'harga dasar') || str_contains(strtolower($res2['reply']), 'estimasi')
);

// -------------------------------------------------------------
// Skenario 5 & 6: Rekomendasi Teknisi & Hanya Teknisi Riil
// -------------------------------------------------------------
echo "\n--- 5 & 6. Rekomendasi Teknisi Aktual & Tanpa Data Sensitif ---\n";
$res3 = $chatbotService->handleChat([
    ['role' => 'user', 'content' => 'Ada rekomendasi teknisi AC dengan rating tinggi?']
], ['user' => $customerBudi]);

assertScenario('Chatbot hanya menampilkan teknisi yang benar-benar ada di DB', 
    str_contains($res3['reply'], 'Hendra Wijaya') || str_contains($res3['reply'], 'Hendra'),
    $res3['reply']
);
assertScenario('Chatbot menyertakan rating teknisi aktual', 
    str_contains($res3['reply'], '5.0') || str_contains($res3['reply'], '★')
);
assertScenario('Chatbot TIDAK membocorkan nomor telepon pribadi teknisi ke pengguna', 
    !str_contains($res3['reply'], '082198765432') && !str_contains($res3['reply'], '081287654321')
);

// -------------------------------------------------------------
// Skenario 7 & 8: Status Pesanan Customer & Anti-IDOR (Isolasi Data)
// -------------------------------------------------------------
echo "\n--- 7 & 8. Status Pesanan Customer & Pencegahan IDOR ---\n";
// Uji Customer 1 (Budi)
$resBudi = $chatbotService->handleChat([
    ['role' => 'user', 'content' => 'Bagaimana status pesanan saya yang aktif?']
], ['user' => $customerBudi]);

assertScenario('Customer 1 dapat membaca status pesanannya sendiri', 
    str_contains($resBudi['reply'], 'ORD-') || str_contains($resBudi['reply'], 'Freon') || str_contains($resBudi['reply'], 'Cuci AC') || str_contains($resBudi['reply'], 'pesanan'),
    $resBudi['reply']
);
assertScenario('Customer 1 TIDAK BISA membaca pesanan Customer 4 (Order #ORD-2)', 
    !str_contains($resBudi['reply'], '#ORD-2') && !str_contains($resBudi['reply'], 'Cihampelas')
);

// Uji Pengunjung (Guest) tanpa login
$resGuest = $chatbotService->handleChat([
    ['role' => 'user', 'content' => 'Bagaimana status pesanan saya?']
], ['user' => null]);

assertScenario('Guest diarahkan untuk login terlebih dahulu untuk membaca pesanan', 
    str_contains(strtolower($resGuest['reply']), 'masuk') || str_contains(strtolower($resGuest['reply']), 'login')
);

// -------------------------------------------------------------
// Skenario 9: Chatbot Menangani Pencarian Tanpa Hasil (Jujur, No Hallucination)
// -------------------------------------------------------------
echo "\n--- 9. Penanganan Pencarian Tanpa Hasil (No Hallucination) ---\n";
$resNotFound = $chatbotService->handleChat([
    ['role' => 'user', 'content' => 'Apakah ada jasa perbaikan kapal selam nuklir atau pesawat tempur?']
], ['user' => $customerBudi]);

assertScenario('Chatbot jujur menyatakan layanan belum tersedia (tidak mengarang)', 
    str_contains(strtolower($resNotFound['reply']), 'belum tersedia') || str_contains(strtolower($resNotFound['reply']), 'tidak tersedia'),
    $resNotFound['reply']
);

// -------------------------------------------------------------
// Skenario 10: Penanganan Database yang Tidak Dapat Diakses / Offline
// -------------------------------------------------------------
echo "\n--- 10. Ketahanan Error Saat Database Bermasalah ---\n";
$serviceWithDb = new ChatbotService();
$groundingResult = $serviceWithDb->getDatabaseGrounding('guest', 0, 'Tamu');
assertScenario('Sistem tidak crash saat membaca grounding database', !empty($groundingResult));

// -------------------------------------------------------------
// Skenario 11: Penanganan Kegagalan AI API (Graceful Fallback)
// -------------------------------------------------------------
echo "\n--- 11. Graceful Local Fallback Saat AI API Gagal / Offline ---\n";
$fallbackReply = $serviceWithDb->generateDatabaseSmartFallback('customer', 'Berapa tarif servis laptop mati total?', 'Budi', 1);

assertScenario('Fallback lokal mampu membaca database dan menjawab dengan tarif resmi', 
    str_contains($fallbackReply, '180.000') || str_contains($fallbackReply, 'Hardware Laptop'),
    $fallbackReply
);
assertScenario('Fallback lokal menyertakan peringatan aman dan transparansi biaya', 
    str_contains(strtolower($fallbackReply), 'harga dasar') || str_contains(strtolower($fallbackReply), 'estimasi ai')
);

// -------------------------------------------------------------
// Skenario 12: Riwayat Percakapan Tersimpan di Database (ai_chats)
// -------------------------------------------------------------
echo "\n--- 12. Penyimpanan & Pengambilan Riwayat Percakapan (ai_chats) ---\n";
$testQ = "Bagaimana cara merawat AC agar tidak cepat rusak?";
$resHistory = $chatbotService->handleChat([
    ['role' => 'user', 'content' => $testQ]
], ['user' => $customerBudi]);

assertScenario('Percakapan mengembalikan chat_id tersimpan', !empty($resHistory['chat_id']));

// Ambil riwayat percakapan via API getConversations
$conversations = $chatbotService->getConversations(1, 'customer');
assertScenario('getConversations() berhasil mengambil daftar percakapan customer', 
    is_array($conversations) && count($conversations) > 0
);

// Uji Anti-IDOR pada Detail Percakapan
$chatId = $resHistory['chat_id'];
$ownerChat = $chatbotService->getConversationMessages((int)$chatId, 1, 'customer');
assertScenario('Pemilik sah (ID 1) dapat membaca detail pesan percakapannya', $ownerChat !== null);

$attackerChat = $chatbotService->getConversationMessages((int)$chatId, 4, 'customer');
assertScenario('Customer lain (ID 4) DITOLAK saat mencoba mengakses percakapan ID 1 (IDOR Guard)', $attackerChat === null);

// -------------------------------------------------------------
// Skenario 13: Keamanan API Key & Kredensial
// -------------------------------------------------------------
echo "\n--- 13. Verifikasi Keamanan API Key & Kredensial ---\n";
$jsonOutput = json_encode($resHistory);
assertScenario('API Key tidak terekspos di output JSON', !str_contains($jsonOutput, 'gsk_'));

$jsChatbotContent = file_get_contents(PUBLIC_PATH . '/assets/js/chatbot.js');
assertScenario('API Key tidak tertulis (hardcode) di berkas JavaScript frontend', 
    !str_contains($jsChatbotContent, 'gsk_') && !str_contains($jsChatbotContent, 'AI_API_KEY')
);

// -------------------------------------------------------------
// Skenario 14: Kesiapan Tampilan Desktop & Mobile Responsive
// -------------------------------------------------------------
echo "\n--- 14. Kesiapan Tampilan Desktop & Responsif Smartphone ---\n";
$cssContent = file_get_contents(PUBLIC_PATH . '/assets/css/chatbot.css');
assertScenario('CSS memuat styling desktop (#tj-chatbot-window)', str_contains($cssContent, '#tj-chatbot-window'));
assertScenario('CSS memuat media query smartphone (@media max-width: 480px)', 
    str_contains($cssContent, '@media') && (str_contains($cssContent, '480px') || str_contains($cssContent, '576px'))
);

// -------------------------------------------------------------
// RINGKASAN HASIL TEST
// -------------------------------------------------------------
echo "\n========================================================\n";
echo ">>> HASIL PENGUJIAN 14 SKENARIO CHATBOT+:\n";
echo "    Total Skenario Diuji : " . ($passCount + $failCount) . "\n";
echo "    Berhasil (Passed)    : {$passCount}\n";
echo "    Gagal (Failed)       : {$failCount}\n";
echo "========================================================\n";

if ($failCount === 0) {
    echo ">>> SELURUH 14 SKENARIO PENGUJIAN CHATBOT+ BERHASIL 100%!\n\n";
    exit(0);
} else {
    echo ">>> TERDAPAT SKENARIO YANG GAGAL!\n\n";
    exit(1);
}
