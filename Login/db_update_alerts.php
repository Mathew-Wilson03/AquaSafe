<?php
require_once 'config.php';

echo "--- Starting Database Schema Updates ---\n";

// 1. Add sensor_id to flood_data
$check_col = mysqli_query($link, "SHOW COLUMNS FROM `flood_data` LIKE 'sensor_id'");
if (mysqli_num_rows($check_col) == 0) {
    echo "Adding 'sensor_id' to 'flood_data'...\n";
    mysqli_query($link, "ALTER TABLE `flood_data` ADD COLUMN `sensor_id` INT DEFAULT 1 AFTER `id` ");
}

// 2. Create sensor_locations table
echo "Creating/Checking 'sensor_locations' table...\n";
$sql_loc = "CREATE TABLE IF NOT EXISTS `sensor_locations` (
    `sensor_id` INT PRIMARY KEY,
    `location_name` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($link, $sql_loc);

// Seed sensor ID 1
$res = mysqli_query($link, "SELECT * FROM `sensor_locations` WHERE `sensor_id` = 1");
if (mysqli_num_rows($res) == 0) {
    echo "Seeding Sensor ID 1 mapping...\n";
    $stmt = mysqli_prepare($link, "INSERT INTO `sensor_locations` (sensor_id, location_name) VALUES (1, ?)");
    $name = "Churakullam, Kakkikavala, & Nellimala";
    mysqli_stmt_bind_param($stmt, "s", $name);
    mysqli_stmt_execute($stmt);
}

// 3. Create alert_history table (Cooldown)
echo "Creating/Checking 'alert_history' table...\n";
$sql_hist = "CREATE TABLE IF NOT EXISTS `alert_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sensor_id` INT NOT NULL,
    `status` VARCHAR(20) NOT NULL,
    `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($link, $sql_hist);

echo "--- Database Schema Updates Completed ---\n";
?>
