<?php
require_once 'config.php';
$res = mysqli_query($link, 'SELECT * FROM sensor_status');
while($row = mysqli_fetch_assoc($res)) {
    echo $row['sensor_id'] . ' : ' . $row['location_name'] . ' : ' . $row['status'] . "\n";
}
?>
