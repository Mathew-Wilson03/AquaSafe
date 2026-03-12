<?php
require_once 'config.php';
echo "DB Server: " . DB_SERVER . "\n";
if ($link) {
    echo "Connection OK\n";
} else {
    echo "Connection Failed: " . mysqli_connect_error() . "\n";
}
?>
