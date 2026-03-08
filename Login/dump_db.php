<?php
require_once 'config.php';
$res = mysqli_query($link, 'SELECT * FROM sensor_status');
while($r = mysqli_fetch_assoc($res)) {
    echo json_encode($r) . PHP_EOL;
}
?>
