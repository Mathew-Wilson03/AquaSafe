<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting test...<br>";

if (file_exists('config.php')) {
    echo "config.php found.<br>";
    require_once 'config.php';
    echo "config.php included successfully.<br>";
} else {
    echo "config.php NOT found.<br>";
}

if (defined('DB_SERVER')) {
    echo "DB_SERVER defined: " . DB_SERVER . "<br>";
}

if (isset($link)) {
    echo "Database connection variable \$link is set.<br>";
    if ($link) {
        echo "Database connection successful.<br>";
    } else {
        echo "Database connection failed.<br>";
    }
} else {
    echo "\$link variable not set.<br>";
}

echo "Test complete.";
?>
