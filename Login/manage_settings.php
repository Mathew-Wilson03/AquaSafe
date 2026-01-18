<?php
// manage_settings.php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// 1. Auto-Create Table
$sql = "CREATE TABLE IF NOT EXISTS `system_settings` (
    `setting_key` VARCHAR(50) PRIMARY KEY,
    `setting_value` TEXT
)";
mysqli_query($link, $sql);

// Helper: Response
function jsonResponse($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

// 2. Handle Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save Settings
    $email = $_POST['email'] ?? '';
    $refresh = $_POST['refresh'] ?? '30';

    // Upsert Email
    $stmt = mysqli_prepare($link, "INSERT INTO system_settings (setting_key, setting_value) VALUES ('admin_email', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    mysqli_stmt_bind_param($stmt, "ss", $email, $email);
    mysqli_stmt_execute($stmt);

    // Upsert Refresh
    $stmt = mysqli_prepare($link, "INSERT INTO system_settings (setting_key, setting_value) VALUES ('refresh_rate', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    mysqli_stmt_bind_param($stmt, "ss", $refresh, $refresh);
    mysqli_stmt_execute($stmt);

    jsonResponse(true, "Settings saved successfully!");
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Load Settings
    $settings = [
        'admin_email' => 'admin@aquasafe.com',
        'refresh_rate' => '30'
    ];

    $res = mysqli_query($link, "SELECT * FROM system_settings");
    if($res) {
        while($row = mysqli_fetch_assoc($res)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    jsonResponse(true, "", $settings);
}
?>
