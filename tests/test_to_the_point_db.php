<?php
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
require_once APP_PATH . '/Helpers/helpers.php';
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

echo "========================================================\n";
echo "   TERIMA JASA — TO-THE-POINT & DATABASE ACCURACY TEST  \n";
echo "========================================================\n\n";

$service = new ChatbotService();
$passed = 0;
$failed = 0;

function assertTest(bool $cond, string $msg, ?string $detail = null) {
    global $passed, $failed;
    if ($cond) {
        $passed++;
        echo " [PASS] {$msg}\n";
    } else {
        $failed++;
        echo " [FAIL] {$msg}\n";
        if ($detail) {
            echo "        Detail: " . str_replace("\n", "\n        ", $detail) . "\n";
        }
    }
}

// 1. Admin context
$adminCtx = [
    'user' => [
        'id'    => 3,
        'name'  => 'Super Admin Terima Jasa',
        'role'  => 'admin',
        'email' => 'admin@terimajasa.id'
    ]
];

// Test 1: Admin bertanya "siapa saya"
echo "--- 1. Uji Pertanyaan Identitas 'siapa saya' untuk Admin ---\n";
$res1 = $service->handleChat([['role' => 'user', 'content' => 'siapa saya']], $adminCtx);
$reply1 = $res1['reply'] ?? '';
assertTest(
    str_contains($reply1, 'Super Admin Terima Jasa') && str_contains($reply1, 'Administrator'),
    "Admin bertanya 'siapa saya' dijawab to the point dengan nama dan peran Administrator",
    $reply1
);
assertTest(
    !str_contains($reply1, 'Mengenai identitas Anda, saya'),
    "Respon 'siapa saya' tidak ragu-ragu atau terpotong",
    $reply1
);

// Test 2: Admin bertanya "siapa saja"
echo "\n--- 2. Uji Pertanyaan 'siapa saja' untuk Admin ---\n";
$res2 = $service->handleChat([['role' => 'user', 'content' => 'siapa saja']], $adminCtx);
$reply2 = $res2['reply'] ?? '';
assertTest(
    str_contains($reply2, 'Budi Santoso') && str_contains($reply2, 'Siti Rahmawati'),
    "Admin bertanya 'siapa saja' menampilkan nama pelanggan aktual dari database",
    $reply2
);
assertTest(
    !str_contains($reply2, 'menjaga privasi, saya tidak dapat menampilkan nama'),
    "Admin TIDAK DITOLAK dengan alasan privasi saat meminta daftar pengguna",
    $reply2
);

// Test 3: Admin bertanya "berapa jumlah total costumer?"
echo "\n--- 3. Uji Pertanyaan Total Customer ---\n";
$res3 = $service->handleChat([['role' => 'user', 'content' => 'berapa jumlah total costumer?']], $adminCtx);
$reply3 = $res3['reply'] ?? '';
assertTest(
    str_contains($reply3, '5') && (str_contains(strtolower($reply3), 'customer') || str_contains(strtolower($reply3), 'pelanggan')),
    "Pertanyaan total costumer dijawab tepat 5 orang pelanggan",
    $reply3
);

// Test 4: Customer context
$customerCtx = [
    'user' => [
        'id'    => 1,
        'name'  => 'Budi Santoso',
        'role'  => 'customer',
        'email' => 'customer@terimajasa.id'
    ]
];

// Test 4: Customer bertanya "siapa saya"
echo "\n--- 4. Uji Pertanyaan Identitas 'siapa saya' untuk Customer ---\n";
$res4 = $service->handleChat([['role' => 'user', 'content' => 'siapa saya']], $customerCtx);
$reply4 = $res4['reply'] ?? '';
assertTest(
    str_contains($reply4, 'Budi Santoso') && str_contains($reply4, 'Customer'),
    "Customer bertanya 'siapa saya' dijawab sesuai akun Budi Santoso",
    $reply4
);

// Test 5: Customer bertanya "siapa saja customer lain"
echo "\n--- 5. Uji Perlindungan Privasi untuk Customer Biasa ---\n";
$res5 = $service->handleChat([['role' => 'user', 'content' => 'siapa saja customernya']], $customerCtx);
$reply5 = $res5['reply'] ?? '';
assertTest(
    str_contains($reply5, 'privasi') && !str_contains($reply5, 'reyhandika'),
    "Customer biasa ditolak melihat data pelanggan lain demi privasi (Anti-Data-Leak)",
    $reply5
);

// Test 6: Pertanyaan daftar teknisi
echo "\n--- 6. Uji Pertanyaan Daftar Teknisi ---\n";
$res6 = $service->handleChat([['role' => 'user', 'content' => 'siapa saja teknisinya']], $adminCtx);
$reply6 = $res6['reply'] ?? '';
assertTest(
    str_contains($reply6, 'Hendra Wijaya') && str_contains($reply6, 'Ahmad Fauzi'),
    "Pertanyaan teknisi menampilkan teknisi terdaftar resmi dari DB",
    $reply6
);

// Test 7: Uji To The Point Sanitizer
echo "\n--- 7. Uji Sanitizer Respon To The Point ---\n";
$verboseSample = "Halo! Saya adalah Chatbot+ Asisten AI Resmi platform Terima Jasa, terhubung langsung dengan database real-time kami. Saya dapat membantu Anda dengan informasi tentang katalog layanan.\n\nTotal teknisi terdaftar adalah 8 orang.";
$cleaned = $service->sanitizeToThePointReply($verboseSample, 'berapa total teknisi');
assertTest(
    !str_contains($cleaned, 'Halo! Saya adalah Chatbot+') && str_contains($cleaned, 'Total teknisi terdaftar adalah 8 orang.'),
    "Boilerplate pembuka berulang berhasil dibersihkan untuk pertanyaan spesifik",
    $cleaned
);

// Test 8: Pertanyaan di luar cakupan (Out-of-Scope)
echo "\n--- 8. Uji Pertanyaan di Luar Cakupan (Out-of-Scope) ---\n";
$res8 = $service->handleChat([['role' => 'user', 'content' => 'buatkan kode python hello world']], $adminCtx);
assertTest(
    ($res8['source'] ?? '') === 'scope_guard' && !str_contains($res8['reply'], 'def main'),
    "Pertanyaan kode python ditolak tegas oleh scope guard",
    $res8['reply'] ?? ''
);

echo "\n========================================================\n";
echo "HASIL PENGUJIAN TO-THE-POINT & DATABASE ACCURACY:\n";
echo "Total Diuji : " . ($passed + $failed) . "\n";
echo "Berhasil    : {$passed}\n";
echo "Gagal       : {$failed}\n";
echo "========================================================\n";

if ($failed === 0) {
    echo ">>> SELURUH PENGUJIAN TO-THE-POINT & DATABASE BERHASIL 100%!\n";
} else {
    exit(1);
}

