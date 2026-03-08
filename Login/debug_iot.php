<?php
require 'config.php';
$res = mysqli_query($link, 'SELECT id, sensor_id, location, level, status, created_at FROM flood_data ORDER BY created_at DESC LIMIT 5');
if ($res) {
    echo "LAST 5 READINGS:\n";
    while ($row = mysqli_fetch_assoc($res)) {
        echo "ID: {$row['id']} | SENSOR: {$row['sensor_id']} | LOC: {$row['location']} | LVL: {$row['level']} | STATUS: {$row['status']} | TIME: {$row['created_at']}\n";
    }
} else {
    echo "ERROR: " . mysqli_error($link);
}
?>
