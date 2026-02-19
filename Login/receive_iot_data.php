<?php
/**
 * receive_iot_data.php
 * AquaSafe IoT Flood Monitoring - Data Ingestion API
 *
 * Accepts POST requests from ESP32 (LoRa Receiver Gateway).
 * Validates, sanitizes, and stores flood sensor readings.
 *
 * POST Parameters:
 *   - level  (float) : Water level in feet, e.g. 3.2
 *   - status (string): Alert status — SAFE, WARNING, or CRITICAL
 *
 * ALSO supports GET requests from the Admin Dashboard to fetch recent alerts.
 */

header('Content-Type: application/json');

// ─────────────────────────────────────────────────────────────
// 1. Database Connection
// ─────────────────────────────────────────────────────────────
require_once 'config.php';

if (!$link) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit;
}

// ─────────────────────────────────────────────────────────────
// 2. Auto-Create flood_data Table (if it doesn't exist)
// ─────────────────────────────────────────────────────────────
$create_table_sql = "
    CREATE TABLE IF NOT EXISTS `flood_data` (
        `id`         INT           AUTO_INCREMENT PRIMARY KEY,
        `level`      FLOAT         NOT NULL COMMENT 'Water level in feet',
        `status`     VARCHAR(20)   NOT NULL COMMENT 'SAFE | WARNING | CRITICAL',
        `created_at` TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if (!mysqli_query($link, $create_table_sql)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Table creation failed: ' . mysqli_error($link)]);
    exit;
}

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
    $level  = filter_var($input['level'], FILTER_VALIDATE_FLOAT);
    $status = strtoupper(trim($input['status']));

    if ($level === false || $level < 0) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Invalid level value. Must be a positive number.']);
        exit;
    }

    $allowed_statuses = ['SAFE', 'WARNING', 'CRITICAL'];
    if (!in_array($status, $allowed_statuses)) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Invalid status. Must be SAFE, WARNING, or CRITICAL.']);
        exit;
    }

    // --- Insert into Database (Prepared Statement) ---
    $stmt = mysqli_prepare($link, "INSERT INTO flood_data (level, status) VALUES (?, ?)");

    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Statement preparation failed.']);
        exit;
    }

    mysqli_stmt_bind_param($stmt, "ds", $level, $status);

    if (mysqli_stmt_execute($stmt)) {
        $inserted_id = mysqli_insert_id($link);
        echo json_encode([
            'status'  => 'success',
            'message' => 'Flood data recorded.',
            'data'    => [
                'id'     => $inserted_id,
                'level'  => $level,
                'status' => $status
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
