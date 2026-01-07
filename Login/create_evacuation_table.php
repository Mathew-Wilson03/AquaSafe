<?php
require_once 'config.php';

$sql = "CREATE TABLE IF NOT EXISTS evacuation_points (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    location VARCHAR(255) NOT NULL,
    latitude DECIMAL(10, 8) DEFAULT 0.0,
    longitude DECIMAL(11, 8) DEFAULT 0.0,
    capacity INT(11) NOT NULL,
    status VARCHAR(50) DEFAULT 'Available',
    assigned_sensor VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (mysqli_query($link, $sql)) {
    echo "Table 'evacuation_points' created or already exists successfully.\n";
} else {
    echo "Error creating table: " . mysqli_error($link) . "\n";
}

mysqli_close($link);
?>
