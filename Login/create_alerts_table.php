<?php
require_once 'config.php';

// 1. Create sensor_status table
$sql1 = "CREATE TABLE IF NOT EXISTS sensor_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sensor_id VARCHAR(50) UNIQUE NOT NULL,
    location_name VARCHAR(100) NOT NULL,
    status ENUM('Active', 'Offline', 'Maintenance') DEFAULT 'Active',
    water_level DECIMAL(5,2) DEFAULT 0.00,
    battery_level INT DEFAULT 100,
    last_ping TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (mysqli_query($link, $sql1)) {
    echo "Table 'sensor_status' created successfully.\n";
} else {
    echo "Error creating table 'sensor_status': " . mysqli_error($link) . "\n";
}

// 2. Create sensor_alerts table
$sql2 = "CREATE TABLE IF NOT EXISTS sensor_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sensor_id VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    severity ENUM('Safe', 'Warning', 'Critical') DEFAULT 'Safe',
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read TINYINT(1) DEFAULT 0
)";

if (mysqli_query($link, $sql2)) {
    echo "Table 'sensor_alerts' created successfully.\n";
} else {
    echo "Error creating table 'sensor_alerts': " . mysqli_error($link) . "\n";
}

// 3. Seed initial data if empty
$check = mysqli_query($link, "SELECT COUNT(*) FROM sensor_status");
$count = mysqli_fetch_row($check)[0];

if ($count == 0) {
    $seed = "INSERT INTO sensor_status (sensor_id, location_name, status, water_level, battery_level) VALUES 
        ('SNS-001', 'Nellimala', 'Active', 1.5, 98),
        ('SNS-002', 'Churakullam', 'Offline', 0.0, 0),
        ('SNS-003', 'Kakkikavala', 'Maintenance', 0.8, 45),
        ('SNS-004', 'South Reservoir', 'Active', 3.2, 92),
        ('SNS-005', 'North Zone', 'Active', 1.2, 85)";
    
    if (mysqli_query($link, $seed)) {
        echo "Initial sensor data seeded.\n";
    }

    $seed_alerts = "INSERT INTO sensor_alerts (sensor_id, message, severity, timestamp) VALUES 
        ('SNS-004', 'South Reservoir exceeded 95% capacity. Automated drainage sequence initiated.', 'Critical', NOW()),
        ('SNS-003', 'Sensor SNS-003 reporting intermittent signal loss.', 'Warning', NOW())";
    
    if (mysqli_query($link, $seed_alerts)) {
        echo "Initial alert data seeded.\n";
    }
} else {
    echo "Tables already contain data, skipping seed.\n";
}
?>
