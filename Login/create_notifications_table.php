<?php
require_once 'config.php';

$sql = "CREATE TABLE IF NOT EXISTS system_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('flood', 'device', 'system') NOT NULL,
    severity ENUM('safe', 'warning', 'danger', 'critical', 'info') NOT NULL,
    location VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    water_level DECIMAL(5,2) DEFAULT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($link, $sql)) {
    echo "Table 'system_notifications' created or already exists.\n";
} else {
    echo "Error creating table: " . mysqli_error($link) . "\n";
}
?>
