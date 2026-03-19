<?php
/**
 * AquaSafe - Emergency Receiver Fix
 * Visit this script once to force REC-001 to Active status.
 */
require_once 'config.php';

echo "<h2>Fixing Receiver Status...</h2>";

// 1. Ensure REC-001 exists with correct role
$check = mysqli_query($link, "SELECT * FROM sensor_status WHERE sensor_id = 'REC-001'");
if (mysqli_num_rows($check) == 0) {
    $sql = "INSERT INTO sensor_status (sensor_id, location_name, status, water_level, last_ping) 
            VALUES ('REC-001', 'Idukki Gateway', 'Active', 0.00, NOW())";
    mysqli_query($link, $sql);
    echo "<p style='color:green;'>REC-001 created and set to Active.</p>";
} else {
    mysqli_query($link, "UPDATE sensor_status SET last_ping = NOW(), status = 'Active', location_name = 'Idukki Gateway' WHERE sensor_id = 'REC-001'");
    echo "<p style='color:green;'>REC-001 status forced to Active.</p>";
}

// 2. Fix the status column length if needed
mysqli_query($link, "ALTER TABLE sensor_status MODIFY COLUMN status VARCHAR(32) DEFAULT 'Active'");
mysqli_query($link, "ALTER TABLE flood_data MODIFY COLUMN status VARCHAR(32) DEFAULT 'SAFE'");

echo "<p>Done. Refresh your Admin Dashboard now.</p>";
?>
