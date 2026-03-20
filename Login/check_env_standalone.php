<?php
// Standalone check for .env loading
require_once __DIR__ . '/vendor/autoload.php';

echo "Testing .env loading...\n";

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    echo ".env loaded successfully.\n";
} catch (\Exception $e) {
    echo "Error loading .env: " . $e->getMessage() . "\n";
}

echo "DB_HOST from \$_ENV: " . ($_ENV['DB_HOST'] ?? 'not set') . "\n";
echo "DB_HOST from \$_SERVER: " . ($_SERVER['DB_HOST'] ?? 'not set') . "\n";
echo "MYSQLHOST from \$_ENV: " . ($_ENV['MYSQLHOST'] ?? 'not set') . "\n";

echo "\nFull \$_ENV contents (keys only):\n";
print_r(array_keys($_ENV));
?>
