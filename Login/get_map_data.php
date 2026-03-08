<?php
header('Content-Type: application/json');
require_once 'config.php';

$response = [
    'status' => 'success',
    'sensors' => []
];

// 1. Fetch thresholds from notification settings
$q_thresh = "SELECT warning_threshold, critical_threshold FROM notification_settings WHERE id = 1";
$res_thresh = mysqli_query($link, $q_thresh);
$thresholds = mysqli_fetch_assoc($res_thresh);

if (!$thresholds) {
    // Fallback defaults if table is empty
    $thresholds = ['warning_threshold' => 75, 'critical_threshold' => 90];
}

$max_depth = 25.0; // feet (max scale used in charts)
$warn_ft = ($thresholds['warning_threshold'] / 100) * $max_depth;
$crit_ft = ($thresholds['critical_threshold'] / 100) * $max_depth;

// 2. Fetch latest telemetry for SNS-001 (Trial IoT Sensor)
$q_iot = "SELECT level, created_at FROM flood_data ORDER BY created_at DESC LIMIT 1";
$res_iot = mysqli_query($link, $q_iot);
$latest_iot = mysqli_fetch_assoc($res_iot);

$iot_level = $latest_iot ? (float)$latest_iot['level'] : 0.00;
$iot_time = $latest_iot ? $latest_iot['created_at'] : 'Never';

// 3. Fetch all sensors with coordinates
$q_sensors = "SELECT sensor_id, location_name, latitude, longitude, last_ping FROM sensor_status";
$res_sensors = mysqli_query($link, $q_sensors);

$active_locations = ['Nellimala', 'Churakullam', 'Kakkikavala'];

while ($row = mysqli_fetch_assoc($res_sensors)) {
    // Only map real-time data to specific trial locations
    $is_iot = in_array($row['location_name'], $active_locations);
    
    $level = $is_iot ? $iot_level : 0.00;
    $timestamp = $is_iot ? $iot_time : $row['last_ping'];
    
    // Determine status based on thresholds
    $status = 'Safe';
    if ($level >= $crit_ft) {
        $status = 'Danger';
    } elseif ($level >= $warn_ft) {
        $status = 'Warning';
    }
    
    // Skip sensors without valid coordinates
    if ((float)$row['latitude'] == 0 && (float)$row['longitude'] == 0) continue;

    $response['sensors'][] = [
        'id'        => $row['sensor_id'],
        'location'  => $row['location_name'],
        'lat'       => (float)$row['latitude'],
        'lng'       => (float)$row['longitude'],
        'level'     => number_format($level, 2),
        'status'    => $status,
        'updated'   => date('H:i:s', strtotime($timestamp))
    ];
}

// 4. Fetch Active & Acknowledged Emergency Signals
$q_emergency = "SELECT user_email, latitude, longitude, created_at, status FROM emergency_signals WHERE status IN ('Active', 'Acknowledged')";
$res_emergency = mysqli_query($link, $q_emergency);

$response['emergency_signals'] = [];
while ($row = mysqli_fetch_assoc($res_emergency)) {
    $response['emergency_signals'][] = [
        'email'  => $row['user_email'],
        'lat'    => (float)$row['latitude'],
        'lng'    => (float)$row['longitude'],
        'time'   => date('H:i:s', strtotime($row['created_at'])),
        'status' => $row['status']
    ];
}

echo json_encode($response);
mysqli_close($link);
?>
