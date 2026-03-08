<?php
require_once 'config.php';

echo "--- Recent Flood Data (Latest 5) ---\n";
$res = mysqli_query($link, "SELECT * FROM flood_data ORDER BY created_at DESC LIMIT 5");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}

echo "\n--- Sensor Status (All) ---\n";
$res = mysqli_query($link, "SELECT sensor_id, location_name, last_ping, water_level FROM sensor_status");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>
