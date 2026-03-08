<?php
require_once 'config.php';

echo "Current Server Time: " . date('Y-m-d H:i:s') . "\n";
echo "Current Date: " . date('Y-m-d') . "\n";

echo "\n--- REC-001 Status ---\n";
$res = mysqli_query($link, "SELECT * FROM sensor_status WHERE sensor_id = 'REC-001'");
if (mysqli_num_rows($res) > 0) {
    print_r(mysqli_fetch_assoc($res));
} else {
    echo "REC-001 not found in sensor_status!\n";
}

echo "\n--- Recent Flood Data (Today) ---\n";
$today = date('Y-m-d');
$res = mysqli_query($link, "SELECT * FROM flood_data WHERE DATE(created_at) = '$today' ORDER BY created_at DESC LIMIT 5");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}

echo "\n--- Daily Peak Query Check ---\n";
$peak_query = "SELECT MAX(level) as peak FROM flood_data WHERE DATE(created_at) = '$today'";
$peak_res = mysqli_query($link, $peak_query);
$peak_val = mysqli_fetch_assoc($peak_res)['peak'];
echo "Calculated Peak for Today: " . ($peak_val ?? 'NULL') . "\n";

?>
