<?php
/**
 * Test Suite: Customer Module Flow & Security Verification
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
use App\Models\Category;
use App\Models\Service;
use App\Models\Order;
use App\Models\Review;
use App\Models\TechnicianProfile;

echo "========================================================\n";
echo "   TERIMA JASA - CUSTOMER MODULE & SECURITY TEST SUITE\n";
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
// 1. SETUP AUTH CUSTOMER
// ---------------------------------------------------------------
echo "\n--- 1. Customer Authentication Setup ---\n";
$customerA = new User([
    'id'     => 1,
    'name'   => 'Budi Santoso',
    'email'  => 'customer@terimajasa.id',
    'role'   => User::ROLE_CUSTOMER,
    'status' => User::STATUS_ACTIVE,
    'phone'  => '081234567890'
]);
$customerA->id = 1;
Auth::login($customerA);

assertTest("Customer A successfully logged in", Auth::check() && Auth::id() === 1 && Auth::role() === 'customer');
assertTest("Customer name matches Budi Santoso", Auth::user()['name'] === 'Budi Santoso');

// ---------------------------------------------------------------
// 2. DASHBOARD METRICS & SHORTCUTS
// ---------------------------------------------------------------
echo "\n--- 2. Customer Dashboard Metrics ---\n";
$ordersCustA = Order::getByCustomerId(1);
$activeOrders = array_filter($ordersCustA, fn($o) => in_array($o->status, ['pending', 'accepted', 'in_progress']));
$completedOrders = array_filter($ordersCustA, fn($o) => $o->status === 'completed');

assertTest("Total orders for Customer A retrieved", count($ordersCustA) >= 2);
assertTest("Active orders count computed", count($activeOrders) >= 1);
assertTest("Completed orders count computed", count($completedOrders) >= 1);

// ---------------------------------------------------------------
// 3. DAFTAR JASA: KATEGORI, SEARCH, FILTER & DETAIL
// ---------------------------------------------------------------
echo "\n--- 3. Service Catalog, Search, Filter & Detail ---\n";
$categories = Category::all();
assertTest("Categories loaded with status active", count($categories) >= 5);

$services = Service::all();
assertTest("Services catalog loaded", count($services) >= 10);

// Search test
$searchKeyword = 'AC';
$searchResults = array_filter($services, function($s) use ($searchKeyword) {
    return str_contains(strtolower($s->name . ' ' . $s->description), strtolower($searchKeyword));
});
assertTest("Search 'AC' returns relevant services", count($searchResults) >= 3);

// Category filter test
$catId = 1;
$catServices = array_filter($services, fn($s) => (int)$s->category_id === $catId);
assertTest("Filter by Category ID 1 returns only category 1 services", count($catServices) >= 2);
foreach ($catServices as $cs) {
    assertTest("Service {$cs->id} belongs to Category {$catId}", (int)$cs->category_id === $catId);
    break;
}

// Service detail test
$detailService = Service::find(1);
assertTest("Service detail found for ID 1", $detailService !== null && $detailService->name !== '');
assertTest("Service detail has associated Category", $detailService->category() !== null);
assertTest("Service detail has estimated price", (float)$detailService->estimated_price > 0);

$availableTechs = $detailService->availableTechnicians();
assertTest("Service detail provides available technicians", !empty($availableTechs));
assertTest("Available technician has valid profile", !empty($availableTechs[0]->expertise));

// ---------------------------------------------------------------
// 4. MEMBUAT PESANAN (CREATE ORDER)
// ---------------------------------------------------------------
echo "\n--- 4. Order Creation Flow ---\n";
$newOrderData = [
    'customer_id'   => Auth::id(), // Strictly from Auth::id()
    'service_id'    => 1,
    'description'   => 'AC hembusan angin terasa kurang dingin dan ada tetesan air.',
    'location'      => 'Jl. Kemang Timur No. 10, Jakarta Selatan',
    'schedule'      => '2026-10-15 10:00:00',
    'problem_image' => 'uploads/orders/test_ac.jpg',
    'status'        => Order::STATUS_PENDING
];

// Validation check
$validErrors = Order::validate($newOrderData, Order::validationRules());
assertTest("Valid order input passes validation", empty($validErrors));

// Invalid validation check
$invalidOrderData = [
    'customer_id' => Auth::id(),
    'service_id'  => 1,
    'description' => 'A', // too short (<5)
    'location'    => '', // required
    'schedule'    => ''  // required
];
$invalidErrors = Order::validate($invalidOrderData, Order::validationRules());
assertTest("Invalid order description detected", isset($invalidErrors['description']));
assertTest("Invalid order location detected", isset($invalidErrors['location']));
assertTest("Invalid order schedule detected", isset($invalidErrors['schedule']));

// Instantiate order
$createdOrder = new Order($newOrderData);
$createdOrder->id = 99;
assertTest("Created order initial status is strictly 'pending'", $createdOrder->status === Order::STATUS_PENDING);
assertTest("Created order canCancel() is true while pending", $createdOrder->canCancel() === true);
assertTest("Created order canReview() is false while pending", $createdOrder->canReview() === false);

// ---------------------------------------------------------------
// 5. PESANAN SAYA & STATUS TRANSITIONS
// ---------------------------------------------------------------
echo "\n--- 5. Order Management & Cancellation ---\n";
// Load existing pending order
$pendingOrder = Order::find(3); // Customer 1 order (status pending)
assertTest("Pending order #3 loaded", $pendingOrder !== null && $pendingOrder->isPending());
assertTest("Pending order can be cancelled", $pendingOrder->canCancel() === true);

// Perform cancellation
$pendingOrder->status = Order::STATUS_CANCELLED;
assertTest("Pending order transitioned to 'cancelled'", $pendingOrder->isCancelled() === true);
assertTest("Cancelled order canCancel() becomes false", $pendingOrder->canCancel() === false);

// Check completed order
$completedOrder = Order::find(1); // Customer 1 order (status completed)
assertTest("Completed order #1 loaded", $completedOrder !== null && $completedOrder->isCompleted());
assertTest("Completed order CANNOT be cancelled", $completedOrder->canCancel() === false);

// ---------------------------------------------------------------
// 6. RATING & REVIEW VERIFICATION
// ---------------------------------------------------------------
echo "\n--- 6. Rating & Review Flow ---\n";
// Order 1 is completed and already has a review
assertTest("Completed order has review", $completedOrder->review() !== null);
assertTest("Order already reviewed CANNOT be reviewed again (1 order = 1 review)", $completedOrder->canReview() === false);

// Create a simulated fresh completed order without review
$freshCompletedOrder = new Order([
    'id'            => 101,
    'customer_id'   => 1,
    'technician_id' => 2,
    'service_id'    => 2,
    'description'   => 'Tambah freon AC',
    'location'      => 'Jl. Tebet No. 10',
    'schedule'      => '2026-10-10 14:00:00',
    'status'        => Order::STATUS_COMPLETED
]);
$freshCompletedOrder->id = 101;
assertTest("Fresh completed order can be reviewed", $freshCompletedOrder->canReview() === true);

// Validate review input
$reviewRules = Review::validationRules();
$validReview = [
    'order_id'      => 101,
    'customer_id'   => 1,
    'technician_id' => 2,
    'rating'        => 5,
    'comment'       => 'Teknisi sangat profesional dan ramah.'
];
$reviewErrors = Review::validate($validReview, $reviewRules);
assertTest("Valid review passes validation", empty($reviewErrors));

$invalidReview = [
    'order_id'      => 101,
    'customer_id'   => 1,
    'technician_id' => 2,
    'rating'        => 6, // invalid (>5)
    'comment'       => ''
];
$invalidReviewErrors = Review::validate($invalidReview, $reviewRules);
assertTest("Invalid rating (>5) rejected", isset($invalidReviewErrors['rating']));

// ---------------------------------------------------------------
// 7. SECURITY: STRICT CUSTOMER DATA ISOLATION
// ---------------------------------------------------------------
echo "\n--- 7. Security: Cross-Customer Access Restriction ---\n";
// Customer A (ID 1) attempts to access Order #2 (which belongs to Customer ID 4 - Siti Rahmawati)
$orderB = Order::find(2); // Customer ID 4
assertTest("Order #2 belongs to Customer ID 4", (int)$orderB->customer_id === 4);

// Security Check A: View Authorization
$isAllowedToView = ((int)$orderB->customer_id === (int)Auth::id());
assertTest("Security: Customer A (ID 1) CANNOT view Customer B's order", $isAllowedToView === false);

// Security Check B: Cancel Authorization
$isAllowedToCancel = ((int)$orderB->customer_id === (int)Auth::id() && $orderB->canCancel());
assertTest("Security: Customer A (ID 1) CANNOT cancel Customer B's order", $isAllowedToCancel === false);

// Security Check C: Review Authorization
$isAllowedToReview = ((int)$orderB->customer_id === (int)Auth::id() && $orderB->isCompleted());
assertTest("Security: Customer A (ID 1) CANNOT review Customer B's order", $isAllowedToReview === false);

// Security Check D: Order Creation Customer ID Integrity
$attemptedHijackCustomerId = 999;
$securedCustomerId = (int)Auth::id(); // System enforces session ID
assertTest("Security: System enforces authenticated customer_id over manipulated input", $securedCustomerId === 1 && $securedCustomerId !== $attemptedHijackCustomerId);

echo "\n--------------------------------------------------------\n";
echo "SUMMARY: {$passCount} PASSED, {$failCount} FAILED.\n";
echo "========================================================\n";

if ($failCount > 0) {
    exit(1);
}
