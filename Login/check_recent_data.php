<?php
require_once 'config.php';
$now_res = mysqli_query($link, "SELECT NOW() as db_time");
$now_row = mysqli_fetch_assoc($now_res);
echo "DB Current Time: " . $now_row['db_time'] . "\n";

$res = mysqli_query($link, "SELECT id, sensor_id, level, location, created_at FROM flood_data ORDER BY created_at DESC LIMIT 5");
echo "Latest Records:\n";
while($row = mysqli_fetch_assoc($res)) {
    echo "ID: {$row['id']} | Sensor: {$row['sensor_id']} | Level: {$row['level']} | Location: {$row['location']} | Created: {$row['created_at']}\n";
}
?>
