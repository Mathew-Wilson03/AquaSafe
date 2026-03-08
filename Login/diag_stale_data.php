<?php
require_once 'config.php';
header('Content-Type: text/plain');

echo "Current Server Time (PHP): " . date('Y-m-d H:i:s') . "\n";
$resTime = mysqli_query($link, "SELECT NOW() as now");
$rowTime = mysqli_fetch_assoc($resTime);
echo "Current Database Time (MySQL): " . $rowTime['now'] . "\n";

echo "\n--- User Info for 'Mathew Wills' (mathew.akashwillson@gmail.com) ---\n";
$resUser = mysqli_query($link, "SELECT id, location, email FROM users WHERE email = 'mathew.akashwillson@gmail.com'");
$user = mysqli_fetch_assoc($resUser);
if ($user) {
    print_r($user);
} else {
    echo "User not found.\n";
}

echo "\n--- Latest 5 Global Flood Data Entries ---\n";
$resFlood = mysqli_query($link, "SELECT * FROM flood_data ORDER BY id DESC LIMIT 5");
while($row = mysqli_fetch_assoc($resFlood)) {
    print_r($row);
}

echo "\n--- Latest 5 Sensor Status Updates ---\n";
$resSens = mysqli_query($link, "SELECT sensor_id, location_name, last_ping, status FROM sensor_status");
while($row = mysqli_fetch_assoc($resSens)) {
    print_r($row);
}
?>
