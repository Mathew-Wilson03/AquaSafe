<?php
require_once 'config.php';
$res = mysqli_query($link, "SELECT * FROM sensor_alerts ORDER BY id DESC LIMIT 10");
$alerts = [];
while($row = mysqli_fetch_assoc($res)) {
    $alerts[] = $row;
}
echo json_encode($alerts);
?>
