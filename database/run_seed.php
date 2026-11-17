<?php
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

require_once APP_PATH . '/Helpers/helpers.php';
require_once ROOT_PATH . '/database/seeders/DatabaseSeeder.php';

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

use App\Core\Model;
use Database\Seeders\DatabaseSeeder;

$pdo = Model::getDb();
if ($pdo) {
    echo "Connected to MySQL. Running DatabaseSeeder...\n";
    DatabaseSeeder::run($pdo);
    echo "DatabaseSeeder completed successfully!\n";
} else {
    echo "Could not connect to MySQL database.\n";
}
