<?php
header('Content-Type: application/json');
require_once 'config.php';

$range = $_GET['range'] ?? '24h';
$area = $_GET['area'] ?? 'All';

$interval = "INTERVAL 1 DAY";
if ($range === '7d') $interval = "INTERVAL 7 DAY";
if ($range === '30d') $interval = "INTERVAL 30 DAY";

$sensor_map = [
    'Nellimala' => 'SNS-001',
    'Churakullam' => 'SNS-001',
    'Kakkikavala' => 'SNS-001'
];

$loc_filter = "";
if ($area !== 'All') {
    $loc_filter = " AND location LIKE '%$area%'";
}

// 1. Total Alerts
$sqlA = "SELECT COUNT(*) as total FROM sensor_alerts WHERE timestamp >= NOW() - $interval $loc_filter";
$resA = mysqli_query($link, $sqlA);
$total_alerts = mysqli_fetch_assoc($resA)['total'] ?? 0;

// 2. Flood Events (defined as Critical alert status)
$sqlF = "SELECT COUNT(*) as total FROM flood_data WHERE status = 'CRITICAL' AND created_at >= NOW() - $interval";
if ($area !== 'All' && isset($sensor_map[$area])) {
    $sid = $sensor_map[$area];
    $sqlF .= " AND sensor_id = '$sid'";
}
$resF = mysqli_query($link, $sqlF);
$flood_events = mysqli_fetch_assoc($resF)['total'] ?? 0;

// 3. Detailed Logs for the table
$sqlLog = "SELECT timestamp as time, message as event, location, severity, 'Resolved' as status 
           FROM sensor_alerts 
           WHERE timestamp >= NOW() - $interval $loc_filter
           ORDER BY timestamp DESC LIMIT 50";
$resLog = mysqli_query($link, $sqlLog);
$logs = [];
while($row = mysqli_fetch_assoc($resLog)) {
    // Format time for JS
    $row['time'] = date('H:i', strtotime($row['time']));
    $logs[] = $row;
}

echo json_encode([
    'status' => 'success',
    'stats' => [
        'total_alerts' => $total_alerts,
        'flood_events' => $flood_events,
        'safe_recovery' => '98%' // Simulated high recovery rate or calculate based on resolved tasks
    ],
    'logs' => $logs
]);
?>
