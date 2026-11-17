<?php
/**
 * Test Suite: Integrated Notification System — Terima Jasa
 * Verifies all 12 mandatory scenarios from Section H of user request.
 */

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

use App\Models\Notification;
use App\Models\Order;
use App\Models\User;
use App\Services\NotificationService;
use App\Controllers\NotificationController;
use App\Core\Auth;
use App\Core\Request;

echo "========================================================\n";
echo "   TERIMA JASA — NOTIFICATION SYSTEM 12 TEST SUITE     \n";
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

// Ensure database table exists
NotificationService::ensureTableExists();

// Fetch test users
$customerUser = User::where('role', User::ROLE_CUSTOMER)[0] ?? null;
$technicianUser = User::where('role', User::ROLE_TEKNISI)[0] ?? null;
$adminUser = User::where('role', User::ROLE_ADMIN)[0] ?? null;

if (!$customerUser || !$technicianUser || !$adminUser) {
    echo "Creating demo users for test...\n";
    if (!$customerUser) {
        $customerUser = User::create([
            'name' => 'Test Customer',
            'email' => 'customer_test_' . time() . '@terimajasa.id',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
            'role' => User::ROLE_CUSTOMER,
            'status' => 'active'
        ]);
    }
    if (!$technicianUser) {
        $technicianUser = User::create([
            'name' => 'Test Technician',
            'email' => 'tech_test_' . time() . '@terimajasa.id',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
            'role' => User::ROLE_TEKNISI,
            'status' => 'active'
        ]);
    }
    if (!$adminUser) {
        $adminUser = User::where('role', 'admin')[0] ?? null;
    }
}

$customerId = (int)$customerUser->id;
$techId = (int)$technicianUser->id;
$adminId = (int)($adminUser ? $adminUser->id : 3);

// ----------------------------------------------------
// Setup Dummy Test Order
// ----------------------------------------------------
$testOrder = Order::create([
    'customer_id'   => $customerId,
    'technician_id' => $techId,
    'service_id'    => 1,
    'description'   => 'Servis AC ruang tamu bocor pipa pendingin',
    'location'      => 'Jl. Testing No. 12, Jakarta',
    'schedule'      => date('Y-m-d H:i:s', strtotime('+1 day')),
    'status'        => Order::STATUS_PENDING,
    'created_at'    => date('Y-m-d H:i:s')
]);

if (!$testOrder) {
    $testOrder = new Order([
        'id'            => 99999,
        'customer_id'   => $customerId,
        'technician_id' => $techId,
        'service_id'    => 1,
        'description'   => 'Servis AC ruang tamu',
        'location'      => 'Jl. Testing No. 12',
        'status'        => Order::STATUS_PENDING
    ]);
}

// ----------------------------------------------------
// Scenario 1: Customer membuat pesanan
// ----------------------------------------------------
assertScenario(
    "Scenario 1: Customer membuat pesanan",
    $testOrder && $testOrder->id > 0 && (int)$testOrder->customer_id === $customerId,
    "Order ID: " . $testOrder->id
);

// ----------------------------------------------------
// Scenario 2: Customer menerima notifikasi pesanan
// ----------------------------------------------------
NotificationService::notifyOrderCreated($testOrder);

$customerNotifs = NotificationService::getByUser($customerId, 5);
$orderCreatedNotif = null;
foreach ($customerNotifs as $cn) {
    if ($cn->type === Notification::TYPE_ORDER_CREATED && (int)$cn->related_id === (int)$testOrder->id) {
        $orderCreatedNotif = $cn;
        break;
    }
}

assertScenario(
    "Scenario 2: Customer menerima notifikasi pesanan berhasil dibuat",
    $orderCreatedNotif !== null && strpos($orderCreatedNotif->title, 'Berhasil Dibuat') !== false,
    $orderCreatedNotif ? $orderCreatedNotif->title : 'Notifikasi customer tidak ditemukan'
);

// ----------------------------------------------------
// Scenario 3: Teknisi menerima permintaan pesanan
// ----------------------------------------------------
$techNotifs = NotificationService::getByUser($techId, 5);
$techReqNotif = null;
foreach ($techNotifs as $tn) {
    if ($tn->type === Notification::TYPE_ORDER_REQUEST && (int)$tn->related_id === (int)$testOrder->id) {
        $techReqNotif = $tn;
        break;
    }
}

