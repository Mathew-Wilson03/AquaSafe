<?php
require_once 'c:\xampp\htdocs\AquaSafe\Login\config.php';
$res = mysqli_query($link, "DESCRIBE flood_data");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
echo "\n--- Alerts Schema ---\n";
$res = mysqli_query($link, "DESCRIBE sensor_alerts");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
