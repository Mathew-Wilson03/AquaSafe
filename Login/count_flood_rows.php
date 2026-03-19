<?php
require_once 'config.php';
$res = mysqli_query($link, "SELECT COUNT(*) as count FROM flood_data");
$row = mysqli_fetch_assoc($res);
echo "Flood Data Count: " . $row['count'] . "\n";
?>
