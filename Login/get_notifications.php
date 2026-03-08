<?php
header('Content-Type: application/json');
require_once 'config.php';

$type = isset($_GET['type']) ? mysqli_real_escape_string($link, $_GET['type']) : 'all';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;

$sql = "
    (SELECT 
        'system' as source, id, type, location, message, severity, water_level, timestamp 
     FROM system_notifications 
     " . ($type !== 'all' ? "WHERE type = '$type'" : "") . ")
    UNION ALL
    (SELECT 
        'emergency' as source, id, 'emergency' as type, 'User Location' as location, 
        CONCAT('SOS: User ', user_email, ' shared coordinates') as message, 
        'critical' as severity, NULL as water_level, created_at as timestamp 
     FROM emergency_signals 
     WHERE status = 'Active')
    ORDER BY timestamp DESC 
    LIMIT $limit";
$result = mysqli_query($link, $sql);

$notifications = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Format timestamp for easy frontend parsing if needed
        $row['formatted_time'] = date('M d, h:i A', strtotime($row['timestamp']));
        $notifications[] = $row;
    }
    echo json_encode(['status' => 'success', 'data' => $notifications]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to fetch notifications.']);
}
?>
