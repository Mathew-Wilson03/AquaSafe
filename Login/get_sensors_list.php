<?php
header('Content-Type: application/json');
require_once 'config.php';

$response = [
    'status' => 'success',
    'sensors' => []
];

// 1. Fetch the real telemetry of the actual Sender (SNS-001)
$sender_id = 'SNS-001';
$offline_threshold = 30;
$signal_threshold_good = 15;

$q_main = "SELECT *, TIMESTAMPDIFF(SECOND, last_ping, NOW()) as seconds_since_ping FROM sensor_status WHERE sensor_id = '$sender_id' LIMIT 1";
$res_main = mysqli_query($link, $q_main);
$real_data = mysqli_fetch_assoc($res_main);

// Prepare real telemetry values
if ($real_data) {
    $real_status = ($real_data['seconds_since_ping'] <= $offline_threshold) ? 'Active' : 'Offline';
    $real_signal = ($real_status === 'Offline') ? 'Lost' : (($real_data['seconds_since_ping'] <= $signal_threshold_good) ? 'Good' : 'Weak');
    $real_water = number_format((float)$real_data['water_level'], 2) . ' ft';
    $real_ping = date('H:i:s', strtotime($real_data['last_ping']));
} else {
    $real_status = 'Offline';
    $real_signal = 'Lost';
    $real_water = '0.00 ft';
    $real_ping = 'Never';
}

// 2. Define Active IoT Locations
$active_locations = ['Nellimala', 'Churakullam', 'Kakkikavala'];

// 3. Fetch all sensor locations from the DB to list them
$q_all = "SELECT sensor_id, location_name FROM sensor_status ORDER BY sensor_id ASC";
$res_all = mysqli_query($link, $q_all);

while ($row = mysqli_fetch_assoc($res_all)) {
    $is_active = in_array($row['location_name'], $active_locations);
    
    // Determine Role (keeping basic logic)
    $role = (strpos($row['sensor_id'], 'SNS') !== false) ? 'Sender ESP' : 'Receiver ESP';

    if ($is_active) {
        // Map data from SNS-001
        $response['sensors'][] = [
            'id' => $row['sensor_id'],
            'location' => $row['location_name'],
            'role' => $role,
            'signal' => $real_signal,
            'water_level' => $real_water,
            'last_ping' => $real_ping,
            'status' => $real_status // Still returning in JSON, UI will hide col
        ];
    } else {
        // Placeholder data
        $response['sensors'][] = [
            'id' => $row['sensor_id'],
            'location' => $row['location_name'],
            'role' => $role,
            'signal' => 'Implementing Soon',
            'water_level' => '-',
            'last_ping' => '-',
            'status' => 'Pending'
        ];
    }
}

echo json_encode($response);
?>
