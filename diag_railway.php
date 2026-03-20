<?php
require 'Login/config.php';

echo "--- DB CONFIG ---\n";
echo "Host: " . DB_SERVER . "\n";
echo "Port: " . DB_PORT . "\n";
echo "User: " . DB_USERNAME . "\n";
echo "DB: " . DB_NAME . "\n\n";

echo "--- RECENT FLOOD DATA ---\n";
$res = mysqli_query($link, "SELECT * FROM flood_data ORDER BY created_at DESC LIMIT 10");
while($row = mysqli_fetch_assoc($res)) {
    echo "ID: {$row['id']} | Sensor: {$row['sensor_id']} | Level: {$row['level']} | Location: {$row['location']} | Status: {$row['status']} | Created: {$row['created_at']}\n";
}

echo "\n--- SENSOR STATUS ---\n";
$res = mysqli_query($link, "SELECT sensor_id, location_name, status, last_ping FROM sensor_status");
while($row = mysqli_fetch_assoc($res)) {
    echo "Sensor: {$row['sensor_id']} | Location: {$row['location_name']} | Status: {$row['status']} | Last Ping: {$row['last_ping']}\n";
}

echo "\n--- CURRENT LOGGED IN USER (SESSION) ---\n";
// Since this is CLI, session won't have data unless I mock it or check the DB for the user the user is likely logged in as.
// I'll check all users to see their locations.
echo "\n--- ALL USERS ---\n";
$res = mysqli_query($link, "SELECT id, email, name, location FROM users");
while($row = mysqli_fetch_assoc($res)) {
    echo "ID: {$row['id']} | Name: {$row['name']} | Email: {$row['email']} | Location: {$row['location']}\n";
}
?>
