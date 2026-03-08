<?php
header('Content-Type: application/json');
require_once 'config.php';

$range = $_GET['range'] ?? '24h';
$area = $_GET['area'] ?? 'All';

$labels = [];
$waterLevels = [];
$alertCounts = [];

// Mapping Area to Sensor ID for filtering
$sensor_map = [
    'Nellimala' => 'SNS-001',
    'Churakullam' => 'SNS-001',
    'Kakkikavala' => 'SNS-001'
];

$filter = "";
if ($area !== 'All' && isset($sensor_map[$area])) {
    $sid = $sensor_map[$area];
    $filter = " AND sensor_id = '$sid'";
}

if ($range === '24h') {
    // Group by hour
    $sql = "SELECT 
                DATE_FORMAT(created_at, '%H:00') as label, 
                AVG(level) as avg_level,
                COUNT(*) as count
            FROM flood_data 
            WHERE created_at >= NOW() - INTERVAL 1 DAY $filter
            GROUP BY label
            ORDER BY created_at ASC";
    
    // Also get alerts count per hour
    $alert_sql = "SELECT 
                    DATE_FORMAT(timestamp, '%H:00') as label, 
                    COUNT(*) as count
                  FROM sensor_alerts
                  WHERE timestamp >= NOW() - INTERVAL 1 DAY
                  GROUP BY label";
} elseif ($range === '7d') {
    // Group by day
    $sql = "SELECT 
                DATE_FORMAT(created_at, '%a') as label, 
                AVG(level) as avg_level,
                COUNT(*) as count
            FROM flood_data 
            WHERE created_at >= NOW() - INTERVAL 7 DAY $filter
            GROUP BY label
            ORDER BY created_at ASC";

    $alert_sql = "SELECT 
                    DATE_FORMAT(timestamp, '%a') as label, 
                    COUNT(*) as count
                  FROM sensor_alerts
                  WHERE timestamp >= NOW() - INTERVAL 7 DAY
                  GROUP BY label";
} else { // 30d
    // Group by week
    $sql = "SELECT 
                CONCAT('Week ', WEEK(created_at) - WEEK(NOW() - INTERVAL 1 MONTH) + 1) as label, 
                AVG(level) as avg_level,
                COUNT(*) as count
            FROM flood_data 
            WHERE created_at >= NOW() - INTERVAL 1 MONTH $filter
            GROUP BY label
            ORDER BY created_at ASC";

    $alert_sql = "SELECT 
                    CONCAT('Week ', WEEK(timestamp) - WEEK(NOW() - INTERVAL 1 MONTH) + 1) as label, 
                    COUNT(*) as count
                  FROM sensor_alerts
                  WHERE timestamp >= NOW() - INTERVAL 1 MONTH
                  GROUP BY label";
}

$res = mysqli_query($link, $sql);
$historicalData = [];
while($row = mysqli_fetch_assoc($res)) {
    $labels[] = $row['label'];
    $waterLevels[] = round($row['avg_level'], 2);
}

$resA = mysqli_query($link, $alert_sql);
$alertMap = [];
while($row = mysqli_fetch_assoc($resA)) {
    $alertMap[$row['label']] = $row['count'];
}

// Align alert data with water level labels
foreach ($labels as $lbl) {
    $alertCounts[] = $alertMap[$lbl] ?? 0;
}

echo json_encode([
    'status' => 'success',
    'labels' => $labels,
    'waterLevels' => $waterLevels,
    'alertCounts' => $alertCounts
]);
?>
