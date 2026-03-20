<?php
require 'Login/config.php';

echo "--- RECENT FLOOD DATA ---\n";
$res = mysqli_query($link, "SELECT * FROM flood_data ORDER BY created_at DESC LIMIT 5");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}

echo "\n--- SENSOR STATUS ---\n";
$res = mysqli_query($link, "SELECT * FROM sensor_status");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}

echo "\n--- USERS ---\n";
$res = mysqli_query($link, "SELECT id, email, name, location FROM users");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>
