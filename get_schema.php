<?php
require_once 'Login/config.php';
$res = mysqli_query($link, 'DESC sensor_alerts');
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>
