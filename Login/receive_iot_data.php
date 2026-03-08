<?php
// ─────────────────────────────────────────────────────────────
// 1. Database Connection & Utilities
// ─────────────────────────────────────────────────────────────
require_once 'config.php';
require_once 'alert_utils.php';

if (!$link) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit;
}

// ... existing table creation logic ...
// (Omitted for brevity, assuming already run via db_update_alerts.php or kept for safety)

// ─────────────────────────────────────────────────────────────
// 3. Handle POST — Receive Data from ESP32
// ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Support both JSON body and standard form POST
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    // --- Validate Required Fields ---
    if (!isset($input['level']) || !isset($input['status'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields: level and status.']);
        exit;
    }

    // --- Sanitize and Validate Values ---
    $level     = filter_var($input['level'], FILTER_VALIDATE_FLOAT);
    $status    = strtoupper(trim($input['status']));
    // --- Map Sensor ID (Numeric from ESP32 to String for Dashboard) ---
    $raw_id = isset($input['sensor_id']) ? intval($input['sensor_id']) : 1;
    $sensor_id = ($raw_id == 1) ? "SNS-001" : "SNS-" . str_pad($raw_id, 3, '0', STR_PAD_LEFT);

    if ($level === false || $level < 0) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Invalid level value. Must be a positive number.']);
        exit;
    }

    $allowed_statuses = ['SAFE', 'WARNING', 'CRITICAL'];
    if ($status === 'DANGER') $status = 'CRITICAL'; // Normalize
    if (!in_array($status, $allowed_statuses)) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Invalid status. Must be SAFE, WARNING, or CRITICAL.']);
        exit;
    }

    // --- 🚨 AUTOMATIC ALERT TRIGGER 🚨 ---
    // Handle legacy alerts and new IoT Intelligence notifications
    require_once 'notification_logic.php';

    // Get location name for notification logging
    $chkLoc = mysqli_query($link, "SELECT location_name FROM sensor_status WHERE sensor_id = '$sensor_id' LIMIT 1");
    $locRow = mysqli_fetch_assoc($chkLoc);
    $location_name = $locRow ? $locRow['location_name'] : "Unknown Cluster";

    // processIoTNotification handles logging to notification_history and sending alerts based on settings
    processIoTNotification($link, $sensor_id, $location_name, $level);

    // handleIoTTrigger removed to ensure only one feed (Notifications Center) handles IoT intelligence
    // handleIoTTrigger($link, $sensor_id, $status, $level);

    // --- 1. Update Real-time Status (sensor_status) ---
    // Check if Sender was offline to log reconnection
    $chkSnd = mysqli_query($link, "SELECT status, location_name FROM sensor_status WHERE sensor_id = '$sensor_id' LIMIT 1");
    if ($chkSnd && $rowSnd = mysqli_fetch_assoc($chkSnd)) {
        if ($rowSnd['status'] === 'Offline') {
            logSystemNotification($link, 'device', 'info', $rowSnd['location_name'], "Device connection restored.", null);
        }
    }

    // Update Sender (SNS-001)
    $updateStatus = mysqli_prepare($link, "UPDATE sensor_status SET water_level = ?, status = ?, last_ping = NOW() WHERE sensor_id = ?");
    mysqli_stmt_bind_param($updateStatus, "dss", $level, $status, $sensor_id);
    mysqli_stmt_execute($updateStatus);
    mysqli_stmt_close($updateStatus);

    // Check if Receiver was offline to log reconnection
    $chkRec = mysqli_query($link, "SELECT status, location_name FROM sensor_status WHERE sensor_id = 'REC-001' LIMIT 1");
    if ($chkRec && $rowRec = mysqli_fetch_assoc($chkRec)) {
        if ($rowRec['status'] === 'Offline') {
            logSystemNotification($link, 'device', 'info', $rowRec['location_name'], "Gateway connection restored.", null);
        }
    }

    // Update Receiver/Gateway (REC-001) - If data is received, it's Active
    $updateReceiver = mysqli_prepare($link, "UPDATE sensor_status SET last_ping = NOW(), status = 'SAFE' WHERE sensor_id = 'REC-001'");
    mysqli_stmt_execute($updateReceiver);
    mysqli_stmt_close($updateReceiver);

    // --- 2. Insert into History (flood_data) ---
    // Note: flood_data now supports location tagging for efficient dashboard querying
    $stmt = mysqli_prepare($link, "INSERT INTO flood_data (sensor_id, location, level, status) VALUES (?, ?, ?, ?)");

    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Statement preparation failed: ' . mysqli_error($link)]);
        exit;
    }

    mysqli_stmt_bind_param($stmt, "ssds", $sensor_id, $location_name, $level, $status);

    if (mysqli_stmt_execute($stmt)) {
        $inserted_id = mysqli_insert_id($link);
        echo json_encode([
            'status'  => 'success',
            'message' => 'Flood data recorded & sensor status updated.',
            'data'    => [
                'id'        => $inserted_id,
                'sensor_id' => $sensor_id,
                'level'     => $level,
                'status'    => $status
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database insert failed: ' . mysqli_stmt_error($stmt)]);
    }

    mysqli_stmt_close($stmt);
    exit;
}

// ─────────────────────────────────────────────────────────────
// 4. Handle GET — Dashboard Polling (Fetch Recent Alerts)
// ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch the latest 20 readings from the last hour
    $sql    = "SELECT * FROM flood_data ORDER BY created_at DESC LIMIT 20";
    $result = mysqli_query($link, $sql);

    if (!$result) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Query failed: ' . mysqli_error($link)]);
        exit;
    }

    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    echo json_encode(['status' => 'success', 'count' => count($rows), 'data' => $rows]);
    exit;
}

// ─────────────────────────────────────────────────────────────
// 5. Reject All Other Methods
// ─────────────────────────────────────────────────────────────
http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Method not allowed. Use GET or POST.']);
?>