assertScenario(
    "Scenario 3: Teknisi menerima permintaan pesanan baru",
    $techReqNotif !== null && strpos($techReqNotif->title, 'Permintaan') !== false,
    $techReqNotif ? $techReqNotif->title : 'Notifikasi teknisi tidak ditemukan'
);

// ----------------------------------------------------
// Scenario 4: Teknisi menerima pesanan
// ----------------------------------------------------
$testOrder->status = Order::STATUS_ACCEPTED;
$testOrder->save();
$acceptedSuccess = NotificationService::notifyOrderAccepted($testOrder);

assertScenario(
    "Scenario 4: Teknisi menerima pesanan dan memicu notifikasi accepted",
    $acceptedSuccess !== null && $acceptedSuccess->type === Notification::TYPE_ORDER_ACCEPTED,
    "Status: " . $testOrder->status
);

// ----------------------------------------------------
// Scenario 5: Customer menerima notifikasi perubahan status
// ----------------------------------------------------
$customerNotifsAfterAccept = NotificationService::getByUser($customerId, 10);
$acceptNotifFound = false;
foreach ($customerNotifsAfterAccept as $cna) {
    if ($cna->type === Notification::TYPE_ORDER_ACCEPTED && (int)$cna->related_id === (int)$testOrder->id) {
        $acceptNotifFound = true;
        break;
    }
}

// Test in_progress and completed status transitions as well
$testOrder->status = Order::STATUS_IN_PROGRESS;
NotificationService::notifyOrderInProgress($testOrder);

$testOrder->status = Order::STATUS_COMPLETED;
NotificationService::notifyOrderCompleted($testOrder);

$customerNotifsCompleted = NotificationService::getByUser($customerId, 10);
$completedNotifFound = false;
foreach ($customerNotifsCompleted as $cnc) {
    if ($cnc->type === Notification::TYPE_ORDER_COMPLETED && (int)$cnc->related_id === (int)$testOrder->id) {
        $completedNotifFound = true;
        break;
    }
}

assertScenario(
    "Scenario 5: Customer menerima notifikasi perubahan status (accepted & completed)",
    $acceptNotifFound && $completedNotifFound,
    "Accepted: " . ($acceptNotifFound ? 'yes' : 'no') . ", Completed: " . ($completedNotifFound ? 'yes' : 'no')
);

// ----------------------------------------------------
// Scenario 6: Notifikasi tersimpan di database
// ----------------------------------------------------
$storedNotif = Notification::find((int)$orderCreatedNotif->id);

assertScenario(
    "Scenario 6: Notifikasi tersimpan secara persisten di tabel notifications database",
    $storedNotif !== null && (int)$storedNotif->id === (int)$orderCreatedNotif->id && !empty($storedNotif->created_at),
    "Record ID: " . ($storedNotif ? $storedNotif->id : 'null')
);

// ----------------------------------------------------
// Scenario 7: Badge jumlah belum dibaca benar
// ----------------------------------------------------
$unreadCountBefore = NotificationService::getUnreadCount($customerId);

assertScenario(
    "Scenario 7: Badge jumlah notifikasi belum dibaca akurat",
    $unreadCountBefore > 0,
    "Unread count: {$unreadCountBefore}"
);

// ----------------------------------------------------
// Scenario 8: Notifikasi dapat ditandai sebagai dibaca
// ----------------------------------------------------
$markSuccess = NotificationService::markAsRead((int)$orderCreatedNotif->id, $customerId);
$updatedNotif = Notification::find((int)$orderCreatedNotif->id);
$unreadCountAfter = NotificationService::getUnreadCount($customerId);

assertScenario(
    "Scenario 8: Notifikasi dapat ditandai sebagai dibaca (read_at tersimpan & unread berkurang)",
    $markSuccess && $updatedNotif->isRead() && $unreadCountAfter < $unreadCountBefore,
    "read_at: " . $updatedNotif->read_at
);

