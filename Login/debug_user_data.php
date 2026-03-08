<?php
require_once 'config.php';
session_start();

$user_id = $_SESSION['id'] ?? 0;
echo "User ID: $user_id\n";

if ($user_id) {
    $res = mysqli_query($link, "SELECT email, location FROM users WHERE id = $user_id");
    $user = mysqli_fetch_assoc($res);
    echo "User Email: " . $user['email'] . "\n";
    echo "User Location: " . $user['location'] . "\n";
} else {
    echo "No User ID in session.\n";
}

echo "\n--- Latest 10 Flood Data Entries ---\n";
$res = mysqli_query($link, "SELECT * FROM flood_data ORDER BY created_at DESC LIMIT 10");
while($row = mysqli_fetch_assoc($res)) {
    echo "[" . $row['created_at'] . "] Location: " . $row['location'] . " | Level: " . $row['level'] . " | Status: " . $row['status'] . "\n";
}

echo "\n--- Recent Admin Alerts (sensor_alerts) ---\n";
$res = mysqli_query($link, "SELECT * FROM sensor_alerts ORDER BY timestamp DESC LIMIT 5");
while($row = mysqli_fetch_assoc($res)) {
    echo "[" . $row['timestamp'] . "] Type: " . $row['alert_type'] . " | Message: " . $row['message'] . "\n";
}
?>
