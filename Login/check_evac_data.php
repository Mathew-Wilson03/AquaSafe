<?php
require_once 'config.php';
echo "--- Evacuation Points Data ---\n";
$res = mysqli_query($link, "SELECT id, name, location, latitude, longitude FROM evacuation_points");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>
