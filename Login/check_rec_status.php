<?php
require_once 'config.php';

echo "--- Current sensor_status for REC-001 ---\n";
$res = mysqli_query($link, "SELECT *, TIMESTAMPDIFF(SECOND, last_ping, NOW()) as seconds_since_ping FROM sensor_status WHERE sensor_id = 'REC-001'");
if ($row = mysqli_fetch_assoc($res)) {
    print_r($row);
} else {
    echo "REC-001 NOT FOUND in sensor_status table!\n";
}

echo "\n--- Recent flood_data entries ---\n";
$res = mysqli_query($link, "SELECT * FROM flood_data ORDER BY created_at DESC LIMIT 3");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>
