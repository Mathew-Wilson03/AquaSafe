<?php
require_once 'config.php';

echo "--- Initializing REC-001 in sensor_status ---\n";
// The previous attempt failed because 'role' column doesn't exist. 
// Valid columns: id, sensor_id, location_name, latitude, longitude, status, water_level, battery_level, last_ping

$check = mysqli_query($link, "SELECT 1 FROM sensor_status WHERE sensor_id = 'REC-001'");
if (mysqli_num_rows($check) == 0) {
    $sql = "INSERT INTO sensor_status (sensor_id, location_name, status, water_level, last_ping) 
            VALUES ('REC-001', 'Idukki Gateway', 'SAFE', 0.00, NOW())";
    if (mysqli_query($link, $sql)) {
        echo "REC-001 successfully initialized.\n";
    } else {
        echo "Error: " . mysqli_error($link) . "\n";
    }
} else {
    echo "REC-001 already exists, updating last_ping...\n";
    mysqli_query($link, "UPDATE sensor_status SET last_ping = NOW() WHERE sensor_id = 'REC-001'");
}
?>
