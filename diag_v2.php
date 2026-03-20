<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'aquasafe';
$port = 3306;

$link = mysqli_connect($host, $user, $pass, $db, $port);

if (!$link) {
    die("Connection failed: " . mysqli_connect_error() . "\n");
}

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
