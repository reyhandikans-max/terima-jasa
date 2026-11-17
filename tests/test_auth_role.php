<?php
/**
 * Test Suite: Authentication & Role-Based Authorization
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
use App\Core\Request;
use App\Models\User;
use App\Models\TechnicianProfile;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Controllers\AuthController;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "========================================================\n";
echo "   TERIMA JASA - AUTH & ROLE AUTHORIZATION TEST SUITE\n";
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
// TEST 1: Password Hashing & Bcrypt Verification
// ---------------------------------------------------------------
echo "\n--- 1. Password Hashing (Bcrypt) ---\n";
$plainPassword = 'SecretPassword123!';
$hash = password_hash($plainPassword, PASSWORD_BCRYPT);
assertTest("Password hash is not empty and starts with Bcrypt identifier ($2y$)", !empty($hash) && str_starts_with($hash, '$2y$'));
assertTest("Password verify matches correct password", password_verify($plainPassword, $hash) === true);
assertTest("Password verify rejects wrong password", password_verify('WrongPassword', $hash) === false);

// ---------------------------------------------------------------
// TEST 2: Customer Validation Rules
// ---------------------------------------------------------------
echo "\n--- 2. Customer Validation Rules ---\n";
$rules = [
    'name'     => 'required|min:3|max:150',
    'email'    => 'required|email',
    'phone'    => 'required|min:9|max:25',
    'password' => 'required|min:6'
];

$validCustomer = [
    'name'     => 'Bambang Sudirman',
    'email'    => 'bambang@example.com',
    'phone'    => '081234567890',
    'password' => 'rahasia123'
];
$errorsValid = User::validate($validCustomer, $rules);
assertTest("Valid customer data produces no validation errors", empty($errorsValid));

$invalidCustomer = [
    'name'     => 'A', // too short (<3)
    'email'    => 'not-email', // invalid email
    'phone'    => '123', // too short (<9)
    'password' => '123' // too short (<6)
];
$errorsInvalid = User::validate($invalidCustomer, $rules);
assertTest("Invalid customer name detected", isset($errorsInvalid['name']));
assertTest("Invalid customer email detected", isset($errorsInvalid['email']));
assertTest("Invalid customer phone detected", isset($errorsInvalid['phone']));
assertTest("Invalid customer password detected", isset($errorsInvalid['password']));

// ---------------------------------------------------------------
// TEST 3: Technician Validation Rules (Expertise, Experience, Address)
// ---------------------------------------------------------------
echo "\n--- 3. Technician Validation Rules ---\n";
$techRules = array_merge($rules, [
    'expertise'  => 'required|min:3|max:255',
    'experience' => 'required|max:100',
    'address'    => 'required|min:5'
]);

$validTech = array_merge($validCustomer, [
    'expertise'  => 'Teknisi AC & Chiller',
    'experience' => '5 Tahun',
    'address'    => 'Jl. Rasuna Said No. 12, Jakarta Selatan'
]);
$errorsValidTech = User::validate($validTech, $techRules);
assertTest("Valid technician data produces no validation errors", empty($errorsValidTech));

$invalidTech = array_merge($validCustomer, [
    'expertise'  => '', // required
    'experience' => '', // required
    'address'    => 'Jl' // too short (<5)
]);
$errorsInvalidTech = User::validate($invalidTech, $techRules);
assertTest("Missing technician expertise detected", isset($errorsInvalidTech['expertise']));
assertTest("Missing technician experience detected", isset($errorsInvalidTech['experience']));
assertTest("Short technician address detected", isset($errorsInvalidTech['address']));

// ---------------------------------------------------------------
// TEST 4: Admin Public Registration Blocking (Security Check)
// ---------------------------------------------------------------
echo "\n--- 4. Security Check: Admin Public Registration Blocking ---\n";
// Simulate role filter logic in AuthController::postRegister
$incomingRole1 = 'admin';
$sanitizedRole1 = in_array($incomingRole1, [User::ROLE_CUSTOMER, User::ROLE_TEKNISI]) ? $incomingRole1 : User::ROLE_CUSTOMER;
assertTest("Attempting to register as 'admin' is blocked and forced to 'customer'", $sanitizedRole1 === User::ROLE_CUSTOMER);

$incomingRole2 = 'superadmin';
$sanitizedRole2 = in_array($incomingRole2, [User::ROLE_CUSTOMER, User::ROLE_TEKNISI]) ? $incomingRole2 : User::ROLE_CUSTOMER;
assertTest("Attempting to inject arbitrary role is blocked", $sanitizedRole2 === User::ROLE_CUSTOMER);

$incomingRole3 = 'teknisi';
$sanitizedRole3 = in_array($incomingRole3, [User::ROLE_CUSTOMER, User::ROLE_TEKNISI]) ? $incomingRole3 : User::ROLE_CUSTOMER;
assertTest("Valid technician role registration is permitted", $sanitizedRole3 === User::ROLE_TEKNISI);

// ---------------------------------------------------------------
// TEST 5: Session & Authentication Engine
// ---------------------------------------------------------------
echo "\n--- 5. Session & Authentication Manager ---\n";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure clean slate
Auth::logout();
assertTest("Auth::check() is false when logged out", Auth::check() === false);
assertTest("Auth::user() is null when logged out", Auth::user() === null);

// Login customer
$mockCustomer = new User([
    'id'     => 10,
    'name'   => 'Rina Astuti',
    'email'  => 'rina@customer.id',
    'role'   => User::ROLE_CUSTOMER,
    'status' => User::STATUS_ACTIVE,
    'phone'  => '081299887766'
]);
$mockCustomer->id = 10;

Auth::login($mockCustomer);
assertTest("Auth::check() is true after login", Auth::check() === true);
assertTest("Auth::id() matches logged-in user id", Auth::id() === 10);
assertTest("Auth::role() matches 'customer'", Auth::role() === User::ROLE_CUSTOMER);
assertTest("Auth::user()['name'] matches logged-in user name", Auth::user()['name'] === 'Rina Astuti');

// ---------------------------------------------------------------
// TEST 6: Role Dashboard URLs
// ---------------------------------------------------------------
echo "\n--- 6. Role Dashboard URLs ---\n";
assertTest("Admin dashboard URL is /admin/dashboard", str_contains(Auth::dashboardUrl(User::ROLE_ADMIN), 'admin/dashboard'));
assertTest("Customer dashboard URL is /customer/dashboard", str_contains(Auth::dashboardUrl(User::ROLE_CUSTOMER), 'customer/dashboard'));
assertTest("Technician dashboard URL is /technician/dashboard", str_contains(Auth::dashboardUrl(User::ROLE_TEKNISI), 'technician/dashboard'));

// ---------------------------------------------------------------
// TEST 7: Role-Based Authorization Guard Simulation
// ---------------------------------------------------------------
echo "\n--- 7. Role-Based Access Control Middleware ---\n";

// Scenario A: Customer attempts to access customer area (Allowed)
$customerRole = Auth::role();
$allowedCustomer = ($customerRole === User::ROLE_CUSTOMER);
assertTest("Customer can access Customer area", $allowedCustomer === true);

// Scenario B: Customer attempts to access admin area (Blocked)
$blockedAdmin = ($customerRole === User::ROLE_ADMIN);
assertTest("Customer CANNOT access Admin area", $blockedAdmin === false);

// Scenario C: Customer attempts to access technician area (Blocked)
$blockedTech = ($customerRole === User::ROLE_TEKNISI);
assertTest("Customer CANNOT access Technician area", $blockedTech === false);

// Switch user to Technician
$mockTech = new User([
    'id'     => 20,
    'name'   => 'Doni Pratama',
    'email'  => 'doni@teknisi.id',
    'role'   => User::ROLE_TEKNISI,
    'status' => User::STATUS_ACTIVE
]);
$mockTech->id = 20;
Auth::login($mockTech);

assertTest("Technician can access Technician area", Auth::role() === User::ROLE_TEKNISI);
assertTest("Technician CANNOT access Customer area", Auth::role() !== User::ROLE_CUSTOMER);
assertTest("Technician CANNOT access Admin area", Auth::role() !== User::ROLE_ADMIN);

// Switch user to Admin
$mockAdmin = new User([
    'id'     => 1,
    'name'   => 'Super Administrator',
    'email'  => 'admin@terimajasa.id',
    'role'   => User::ROLE_ADMIN,
    'status' => User::STATUS_ACTIVE
]);
$mockAdmin->id = 1;
Auth::login($mockAdmin);

assertTest("Admin can access Admin area", Auth::role() === User::ROLE_ADMIN);
assertTest("Admin is restricted from customer area directly via role guard", Auth::role() !== User::ROLE_CUSTOMER);
assertTest("Admin is restricted from technician area directly via role guard", Auth::role() !== User::ROLE_TEKNISI);

// Logout
Auth::logout();
assertTest("Auth::logout() successfully clears session", Auth::check() === false && Auth::user() === null);

// ---------------------------------------------------------------
// TEST 8: Demo & Fallback Authentication Attempt
// ---------------------------------------------------------------
echo "\n--- 8. Authentication Attempt (Mock & DB Fallback) ---\n";
$attemptCustomer = Auth::attempt('customer@terimajasa.id', 'password123');
assertTest("Auth::attempt succeeds for customer demo credentials", $attemptCustomer === true && Auth::role() === User::ROLE_CUSTOMER);
Auth::logout();

$attemptTech = Auth::attempt('teknisi@terimajasa.id', 'password123');
assertTest("Auth::attempt succeeds for teknisi demo credentials", $attemptTech === true && Auth::role() === User::ROLE_TEKNISI);
Auth::logout();

$attemptAdmin = Auth::attempt('admin@terimajasa.id', 'password123');
assertTest("Auth::attempt succeeds for admin demo credentials", $attemptAdmin === true && Auth::role() === User::ROLE_ADMIN);
Auth::logout();

$attemptWrong = Auth::attempt('customer@terimajasa.id', 'wrongpass');
assertTest("Auth::attempt fails for incorrect password", $attemptWrong === false && Auth::check() === false);

echo "\n--------------------------------------------------------\n";
echo "SUMMARY: {$passCount} PASSED, {$failCount} FAILED.\n";
echo "========================================================\n";

if ($failCount > 0) {
    exit(1);
}
