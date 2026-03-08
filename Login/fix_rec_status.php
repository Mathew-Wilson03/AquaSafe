<?php
require_once 'config.php';

echo "--- Initializing REC-001 if missing ---\n";
$check = mysqli_query($link, "SELECT 1 FROM sensor_status WHERE sensor_id = 'REC-001'");
if (mysqli_num_rows($check) == 0) {
    $sql = "INSERT INTO sensor_status (sensor_id, location_name, role, status, water_level, last_ping) 
            VALUES ('REC-001', 'Idukki Gateway', 'Receiver ESP', 'SAFE', 0.00, NOW())";
    if (mysqli_query($link, $sql)) {
        echo "REC-001 successfully initialized.\n";
    } else {
        echo "Error: " . mysqli_error($link) . "\n";
    }
} else {
    echo "REC-001 already exists, updating last_ping...\n";
    mysqli_query($link, "UPDATE sensor_status SET last_ping = NOW() WHERE sensor_id = 'REC-001'");
}

echo "Cleanup: deleting check_rec_status.php\n";
?>
