<?php
require_once 'config.php';
$res = mysqli_query($link, "SELECT * FROM sensor_status WHERE sensor_id = 'SNS-001'");
$row = mysqli_fetch_assoc($res);
print_r($row);
?>
