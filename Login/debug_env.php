<?php
require_once 'config.php';

echo "Database Configuration Debug:\n";
echo "DB_SERVER: " . DB_SERVER . "\n";
echo "DB_USERNAME: " . DB_USERNAME . "\n";
echo "DB_NAME: " . DB_NAME . "\n";
echo "DB_PORT: " . DB_PORT . "\n";
echo "DB_PASSWORD (length): " . strlen(DB_PASSWORD) . "\n";

echo "\nRaw Env Check:\n";
echo "DB_HOST from getenv: " . getenv('DB_HOST') . "\n";
echo "DB_HOST from \$_SERVER: " . ($_SERVER['DB_HOST'] ?? 'not set') . "\n";
?>
