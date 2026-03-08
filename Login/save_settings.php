<?php
require_once 'config.php';

header('Content-Type: application/json');

// Ensure admin access (Reuse session check if needed or just assume for now as it's a backend endpoint)
session_start();
if (!isset($_SESSION['email'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $allowed_keys = [
        'threshold_safe_max', 
        'threshold_warning_max', 
        'channel_sms', 
        'channel_email', 
        'channel_push', 
        'channel_siren'
    ];

    $success_count = 0;
    foreach ($allowed_keys as $key) {
        if (isset($_POST[$key])) {
            $value = mysqli_real_escape_string($link, $_POST[$key]);
            $sql = "INSERT INTO notification_settings (setting_key, setting_value) 
                    VALUES ('$key', '$value') 
                    ON DUPLICATE KEY UPDATE setting_value = '$value'";
            if (mysqli_query($link, $sql)) {
                $success_count++;
            }
        }
    }

    if ($success_count > 0) {
        echo json_encode(['status' => 'success', 'message' => "Updated $success_count settings."]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No settings updated or invalid keys provided.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
