<?php
require_once 'config.php';
$now_res = mysqli_query($link, "SELECT NOW() as db_time");
$now_row = mysqli_fetch_assoc($now_res);
echo "DB Current Time: " . $now_row['db_time'] . "\n";

$res = mysqli_query($link, "SELECT sensor_id, water_level, location_name, last_ping FROM sensor_status ORDER BY last_ping DESC LIMIT 5");
echo "Latest Sensor Status:\n";
while($row = mysqli_fetch_assoc($res)) {
    echo "Sensor: {$row['sensor_id']} | Level: {$row['water_level']} | Location: {$row['location_name']} | Last Ping: {$row['last_ping']}\n";
}
?>
