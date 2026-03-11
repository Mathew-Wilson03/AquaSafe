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

    // Check which schema we have
    $hasKeyValue = false;
    $check = @mysqli_query($link, "SHOW COLUMNS FROM notification_settings LIKE 'setting_key'");
    if ($check && mysqli_num_rows($check) > 0) $hasKeyValue = true;

    $success_count = 0;
    if ($hasKeyValue) {
        // Key-Value Schema
        foreach ($allowed_keys as $key) {
            if (isset($_POST[$key])) {
                $value = mysqli_real_escape_string($link, $_POST[$key]);
                $sql = "INSERT INTO notification_settings (setting_key, setting_value) 
                        VALUES ('$key', '$value') 
                        ON DUPLICATE KEY UPDATE setting_value = '$value'";
                if (mysqli_query($link, $sql)) $success_count++;
            }
        }
    } else {
        // Column-based Schema (Legacy/Dump)
        $updates = [];
        $map = [
            'threshold_safe_max' => 'warning_threshold',
            'threshold_warning_max' => 'critical_threshold',
            'channel_sms' => 'sms_enabled',
            'channel_email' => 'email_enabled',
            'channel_push' => 'push_enabled',
            'channel_siren' => 'siren_enabled'
        ];
        
        foreach ($map as $appKey => $dbCol) {
            if (isset($_POST[$appKey])) {
                $val = (int)$_POST[$appKey];
                $updates[] = "$dbCol = $val";
            }
        }
        
        if (!empty($updates)) {
            $sql = "UPDATE notification_settings SET " . implode(', ', $updates) . " WHERE id = 1";
            if (mysqli_query($link, $sql)) $success_count = count($updates);
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
