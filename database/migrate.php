<?php

require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register(__DIR__ . '/../app');

use App\Core\Env;
use App\Core\Logger;

// Load .env
\App\Core\Env::load(__DIR__ . '/../.env');

$host = Env::get('DB_HOST', '127.0.0.1');
$port = Env::get('DB_PORT', '3306');
$database = Env::get('DB_DATABASE', 'qfunnel_db');
$username = Env::get('DB_USERNAME', 'root');
$password = Env::get('DB_PASSWORD', '');
$charset = 'utf8mb4';

echo "=== Quiz Funnel SaaS Migration Runner ===\n";
echo "Host: {$host}:{$port}\n";
echo "Database: {$database}\n\n";

try {
    // 1. Connect without db to ensure database exists
    $rootPdo = new PDO("mysql:host={$host};port={$port};charset={$charset}", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    echo "[✓] Database '{$database}' verified/created successfully.\n";

    // 2. Connect to the target database
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$database};charset={$charset}", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Ensure migrations table exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `migrations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `migration` VARCHAR(255) NOT NULL UNIQUE,
            `executed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $executed = $pdo->query("SELECT migration FROM migrations")->fetchAll(PDO::FETCH_COLUMN);

    $migrationFiles = glob(__DIR__ . '/migrations/*.sql');
    sort($migrationFiles);

    $count = 0;
    foreach ($migrationFiles as $file) {
        $name = basename($file);
        if (in_array($name, $executed, true)) {
            echo "[·] Skipping already executed: {$name}\n";
            continue;
        }

        echo "[>] Executing migration: {$name}... ";
        $sql = file_get_contents($file);

        try {
            $pdo->exec($sql);
            $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
            $stmt->execute([$name]);
            echo "DONE.\n";
            $count++;
        } catch (\Throwable $e) {
            echo "FAILED!\n";
            echo "Error in {$name}: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    echo "\n[✓] Migrations finished. Total newly applied: {$count}.\n";
} catch (\Throwable $e) {
    echo "\n[✗] Fatal Migration Error: " . $e->getMessage() . "\n";
    exit(1);
}
