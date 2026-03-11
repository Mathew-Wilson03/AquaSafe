<?php
header('Content-Type: application/json');
require_once 'config.php';

// Force UTC for API consistency
date_default_timezone_set('UTC');

$response = [
    'status' => 'success',
    'data' => []
];

// 1. Total Alerts Today (Using MySQL CURDATE for consistency)
$alerts_query = "SELECT COUNT(*) as total FROM sensor_alerts WHERE DATE(timestamp) = CURDATE()";
$alerts_result = mysqli_query($link, $alerts_query);
$response['data']['total_alerts_today'] = mysqli_fetch_assoc($alerts_result)['total'] ?? 0;

// 2. Latest Alert Message (with Location)
$latest_alert_query = "
    SELECT a.message, a.severity, a.timestamp, s.location_name as location 
    FROM sensor_alerts a 
    LEFT JOIN sensor_status s ON (a.message LIKE CONCAT('%', s.sensor_id, '%') OR a.message LIKE CONCAT('%', s.location_name, '%'))
    ORDER BY a.timestamp DESC LIMIT 1
";
$latest_alert_result = mysqli_query($link, $latest_alert_query);
$latest_alert = mysqli_fetch_assoc($latest_alert_result) ?: ['message' => 'No recent alerts', 'severity' => 'Safe', 'timestamp' => null, 'location' => 'N/A'];
if($latest_alert['timestamp']) $latest_alert['timestamp'] .= 'Z';
$response['data']['latest_alert'] = $latest_alert;

// 3. Flood Risk Level (Based on latest sensor reading)
$risk_query = "SELECT status FROM flood_data ORDER BY created_at DESC LIMIT 1";
$risk_result = mysqli_query($link, $risk_query);
$response['data']['flood_risk_level'] = mysqli_fetch_assoc($risk_result)['status'] ?? 'SAFE';

// 4. Evacuation Points Count
$evac_query = "SELECT COUNT(*) as total FROM evacuation_points";
$evac_result = mysqli_query($link, $evac_query);
$response['data']['evacuation_points_count'] = mysqli_fetch_assoc($evac_result)['total'] ?? 0;

// 5. Daily High Water Level (Using MySQL CURDATE for consistency)
$peak_query = "SELECT MAX(level) as peak FROM flood_data WHERE DATE(created_at) = CURDATE()";
$peak_result = mysqli_query($link, $peak_query);
$response['data']['daily_peak'] = mysqli_fetch_assoc($peak_result)['peak'] ?? 0.00;

// 6. Last Sync Time (UTC)
$response['data']['last_sync'] = date('H:i:s') . 'Z';
$response['data']['timestamp'] = date('Y-m-d H:i:s') . 'Z';

echo json_encode($response);
?>
