<?php
require_once 'config.php';

header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['email'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 30;

$sql = "SELECT id, sensor_id, location, water_level, severity, message, created_at 
        FROM notification_history 
        ORDER BY created_at DESC 
        LIMIT $limit";

$result = mysqli_query($link, $sql);
$feed = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $row['formatted_time'] = date('M d, h:i A', strtotime($row['created_at']));
        $feed[] = $row;
    }
    echo json_encode(['status' => 'success', 'data' => $feed]);
} else {
    echo json_encode(['status' => 'error', 'message' => mysqli_error($link)]);
}
?>
