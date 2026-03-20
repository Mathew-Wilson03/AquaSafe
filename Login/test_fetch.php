<?php
require_once 'config.php';
$user_location = 'System Wide'; // Simulating default user
$loc_esc = mysqli_real_escape_string($link, $user_location);
$where_cond = "1=1";
if ($user_location !== 'System Wide' && !empty($user_location)) {
    $where_cond = "(location = '$loc_esc' OR location = 'System Wide' OR location = 'Unknown Cluster')";
}
$iot_sql = "SELECT sensor_id, level, status, location, created_at FROM flood_data WHERE $where_cond ORDER BY created_at DESC LIMIT 20";
$iot_res = mysqli_query($link, $iot_sql);
$readings = [];
while ($row = mysqli_fetch_assoc($iot_res)) {
    $row['timestamp'] = $row['created_at']; 
    $readings[] = $row;
}
echo json_encode(['count' => count($readings), 'readings' => $readings]);
?>
