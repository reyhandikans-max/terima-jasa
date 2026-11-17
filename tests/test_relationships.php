<?php
/**
 * Automated Verification Script: Models, Relationships, and Validations
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

require_once APP_PATH . '/Helpers/helpers.php';

// Register autoloader
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
use App\Models\TechnicianProfile;
use App\Models\Category;
use App\Models\Service;
use App\Models\Order;
use App\Models\Review;
use App\Models\AiChat;

echo "========================================================\n";
echo "   TERIMA JASA - DATA MODEL & RELATIONSHIP TEST SUITE\n";
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

// 1. Test User 1 : 1 TechnicianProfile
$techUser = User::find(2);
assertTest("Load User id 2 (Teknisi)", $techUser !== null && $techUser->isTeknisi());

$techProfile = $techUser ? $techUser->technicianProfile() : null;
assertTest("User 1 : 1 TechnicianProfile (user -> profile)", $techProfile !== null && $techProfile->user_id == 2);

$inverseUser = $techProfile ? $techProfile->user() : null;
assertTest("TechnicianProfile 1 : 1 User (profile -> user)", $inverseUser !== null && $inverseUser->id == 2);

// 2. Test Category 1 : N Services
$cat = Category::find(1);
assertTest("Load Category id 1", $cat !== null);

$services = $cat ? $cat->services() : [];
assertTest("Category 1 : N Services (category -> services)", count($services) >= 2);

$firstService = $services[0] ?? null;
$inverseCat = $firstService ? $firstService->category() : null;
assertTest("Service N : 1 Category (service -> category)", $inverseCat !== null && $inverseCat->id == 1);

// 3. Test Customer/User 1 : N Orders
$customer = User::find(1);
assertTest("Load Customer id 1", $customer !== null && $customer->isCustomer());

$custOrders = $customer ? $customer->customerOrders() : [];
assertTest("Customer/User 1 : N Orders (customer -> orders)", count($custOrders) >= 2);

$order1 = Order::find(1);
assertTest("Order N : 1 Customer (order -> customer)", $order1 && $order1->customer()->id == 1);

// 4. Test Technician/User 1 : N Orders
$techOrders = $techUser ? $techUser->technicianOrders() : [];
assertTest("Technician/User 1 : N Orders (technician -> orders)", count($techOrders) >= 2);
assertTest("Order N : 1 Technician (order -> technician)", $order1 && $order1->technician()->id == 2);

// 5. Test Service 1 : N Orders
$service1 = Service::find(1);
$servOrders = $service1 ? $service1->orders() : [];
assertTest("Service 1 : N Orders (service -> orders)", count($servOrders) >= 1);
assertTest("Order N : 1 Service (order -> service)", $order1 && $order1->service()->id == 1);

// 6. Test Order 1 : 1 Review
$review = $order1 ? $order1->review() : null;
assertTest("Order 1 : 1 Review (order -> review)", $review !== null && $review->rating == 5);

$revOrder = $review ? $review->order() : null;
assertTest("Review 1 : 1 Order (review -> order)", $revOrder !== null && $revOrder->id == 1);

// 7. Test Customer/User 1 : N AI Chats
$aiChats = $customer ? $customer->aiChats() : [];
assertTest("Customer/User 1 : N AI Chats (customer -> aiChats)", count($aiChats) >= 2);

$firstChat = $aiChats[0] ?? null;
assertTest("AiChat N : 1 Customer (chat -> customer)", $firstChat && $firstChat->customer()->id == 1);

// 8. Test Validation Engine
$validRules = User::validationRules();
$invalidData = [
    'name' => 'A', // too short
    'email' => 'not-an-email',
    'password' => '123', // too short
    'role' => 'superman' // not in enum
];
$errors = User::validate($invalidData, $validRules);
assertTest("Validation: detect invalid name, email, password, and role", 
    isset($errors['name']) && isset($errors['email']) && isset($errors['password']) && isset($errors['role'])
);

echo "\n--------------------------------------------------------\n";
echo "SUMMARY: {$passCount} PASSED, {$failCount} FAILED.\n";
echo "========================================================\n";

if ($failCount > 0) {
    exit(1);
}
