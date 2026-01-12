<?php
require_once 'config.php';

// Create sensor_alerts table
$sql = "CREATE TABLE IF NOT EXISTS sensor_alerts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    severity VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    location VARCHAR(100) DEFAULT 'System Wide',
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($link, $sql)) {
    echo "✅ Table 'sensor_alerts' ready\n";
} else {
    echo "Error: " . mysqli_error($link) . "\n";
}

// Create sensor_status table (for fetches)
$sql2 = "CREATE TABLE IF NOT EXISTS sensor_status (
    sensor_id VARCHAR(20) PRIMARY KEY,
    location_name VARCHAR(100),
    status VARCHAR(20),
    battery_level INT,
    last_ping TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($link, $sql2)) {
    echo "✅ Table 'sensor_status' ready\n";
    
    // Insert dummy data if empty
    $check = mysqli_query($link, "SELECT COUNT(*) as c FROM sensor_status");
    $row = mysqli_fetch_assoc($check);
    if ($row['c'] == 0) {
        $sql3 = "INSERT INTO sensor_status (sensor_id, location_name, status, battery_level) VALUES 
        ('SNS-001', 'Nellimala', 'Active', 98),
        ('SNS-002', 'Churakullam', 'Offline', 0),
        ('SNS-003', 'Kakkikavala', 'Maintenance', 45)";
        mysqli_query($link, $sql3);
        echo "✅ Dummy sensor data inserted\n";
    }
} else {
    echo "Error: " . mysqli_error($link) . "\n";
}

mysqli_close($link);
?>
