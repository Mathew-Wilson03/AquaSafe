<?php
require_once 'config.php';

header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['email'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$settings = [];
$result = mysqli_query($link, "SELECT setting_key, setting_value FROM notification_settings");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Ensure defaults are present in response for UI
$response = array_merge([
    'threshold_safe_max' => '10.00',
    'threshold_warning_max' => '18.00',
    'channel_sms' => '1',
    'channel_email' => '1',
    'channel_push' => '1',
    'channel_siren' => '1'
], $settings);

echo json_encode(['status' => 'success', 'data' => $response]);
?>
