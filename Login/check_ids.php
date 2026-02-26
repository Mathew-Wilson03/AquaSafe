<?php
require_once 'config.php';
$res = mysqli_query($link, "SELECT id, severity, alert_type, timestamp FROM sensor_alerts ORDER BY id DESC LIMIT 50");
$data = [];
while($row = mysqli_fetch_assoc($res)) {
    $data[] = $row;
}
echo json_encode($data);
?>
