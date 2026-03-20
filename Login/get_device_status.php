<?php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'alert_utils.php';

// Force UTC for API consistency
date_default_timezone_set('UTC');

$response = [
    'status' => 'success',
    'devices' => []
];

// Define devices to monitor
$devices = [
    ['id' => 'SNS-001', 'name' => 'Sender ESP'],
    ['id' => 'REC-001', 'name' => 'Receiver ESP']
];

$offline_threshold = 60; // Increased to 60s for better stability

foreach ($devices as $device) {
    $sensor_id = $device['id'];
    $name = $device['name'];
    
    $query = "SELECT *, TIMESTAMPDIFF(SECOND, last_ping, NOW()) as seconds_since_ping FROM sensor_status WHERE sensor_id = '$sensor_id' LIMIT 1";
    $result = mysqli_query($link, $query);
    $data = mysqli_fetch_assoc($result);
    
    if ($data) {
        $data['is_online'] = ($data['seconds_since_ping'] <= $offline_threshold);
        
        // --- 🚨 DEVICE HEALTH TRACKING 🚨 ---
        // If it crosses the offline threshold and isn't marked Offline yet...
        if (!$data['is_online'] && $data['status'] !== 'Offline') {
            mysqli_query($link, "UPDATE sensor_status SET status = 'Offline' WHERE sensor_id = '$sensor_id'");
            logSystemNotification($link, 'device', 'warning', $data['location_name'], "Device connection lost (No ping for {$data['seconds_since_ping']}s).", null);
            $data['status'] = 'Offline'; // Update for current response
        }

        $data['display_name'] = $name;
        $data['location'] = $data['location_name']; // Map for easier JS access
        // Human readable timestamp
        $data['last_updated'] = $data['last_ping'] ? date('M d, H:i:s', strtotime($data['last_ping'])) : 'Never';
        $data['last_ping'] = $data['last_ping'] ? $data['last_ping'] . 'Z' : null;
        $response['devices'][] = $data;
    } else {
        $response['devices'][] = [
            'sensor_id' => $sensor_id,
            'display_name' => $name,
            'location' => 'Unknown',
            'status' => 'Offline',
            'is_online' => false,
            'water_level' => 0.00,
            'last_ping' => null,
            'last_updated' => 'Never',
            'seconds_since_ping' => null
        ];
    }
}

echo json_encode($response);
?>