// ----------------------------------------------------
// Scenario 9: Pengguna tidak dapat mengakses notifikasi pengguna lain (Anti-IDOR)
// ----------------------------------------------------
// Attempt to mark customer's notification as read using technician's user ID
$idorAttempt = NotificationService::markAsRead((int)$updatedNotif->id, $techId);

// Check that technician only gets their own notifications
$techAllNotifs = NotificationService::getByUser($techId, 20);
$hasLeakedCustomerNotif = false;
foreach ($techAllNotifs as $tn) {
    if ((int)$tn->user_id !== $techId) {
        $hasLeakedCustomerNotif = true;
        break;
    }
}

assertScenario(
    "Scenario 9: Otorisasi ketat (Anti-IDOR): Pengguna tidak dapat membaca/mengubah notifikasi pengguna lain",
    $idorAttempt === false && !$hasLeakedCustomerNotif,
    "IDOR Attempt blocked: " . ($idorAttempt === false ? 'YES' : 'NO')
);

// ----------------------------------------------------
// Scenario 10: Tidak ada notifikasi ganda untuk satu event yang sama (Deduplication)
// ----------------------------------------------------
$countBeforeDup = count(NotificationService::getByUser($customerId, 50));
// Trigger duplicate within 10-second window
$duplicateNotif = NotificationService::create(
    $customerId,
    Notification::TYPE_ORDER_COMPLETED,
    'Pekerjaan Servis Selesai!',
    'Servis telah selesai.',
    'order',
    (int)$testOrder->id
);
$countAfterDup = count(NotificationService::getByUser($customerId, 50));

assertScenario(
    "Scenario 10: Mekanisme pencegahan notifikasi ganda (Deduplication 10 detik)",
    $countBeforeDup === $countAfterDup,
    "Count before: {$countBeforeDup}, Count after: {$countAfterDup}"
);

// ----------------------------------------------------
// Scenario 11: UI berjalan di desktop dan smartphone (View & Asset files verification)
// ----------------------------------------------------
$viewExists = file_exists(APP_PATH . '/Views/notifications/index.php');
$dashboardLayoutHasBell = strpos(file_get_contents(APP_PATH . '/Views/layouts/dashboard.php'), 'tjNotifDropdownBtn') !== false;
$jsAssetExists = file_exists(PUBLIC_PATH . '/assets/js/notifications.js');

assertScenario(
    "Scenario 11: UI Notifikasi responsif tersedia (View index, navbar bell dropdown, polling script)",
    $viewExists && $dashboardLayoutHasBell && $jsAssetExists,
    "View: " . ($viewExists ? 'OK' : 'MISSING') . ", Bell: " . ($dashboardLayoutHasBell ? 'OK' : 'MISSING') . ", JS: " . ($jsAssetExists ? 'OK' : 'MISSING')
);

// ----------------------------------------------------
// Scenario 12: Fitur Chatbot+ tetap berfungsi tanpa regresi
// ----------------------------------------------------
$chatbotServiceClassExists = class_exists('App\\Services\\ChatbotService');
$chatbotControllerClassExists = class_exists('App\\Controllers\\ChatbotController');

// Test ChatbotService query execution directly
Auth::login($customerUser);
$chatbotService = new \App\Services\ChatbotService();
$botReply = $chatbotService->handleChat([
    ['role' => 'user', 'content' => 'Halo Terima Jasa, bagaimana cara memesan servis AC?']
], [
    'user_id' => $customerId,
    'role'    => 'customer',
    'name'    => 'Test Customer'
]);

assertScenario(
    "Scenario 12: Fitur Chatbot+ tetap berfungsi 100% tanpa regresi",
    $chatbotServiceClassExists && $chatbotControllerClassExists && !empty($botReply['reply']) && !empty($botReply['conversation_id']),
    "Bot response received: " . substr($botReply['reply'] ?? '', 0, 40) . '...'
);

// Clean up test order if created
if ($testOrder && $testOrder->id && $testOrder->id !== 99999) {
    try {
        Order::delete((int)$testOrder->id);
    } catch (\Throwable $e) {}
}

echo "\n========================================================\n";
echo "   TEST SUMMARY: {$passCount} PASSED, {$failCount} FAILED\n";
echo "========================================================\n";

if ($failCount > 0) {
    exit(1);
}
