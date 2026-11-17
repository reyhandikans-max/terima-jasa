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

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Controllers\AdminProfileController;

echo "========================================================\n";
echo "   TERIMA JASA — ADMIN PROFILE MANAGEMENT TEST SUITE   \n";
echo "========================================================\n\n";

$passed = 0;
$failed = 0;

function assertTest(bool $condition, string $msg, ?string $detail = null) {
    global $passed, $failed;
    if ($condition) {
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

// Cari akun Admin di database
$adminUser = User::firstWhere('role', 'admin');
if (!$adminUser) {
    echo " [FATAL] User admin tidak ditemukan di database!\n";
    exit(1);
}

// Simpan state awal admin agar bisa direstore kembali setelah test
$originalName  = $adminUser->name;
$originalEmail = $adminUser->email;
$originalPhone = $adminUser->phone;
$originalPass  = $adminUser->password;

echo "--- 1. Akses Halaman Edit Profil Admin (GET /admin/profile) ---\n";

// Login sebagai Admin
$_SESSION['user'] = [
    'id'    => (int)$adminUser->id,
    'name'  => $adminUser->name,
    'email' => $adminUser->email,
    'role'  => 'admin',
    'phone' => $adminUser->phone,
];

assertTest(Auth::check() && Auth::role() === 'admin', "Sesi Admin aktif (ID: {$adminUser->id})");

// Pastikan controller dan view dapat di-instantiate
$controller = new AdminProfileController();
assertTest(class_exists(AdminProfileController::class), "Controller AdminProfileController tersedia");
assertTest(file_exists(APP_PATH . '/Views/admin/profile/edit.php'), "View admin/profile/edit.php tersedia");

echo "\n--- 2. Validasi Input Profil Admin (POST /admin/profile) ---\n";

// Uji 2a: Nama terlalu pendek (< 3 karakter)
$reqInvalidName = new Request(['name' => 'Ad', 'email' => 'admin@terimajasa.id']);
$controller->update($reqInvalidName);
assertTest(
    !empty($_SESSION['errors']['name']),
    "Validasi menolak nama administrator yang kurang dari 3 karakter"
);

// Uji 2b: Format email tidak valid
$reqInvalidEmail = new Request(['name' => 'Super Administrator', 'email' => 'bukan-email-valid']);
$controller->update($reqInvalidEmail);
assertTest(
    !empty($_SESSION['errors']['email']),
    "Validasi menolak format email yang tidak valid"
);

// Uji 2c: Email duplikat (menggunakan email akun customer)
$customerUser = User::firstWhere('role', 'customer');
if ($customerUser) {
    $reqDuplicateEmail = new Request([
        'name'  => 'Super Administrator',
        'email' => $customerUser->email
    ]);
    $controller->update($reqDuplicateEmail);
    assertTest(
        !empty($_SESSION['errors']['email']),
        "Validasi menolak email yang sudah terdaftar pada pengguna lain ({$customerUser->email})"
    );
}

echo "\n--- 3. Pembaruan Identitas Profil yang Valid ---\n";

$newName  = 'Super Admin Terverifikasi';
$newEmail = 'admin.updated@terimajasa.id';
$newPhone = '+6281299887766';

$reqValid = new Request([
    'name'  => $newName,
    'email' => $newEmail,
    'phone' => $newPhone,
]);

$controller->update($reqValid);

// Cek data di database
$refreshedAdmin = User::find((int)$adminUser->id);
assertTest(
    $refreshedAdmin && $refreshedAdmin->name === $newName,
    "Nama admin berhasil diupdate di database menjadi '{$newName}'"
);
assertTest(
    $refreshedAdmin && $refreshedAdmin->email === $newEmail,
    "Email admin berhasil diupdate di database menjadi '{$newEmail}'"
);
assertTest(
    $refreshedAdmin && $refreshedAdmin->phone === $newPhone,
    "Nomor telepon admin berhasil diupdate di database menjadi '{$newPhone}'"
);

// Cek pembaruan pada session aktif
assertTest(
    $_SESSION['user']['name'] === $newName && $_SESSION['user']['email'] === $newEmail,
    "Data session aktif terbarui otomatis tanpa perlu login ulang"
);
assertTest(
    !empty($_SESSION['flash_success']),
    "Pesan sukses ditampilkan: '{$_SESSION['flash_success']}'"
);

echo "\n--- 4. Validasi & Penggantian Kata Sandi Admin ---\n";

// Uji 4a: Ganti password dengan password lama salah
$reqWrongOldPass = new Request([
    'name'                     => $newName,
    'email'                    => $newEmail,
    'phone'                    => $newPhone,
    'current_password'         => 'passwordSalah123',
    'new_password'             => 'adminBaru1234',
    'new_password_confirmation'=> 'adminBaru1234',
]);
$controller->update($reqWrongOldPass);
assertTest(
    !empty($_SESSION['errors']['current_password']),
    "Perubahan password ditolak jika kata sandi lama salah"
);

// Uji 4b: Ganti password dengan konfirmasi tidak cocok
$reqMismatchPass = new Request([
    'name'                     => $newName,
    'email'                    => $newEmail,
    'phone'                    => $newPhone,
    'current_password'         => 'admin123',
    'new_password'             => 'adminBaru1234',
    'new_password_confirmation'=> 'passwordBedaTotal',
]);
$controller->update($reqMismatchPass);
assertTest(
    !empty($_SESSION['errors']['new_password_confirmation']),
    "Perubahan password ditolak jika konfirmasi kata sandi tidak cocok"
);

// Uji 4c: Update tanpa mengubah password (password lama harus tetap utuh)
$passBeforeUpdate = $refreshedAdmin->password;
$reqNoPassChange = new Request([
    'name'                     => 'Super Admin Official',
    'email'                    => $newEmail,
    'phone'                    => $newPhone,
    'current_password'         => '',
    'new_password'             => '',
    'new_password_confirmation'=> '',
]);
$controller->update($reqNoPassChange);
$adminAfterNoPass = User::find((int)$adminUser->id);
assertTest(
    $adminAfterNoPass->password === $passBeforeUpdate,
    "Password lama tetap utuh jika kolom password baru dikosongkan"
);

echo "\n--- 5. Integrasi dengan Chatbot+ Identitas Admin ---\n";
$chatbotService = new \App\Services\ChatbotService();
$chatRes = $chatbotService->handleChat([['role' => 'user', 'content' => 'siapa saya']], [
    'user' => [
        'id'    => (int)$adminUser->id,
        'name'  => $adminAfterNoPass->name,
        'role'  => 'admin',
        'email' => $adminAfterNoPass->email
    ]
]);
$botReply = $chatRes['reply'] ?? '';
assertTest(
    str_contains($botReply, 'Super Admin Official') && str_contains($botReply, 'Administrator'),
    "Chatbot+ langsung mengenali nama identitas admin baru yang telah diubah",
    $botReply
);

echo "\n--- 6. Kembalikan Data Semula (Clean Up / Restore) ---\n";
$adminUser->name     = $originalName;
$adminUser->email    = $originalEmail;
$adminUser->phone    = $originalPhone;
$adminUser->password = $originalPass;
$adminUser->save();

$_SESSION['user'] = [
    'id'    => (int)$adminUser->id,
    'name'  => $originalName,
    'email' => $originalEmail,
    'role'  => 'admin',
    'phone' => $originalPhone,
];
assertTest($adminUser->name === $originalName, "Data identitas admin asli berhasil direstore ({$originalName})");

echo "\n========================================================\n";
echo "HASIL PENGUJIAN PROFIL ADMINISTRATOR:\n";
echo "Total Kasus Diuji : " . ($passed + $failed) . "\n";
echo "Berhasil (Passed) : {$passed}\n";
echo "Gagal (Failed)    : {$failed}\n";
echo "========================================================\n";

if ($failed === 0) {
    echo ">>> SELURUH PENGUJIAN PROFIL ADMIN BERHASIL 100%!\n";
} else {
    exit(1);
}

