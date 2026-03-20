<?php
require_once 'config.php';
$res = mysqli_query($link, "SELECT COUNT(*) as count FROM flood_data WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)");
$row = mysqli_fetch_assoc($res);
echo "New rows in last minute: " . $row['count'] . "\n";
?>
