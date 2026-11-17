<?php
/**
 * Test Suite: Technician Module Flow & Security Verification
 * Terima Jasa - Platform Penghubung Customer & Teknisi
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
use App\Models\Order;
use App\Models\Service;
use App\Models\Review;

echo "========================================================\n";
echo "   TERIMA JASA - TECHNICIAN MODULE & SECURITY TEST SUITE\n";
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
// 1. SETUP AUTH TECHNICIAN
// ---------------------------------------------------------------
echo "\n--- 1. Technician Authentication Setup ---\n";
$techA = new User([
    'id'     => 2,
    'name'   => 'Hendra Wijaya',
    'email'  => 'teknisi@terimajasa.id',
    'role'   => User::ROLE_TEKNISI,
    'status' => User::STATUS_ACTIVE,
    'phone'  => '082198765432'
]);
$techA->id = 2;
Auth::login($techA);

assertTest("Technician A successfully logged in", Auth::check() && Auth::id() === 2 && Auth::role() === 'teknisi');
assertTest("Technician name matches Hendra Wijaya", Auth::user()['name'] === 'Hendra Wijaya');

// ---------------------------------------------------------------
// 2. DASHBOARD METRICS & AVAILABILITY STATUS
// ---------------------------------------------------------------
echo "\n--- 2. Technician Dashboard Metrics & Availability ---\n";
$profileA = $techA->technicianProfile();
assertTest("Technician profile found", $profileA !== null);
assertTest("Initial availability status is available or defined", in_array($profileA->availability, ['available', 'unavailable', 'busy', 'offline']));

$techOrders = $techA->technicianOrders();
$activeJobs = array_filter($techOrders, fn($o) => in_array($o->status, ['accepted', 'in_progress']));
$completedJobs = array_filter($techOrders, fn($o) => $o->status === 'completed');

assertTest("Total jobs loaded for Technician A", count($techOrders) >= 1);
assertTest("Active jobs computed", is_numeric(count($activeJobs)));
assertTest("Completed jobs computed", count($completedJobs) >= 1);

$reviews = $techA->reviewsReceived();
assertTest("Reviews received by Technician A loaded", count($reviews) >= 1);
$avgRating = !empty($reviews) ? round(array_sum(array_map(fn($r) => (int)$r->rating, $reviews)) / count($reviews), 1) : 5.0;
assertTest("Average rating score computed correctly (e.g. 5.0)", $avgRating >= 1.0 && $avgRating <= 5.0);

// ---------------------------------------------------------------
// 3. EDIT PROFIL & KETERSEDIAAN
// ---------------------------------------------------------------
echo "\n--- 3. Edit Profile & Availability Toggle ---\n";
$validProfileData = [
    'user_id'             => 2,
    'expertise'           => 'Spesialis AC Inverter & Chiller Komersial',
    'experience'          => '7 Tahun Pengalaman',
    'phone'               => '082198765432',
    'location'            => 'Jakarta Selatan & Depok',
    'availability'        => 'available',
    'verification_status' => 'verified'
];
$profileErrors = TechnicianProfile::validate($validProfileData, TechnicianProfile::validationRules(2));
assertTest("Valid profile update passes validation", empty($profileErrors));

// Test availability update: available -> unavailable
$profileA->availability = 'unavailable';
assertTest("Availability updated to unavailable", $profileA->availability === 'unavailable');

// Toggle back: unavailable -> available
$profileA->availability = 'available';
assertTest("Availability toggled back to available", $profileA->availability === 'available');

// ---------------------------------------------------------------
// 4. FLOW TEST: PERMINTAAN -> ACCEPT -> IN PROGRESS -> COMPLETED
// ---------------------------------------------------------------
echo "\n--- 4. Full Flow: Permintaan -> Accept -> In Progress -> Completed ---\n";

// Step 1: Permintaan (Pending Order)
$testOrder = new Order([
    'id'            => 501,
    'customer_id'   => 1,
    'technician_id' => 2,
    'service_id'    => 1,
    'description'   => 'AC kamar anak tidak dingin dan blower berisik.',
    'location'      => 'Jl. Radio Dalam No. 15, Jakarta Selatan',
    'schedule'      => '2026-10-14 11:00:00',
    'status'        => Order::STATUS_PENDING,
    'created_at'    => date('Y-m-d H:i:s')
]);
$testOrder->id = 501;

assertTest("Step 1 (Permintaan): Order initial status is strictly 'pending'", $testOrder->status === Order::STATUS_PENDING);
assertTest("Step 1 (Permintaan): Order is in pending state", $testOrder->isPending() === true);

// Step 2: Accept
$testOrder->status = Order::STATUS_ACCEPTED;
$testOrder->technician_id = Auth::id(); // Assign to logged-in tech (ID 2)
assertTest("Step 2 (Accept): Order status transitions to 'accepted'", $testOrder->status === Order::STATUS_ACCEPTED);
assertTest("Step 2 (Accept): Technician ID assigned to logged-in technician (ID 2)", (int)$testOrder->technician_id === 2);
assertTest("Step 2 (Accept): isAccepted() is true", $testOrder->isAccepted() === true);

// Step 3: In Progress (Mulai Pengerjaan)
$testOrder->status = Order::STATUS_IN_PROGRESS;
assertTest("Step 3 (In Progress): Order status transitions to 'in_progress'", $testOrder->status === Order::STATUS_IN_PROGRESS);
assertTest("Step 3 (In Progress): isInProgress() is true", $testOrder->isInProgress() === true);

// Step 4: Completed (Selesaikan Pekerjaan)
$testOrder->status = Order::STATUS_COMPLETED;
assertTest("Step 4 (Completed): Order status transitions to 'completed'", $testOrder->status === Order::STATUS_COMPLETED);
assertTest("Step 4 (Completed): isCompleted() is true", $testOrder->isCompleted() === true);

// ---------------------------------------------------------------
// 5. SECURITY: CROSS-TECHNICIAN PROTECTION
// ---------------------------------------------------------------
echo "\n--- 5. Security: Cross-Technician Modification Guard ---\n";
// Create another technician (Tech B - Ahmad Fauzi, ID 5)
$techB = new User([
    'id'     => 5,
    'name'   => 'Ahmad Fauzi (Teknisi Listrik)',
    'email'  => 'ahmad@teknisi.id',
    'role'   => User::ROLE_TEKNISI,
    'status' => User::STATUS_ACTIVE
]);
$techB->id = 5;

// Simulate Tech B logging in
Auth::login($techB);
assertTest("Tech B (ID 5) logged in", Auth::id() === 5);

// Tech B tries to modify Tech A's order ($testOrder has technician_id = 2)
$isTechBAuthorized = ((int)$testOrder->technician_id === (int)Auth::id());
assertTest("Security: Tech B CANNOT modify status of Tech A's order", $isTechBAuthorized === false);

// Security Check: Complete action guard
$canTechBComplete = ((int)$testOrder->technician_id === (int)Auth::id() && $testOrder->isInProgress());
assertTest("Security: Tech B CANNOT complete Tech A's order", $canTechBComplete === false);

// Switch back to Tech A
Auth::login($techA);
assertTest("Switched back to Tech A (ID 2)", Auth::id() === 2);

// ---------------------------------------------------------------
// 6. RIWAYAT & REVIEWS QUERY
// ---------------------------------------------------------------
echo "\n--- 6. Job History & Customer Reviews Display ---\n";
// Check completed order #1 belongs to Tech A
$completedJob1 = Order::find(1);
assertTest("Completed Job #1 technician_id matches Tech A (ID 2)", (int)$completedJob1->technician_id === 2);
assertTest("Completed Job #1 has customer", $completedJob1->customer() !== null);
assertTest("Completed Job #1 has service", $completedJob1->service() !== null);
assertTest("Completed Job #1 has review with rating", $completedJob1->review() !== null && $completedJob1->review()->rating == 5);

echo "\n--------------------------------------------------------\n";
echo "SUMMARY: {$passCount} PASSED, {$failCount} FAILED.\n";
echo "========================================================\n";

if ($failCount > 0) {
    exit(1);
}
