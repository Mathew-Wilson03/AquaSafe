<?php
header('Content-Type: application/json');
require_once 'config.php';

$action = $_GET['action'] ?? '';

if ($action === 'fetch_all') {
    $sql = "SELECT * FROM sensor_alerts ORDER BY timestamp DESC LIMIT 20";
    $result = mysqli_query($link, $sql);
    $alerts = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Format relative time if needed or just send ISO
        $alerts[] = $row;
    }
    echo json_encode(['status' => 'success', 'data' => $alerts]);
    exit;
}

if ($action === 'fetch_sensors') {
    $sql = "SELECT * FROM sensor_status ORDER BY sensor_id ASC";
    $result = mysqli_query($link, $sql);
    $sensors = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $sensors[] = $row;
    }
    echo json_encode(['status' => 'success', 'data' => $sensors]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
?>
