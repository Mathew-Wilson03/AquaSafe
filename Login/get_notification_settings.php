<?php
require_once 'config.php';

header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['email'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

require_once 'notification_logic.php';

$settings = getNotificationSettings($link);
echo json_encode(['status' => 'success', 'data' => $settings]);
exit;
?>
