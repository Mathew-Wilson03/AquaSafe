<?php
// receive_iot_data.php
// API Endpoint to receive Flood Alerts from ESP32 Gateway
// and store them in the database for the Dashboard to fetch.

header('Content-Type: application/json');
require_once 'config.php';

// 1. Auto-Create Table
$table_sql = "CREATE TABLE IF NOT EXISTS `flood_alerts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `alert_id` INT NOT NULL,
    `alert_level` VARCHAR(20) NOT NULL,
    `message` TEXT NOT NULL,
    `received_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `is_active` TINYINT(1) DEFAULT 1
)";
mysqli_query($link, $table_sql);

// 2. Handle POST Request (From ESP32)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if data is JSON or Form Data
    $data = json_decode(file_get_contents('php://input'), true);
    if(!$data) $data = $_POST; // Fallback to standard POST

    $alert_id = $data['alert_id'] ?? 0;
    $level = $data['alert_level'] ?? 'UNKNOWN';
    $msg = $data['message'] ?? 'No message';

    if ($alert_id > 0) {
        $stmt = mysqli_prepare($link, "INSERT INTO flood_alerts (alert_id, alert_level, message) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iss", $alert_id, $level, $msg);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(["status" => "success", "message" => "Alert stored"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Database error"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid Data"]);
    }
    exit;
}

// 3. Handle GET Request (From Admin Dashboard polling)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch latest active alerts (last 1 minute)
    $sql = "SELECT * FROM flood_alerts WHERE received_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE) ORDER BY id DESC LIMIT 5";
    $result = mysqli_query($link, $sql);
    
    $alerts = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $alerts[] = $row;
    }
    
    echo json_encode(["status" => "success", "data" => $alerts]);
    exit;
}
?>
