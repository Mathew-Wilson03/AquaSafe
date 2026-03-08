<?php
require_once 'config.php';
$res = mysqli_query($link, "SHOW COLUMNS FROM sensor_status");
while($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . "\n";
}
?>
