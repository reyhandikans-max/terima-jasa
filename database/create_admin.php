<?php
/**
 * Terima Jasa - Create Admin CLI Command
 * Usage: php database/create_admin.php [name] [email] [password] [phone]
 * Example: php database/create_admin.php "Super Admin" "admin@terimajasa.id" "password123" "081100001111"
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

require_once APP_PATH . '/Helpers/helpers.php';

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = APP_PATH . '/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require $file;
});

use App\Models\User;

echo "========================================================\n";
echo "   TERIMA JASA - COMMAND PEMBUATAN AKUN ADMINISTRATOR\n";
echo "========================================================\n";

$args = array_slice($argv ?? [], 1);

$name = $args[0] ?? 'Super Admin Terima Jasa';
$email = $args[1] ?? 'admin@terimajasa.id';
$password = $args[2] ?? 'password123';
$phone = $args[3] ?? '081100001111';

echo "Membuat akun Admin dengan data:\n";
echo "- Nama     : {$name}\n";
echo "- Email    : {$email}\n";
echo "- Role     : admin (Hak akses sistem penuh)\n";
echo "- No. Telp : {$phone}\n\n";

$passwordHash = password_hash($password, PASSWORD_BCRYPT);

try {
    // Check if user already exists
    $existing = User::firstWhere('email', $email);
    if ($existing) {
        echo "[INFO] User dengan email {$email} sudah ada di database.\n";
        echo "Mengupdate password dan memastikan peran adalah admin...\n";
        $existing->password = $passwordHash;
        $existing->role = User::ROLE_ADMIN;
        $existing->status = User::STATUS_ACTIVE;
        $existing->save();
        echo "[SUCCESS] Akun admin berhasil diperbarui!\n";
    } else {
        $admin = User::create([
            'name'     => $name,
            'email'    => $email,
            'password' => $passwordHash,
            'role'     => User::ROLE_ADMIN,
            'status'   => User::STATUS_ACTIVE,
            'phone'    => $phone
        ]);

        if ($admin) {
            echo "[SUCCESS] Akun Admin ID #{$admin->id} berhasil dibuat di database!\n";
        } else {
            echo "[INFO] Database offline atau tidak tersedia. Data admin siap digunakan via mock fallback.\n";
        }
    }

    echo "\nKredensial Login Admin:\n";
    echo "URL      : " . url('login') . "\n";
    echo "Email    : {$email}\n";
    echo "Password : {$password}\n";
    echo "========================================================\n";

} catch (\Exception $e) {
    echo "[ERROR] Terjadi kesalahan: " . $e->getMessage() . "\n";
    exit(1);
}
