<?php
header('Content-Type: application/json');
require_once 'config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'fetch_all') {
    $user_location = isset($_GET['user_location']) ? mysqli_real_escape_string($link, $_GET['user_location']) : '';
    
    // Base SQL
    $whereClause = "";
    if (!empty($user_location)) {
        // Filter: Show matching location OR 'System Wide' OR 'System Broadcast'
        $whereClause = "WHERE location = '$user_location' OR location = 'System Wide' OR location = 'System Broadcast'";
    }

    $sql = "SELECT * FROM sensor_alerts $whereClause ORDER BY timestamp DESC LIMIT 20";
    $result = mysqli_query($link, $sql);
    $alerts = [];
    while ($row = mysqli_fetch_assoc($result)) {
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

if ($action === 'broadcast') {
    $severity = $_POST['severity'] ?? 'Info';
    $message = $_POST['message'] ?? '';
    $location = $_POST['location'] ?? 'System Broadcast';

    if (empty($message)) {
        echo json_encode(['status' => 'error', 'message' => 'Message is required']);
        exit;
    }

    $sql = "INSERT INTO sensor_alerts (severity, message, location) VALUES ('$severity', '$message', '$location')";
    if (mysqli_query($link, $sql)) {
        echo json_encode(['status' => 'success', 'message' => 'Alert broadcasted successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to broadcast: ' . mysqli_error($link)]);
    }
    exit;
}

if ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid Alert ID']);
        exit;
    }
    
    $sql = "DELETE FROM sensor_alerts WHERE id = $id";
    if (mysqli_query($link, $sql)) {
        echo json_encode(['status' => 'success', 'message' => 'Alert deleted successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete alert']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
?>
