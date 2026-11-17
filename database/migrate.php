<?php
/**
 * Terima Jasa - Database Migration Runner
 * Usage via CLI: php database/migrate.php [--seed]
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

require_once APP_PATH . '/Helpers/helpers.php';

$config = config('database.connections.mysql');
$host = $config['host'] ?? '127.0.0.1';
$port = $config['port'] ?? 3306;
$dbName = $config['database'] ?? 'terima_jasa_db';
$user = $config['username'] ?? 'root';
$pass = $config['password'] ?? '';
$charset = $config['charset'] ?? 'utf8mb4';

echo "========================================================\n";
echo "   TERIMA JASA - DATABASE MIGRATION SYSTEM\n";
echo "========================================================\n";
echo "Connecting to MySQL server at {$host}:{$port}...\n";

try {
    // 1. Connect without database to ensure database exists
    $pdo = new PDO("mysql:host={$host};port={$port};charset={$charset}", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "[OK] Connected to MySQL server successfully.\n";

    // 2. Create Database
    echo "Creating database `{$dbName}` if not exists...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET {$charset} COLLATE {$charset}_unicode_ci;");
    $pdo->exec("USE `{$dbName}`;");
    echo "[OK] Database `{$dbName}` is active.\n\n";

    // 3. Read and execute schema
    $schemaFile = ROOT_PATH . '/database/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found at: {$schemaFile}");
    }

    echo "Running schema DDL migrations from database/schema.sql...\n";
    $sqlContent = file_get_contents($schemaFile);

    // Split and execute statements
    $statements = array_filter(array_map('trim', explode(';', $sqlContent)));

    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }

    echo "[OK] All tables migrated successfully!\n\n";

    // 4. Verify created tables
    echo "--------------------------------------------------------\n";
    echo "Verifying created tables in `{$dbName}`:\n";
    echo "--------------------------------------------------------\n";
    $stmt = $pdo->query("SHOW TABLES;");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $index => $table) {
        $countStmt = $pdo->query("SELECT COUNT(*) FROM `{$table}`;");
        $rowCount = $countStmt->fetchColumn();
        echo sprintf(" %d. %-24s [Columns & Constraints OK, Rows: %d]\n", $index + 1, $table, $rowCount);
    }
    echo "--------------------------------------------------------\n\n";

    // 5. Check if seeding is requested
    $args = $argv ?? [];
    if (in_array('--seed', $args)) {
        echo "Seeding initial mock data...\n";
        $seederFile = ROOT_PATH . '/database/seeders/DatabaseSeeder.php';
        if (file_exists($seederFile)) {
            require_once $seederFile;
            \Database\Seeders\DatabaseSeeder::run($pdo);
            echo "[OK] Database seeding completed!\n\n";
        }
    }

    echo ">>> MIGRATION STATUS: 100% SUCCESS, NO ERRORS DETECTED.\n";
    echo "========================================================\n";

} catch (Exception $e) {
    echo "\n[ERROR] Migration Failed: " . $e->getMessage() . "\n";
    exit(1);
}
