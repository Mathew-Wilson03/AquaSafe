<?php
header('Content-Type: application/json');
require_once 'config.php';

$response = [
    'status' => 'success',
    'sensors' => []
];

// 2. Fetch all sensor statuses with timestamp diff
$q_all = "SELECT *, TIMESTAMPDIFF(SECOND, last_ping, NOW()) as seconds_since_ping FROM sensor_status ORDER BY sensor_id ASC";
$res_all = mysqli_query($link, $q_all);

$offline_threshold = 60; // Increased to 60s for better stability
$signal_threshold_good = 15;

while ($row = mysqli_fetch_assoc($res_all)) {
    $role = (strpos($row['sensor_id'], 'SNS') !== false) ? 'Sender ESP' : 'Receiver ESP';
    
    // Real Status Logic
    $is_online = ($row['last_ping'] && $row['seconds_since_ping'] <= $offline_threshold);
    $status_text = $is_online ? 'Active' : 'Offline';
    
    // Signal Logic
    if (!$row['last_ping']) {
        $signal = 'Never Seen';
    } else if (!$is_online) {
        $signal = 'Lost';
    } else {
        $signal = ($row['seconds_since_ping'] <= $signal_threshold_good) ? 'Good' : 'Weak';
    }

    $response['sensors'][] = [
        'id' => $row['sensor_id'],
        'location' => $row['location_name'],
        'role' => $role,
        'signal' => $signal,
        'water_level' => number_format((float)$row['water_level'], 2) . ' ft',
        'last_ping' => $row['last_ping'] ? date('H:i:s', strtotime($row['last_ping'])) : 'Never',
        'status' => $status_text
    ];
}

echo json_encode($response);
?>
