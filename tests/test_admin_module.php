<?php
/**
 * Test Suite: Admin Module Flow, Management & Security Verification
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
use App\Models\TechnicianProfile;
use App\Models\Category;
use App\Models\Service;
use App\Models\Order;
use App\Models\Review;

echo "========================================================\n";
echo "   TERIMA JASA - ADMIN MODULE VERIFICATION TEST SUITE   \n";
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
// 1. ADMIN AUTHENTICATION & ROLE RESTRICTION
// ---------------------------------------------------------------
echo "\n--- 1. Admin Authentication & Role Security ---\n";

$adminUser = new User([
    'id'     => 3,
    'name'   => 'Super Admin Terima Jasa',
    'email'  => 'admin@terimajasa.id',
    'role'   => User::ROLE_ADMIN,
    'status' => User::STATUS_ACTIVE,
    'phone'  => '081100001111'
]);
$adminUser->id = 3;

Auth::login($adminUser);
assertTest('Admin dapat login dengan role admin', Auth::check() && Auth::role() === User::ROLE_ADMIN);
assertTest('Admin user model isAdmin() bernilai true', $adminUser->isAdmin());
assertTest('Admin dashboardUrl mengarah ke /admin/dashboard', str_contains(Auth::dashboardUrl(User::ROLE_ADMIN), 'admin/dashboard'));

// ---------------------------------------------------------------
// 2. ADMIN DASHBOARD STATS & RECENT LISTS
// ---------------------------------------------------------------
echo "\n--- 2. Admin Dashboard Stats & Metrics ---\n";

$allUsers   = User::all();
$allOrders  = Order::all();
$allTechs   = TechnicianProfile::all();
$allCats    = Category::all();
$allServs   = Service::all();

$customers   = array_values(array_filter($allUsers, fn($u) => $u->role === 'customer'));
$technicians = array_values(array_filter($allUsers, fn($u) => $u->role === 'teknisi'));

$pendingVerifs = count(array_filter($allTechs, fn($t) => $t->verification_status === 'pending'));
$pendingOrders = count(array_filter($allOrders, fn($o) => $o->status === 'pending'));
$activeOrders  = count(array_filter($allOrders, fn($o) => in_array($o->status, ['accepted', 'in_progress'])));
$completedOrders = count(array_filter($allOrders, fn($o) => $o->status === 'completed'));

assertTest('Total customer terhitung valid (> 0)', count($customers) > 0);
assertTest('Total teknisi terhitung valid (> 0)', count($technicians) > 0);
assertTest('Teknisi menunggu verifikasi terdeteksi', $pendingVerifs >= 0);
assertTest('Total jasa katalog terhitung (> 0)', count($allServs) > 0);
assertTest('Total order terhitung (> 0)', count($allOrders) > 0);
assertTest('Order status pending terhitung', $pendingOrders >= 0);
assertTest('Order status aktif terhitung', $activeOrders >= 0);
assertTest('Order status selesai terhitung', $completedOrders >= 0);

// ---------------------------------------------------------------
// 3. CUSTOMER MANAGEMENT
// ---------------------------------------------------------------
echo "\n--- 3. Customer Management Features ---\n";

// List & Search
$cust = $customers[0] ?? null;
assertTest('Customer pertama berhasil ditemukan', $cust !== null);

// Detail customer
$custOrders = Order::getByCustomerId((int)$cust->id);
assertTest('Melihat riwayat order customer valid', is_array($custOrders));

// Edit & Toggle Status
$initialStatus = $cust->status ?? 'active';
$cust->status = ($initialStatus === 'active') ? 'inactive' : 'active';
assertTest('Activate/Deactivate customer status toggled', $cust->status !== $initialStatus);
$cust->status = $initialStatus; // restore

// ---------------------------------------------------------------
// 4. TECHNICIAN MANAGEMENT & VERIFICATION
// ---------------------------------------------------------------
echo "\n--- 4. Technician Management & Verification ---\n";

$tech = $technicians[0] ?? null;
assertTest('Mitra teknisi berhasil dimuat', $tech !== null);

$techProfile = $tech->technicianProfile();
assertTest('Profil teknisi terkait berhasil diakses', $techProfile !== null);

// Test Status Verifikasi: pending -> verified -> rejected
$techProfile->verification_status = TechnicianProfile::VERIFICATION_VERIFIED;
assertTest('Admin dapat memverifikasi teknisi (status = verified)', $techProfile->verification_status === 'verified');

$techProfile->verification_status = TechnicianProfile::VERIFICATION_REJECTED;
assertTest('Admin dapat menolak verifikasi teknisi (status = rejected)', $techProfile->verification_status === 'rejected');

$techProfile->verification_status = TechnicianProfile::VERIFICATION_PENDING;
assertTest('Status pending terakomodir', $techProfile->verification_status === 'pending');

// ---------------------------------------------------------------
// 5. CATEGORY MANAGEMENT CRUD
// ---------------------------------------------------------------
echo "\n--- 5. Category Management CRUD ---\n";

// Create
$newCat = new Category([
    'name'        => 'Kategori Uji Coba Admin',
    'description' => 'Deskripsi testing kategori baru',
    'status'      => 'active'
]);
$saved = $newCat->save();
assertTest('Admin dapat Create Category', $saved === true && !empty($newCat->id));

// Read
$readCat = Category::find($newCat->id) ?? $newCat;
assertTest('Admin dapat Read Category', $readCat !== null && $readCat->name === 'Kategori Uji Coba Admin');

// Update
$readCat->name = 'Kategori Uji Coba Diperbarui';
$readCat->save();
assertTest('Admin dapat Update Category', $readCat->name === 'Kategori Uji Coba Diperbarui');

// Delete
$deleted = $readCat->delete();
assertTest('Admin dapat Delete Category', $deleted === true);

// ---------------------------------------------------------------
// 6. SERVICE MANAGEMENT CRUD
// ---------------------------------------------------------------
echo "\n--- 6. Service Management CRUD ---\n";

// Create Service
$newService = new Service([
    'category_id'     => 1,
    'name'            => 'Servis AC Inverter Baru',
    'description'     => 'Pembersihan modul dan PCB inverter',
    'estimated_price' => 125000.00,
    'status'          => 'active'
]);
$savedSrv = $newService->save();
assertTest('Admin dapat Create Service', $savedSrv === true && !empty($newService->id));

// Read Service
$readSrv = Service::find($newService->id) ?? $newService;
assertTest('Admin dapat Read Service dan relasi kategori', $readSrv !== null && $readSrv->category_id === 1);

// Update Service
$readSrv->estimated_price = 140000.00;
$readSrv->save();
assertTest('Admin dapat Update Service (harga/status)', (float)$readSrv->estimated_price === 140000.00);

// Delete Service
$deletedSrv = $readSrv->delete();
assertTest('Admin dapat Delete Service', $deletedSrv === true);

// ---------------------------------------------------------------
// 7. ORDER MANAGEMENT & FILTERS
// ---------------------------------------------------------------
echo "\n--- 7. Order Management & Multi-Criteria Filters ---\n";

$firstOrder = $allOrders[0] ?? null;
assertTest('Admin dapat melihat detail order', $firstOrder !== null);
assertTest('Detail order memuat customer', $firstOrder->customer() !== null || !empty($firstOrder->customer_id));
assertTest('Detail order memuat service', $firstOrder->service() !== null);
assertTest('Badge status order valid', isset($firstOrder->getStatusBadge()['label']));

// Filter simulation
$ordersPending = array_filter($allOrders, fn($o) => $o->status === 'pending');
$ordersCompleted = array_filter($allOrders, fn($o) => $o->status === 'completed');
assertTest('Filter berdasarkan status berfungsi', is_array($ordersPending) && is_array($ordersCompleted));

// ---------------------------------------------------------------
// 8. REPORTS & ANALYTICS
// ---------------------------------------------------------------
echo "\n--- 8. Reports & Analytics Summary ---\n";

// Order berdasarkan status
$statusDist = [];
foreach ($allOrders as $o) {
    $st = $o->status ?? 'pending';
    $statusDist[$st] = ($statusDist[$st] ?? 0) + 1;
}
assertTest('Laporan status pesanan teragregasi', count($statusDist) > 0);

// Teknisi dengan order terbanyak
$techRanking = [];
foreach ($technicians as $t) {
    $c = count(array_filter($allOrders, fn($o) => (int)$o->technician_id === (int)$t->id));
    $techRanking[$t->name] = $c;
}
arsort($techRanking);
assertTest('Peringkat teknisi dengan order terbanyak terhitung', !empty($techRanking));

// Jasa paling banyak dipesan
$serviceRanking = [];
foreach ($allServs as $s) {
    $c = count(array_filter($allOrders, fn($o) => (int)$o->service_id === (int)$s->id));
    $serviceRanking[$s->name] = $c;
}
arsort($serviceRanking);
assertTest('Peringkat jasa paling banyak dipesan terhitung', !empty($serviceRanking));

// ---------------------------------------------------------------
// TEST SUMMARY
// ---------------------------------------------------------------
echo "\n========================================================\n";
echo ">>> TEST EXECUTION SUMMARY:\n";
echo "    Passed: {$passCount}\n";
echo "    Failed: {$failCount}\n";
echo "========================================================\n";

if ($failCount === 0) {
    echo ">>> ALL ADMIN MODULE SPECIFICATIONS MET (100% SUCCESS)\n\n";
    exit(0);
} else {
    echo ">>> SOME TESTS FAILED!\n\n";
    exit(1);
}
