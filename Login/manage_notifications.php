<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Fetch current settings
if ($action === 'fetch') {
    $sql = "SELECT * FROM notification_settings WHERE id = 1";
    $result = mysqli_query($link, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $settings = mysqli_fetch_assoc($result);
        echo json_encode([
            'status' => 'success',
            'data' => [
                'master_enabled' => (bool)$settings['master_enabled'],
                'warning_threshold' => (int)$settings['warning_threshold'],
                'critical_threshold' => (int)$settings['critical_threshold'],
                'sms_enabled' => (bool)$settings['sms_enabled'],
                'email_enabled' => (bool)$settings['email_enabled'],
                'push_enabled' => (bool)$settings['push_enabled'],
                'siren_enabled' => (bool)$settings['siren_enabled']
            ]
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Settings not found']);
    }
}

// Update master toggle
elseif ($action === 'update_master') {
    $enabled = isset($_POST['enabled']) ? (int)$_POST['enabled'] : 0;
    
    $sql = "UPDATE notification_settings SET master_enabled = $enabled WHERE id = 1";
    
    if (mysqli_query($link, $sql)) {
        echo json_encode([
            'status' => 'success',
            'message' => $enabled ? 'Alert system enabled' : 'Alert system disabled',
            'enabled' => (bool)$enabled
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update']);
    }
}

// Update thresholds
elseif ($action === 'update_thresholds') {
    $warning = isset($_POST['warning']) ? (int)$_POST['warning'] : 75;
    $critical = isset($_POST['critical']) ? (int)$_POST['critical'] : 90;
    
    if ($warning < 0 || $warning > 100 || $critical < 0 || $critical > 100) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid values']);
        exit;
    }
    
    $sql = "UPDATE notification_settings SET warning_threshold = $warning, critical_threshold = $critical WHERE id = 1";
    
    if (mysqli_query($link, $sql)) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Thresholds updated',
            'warning' => $warning,
            'critical' => $critical
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update']);
    }
}

// Update delivery channels
elseif ($action === 'update_channels') {
    $sms = isset($_POST['sms']) ? (int)$_POST['sms'] : 0;
    $email = isset($_POST['email']) ? (int)$_POST['email'] : 0;
    $push = isset($_POST['push']) ? (int)$_POST['push'] : 0;
    $siren = isset($_POST['siren']) ? (int)$_POST['siren'] : 0;
    
    $sql = "UPDATE notification_settings SET sms_enabled = $sms, email_enabled = $email, push_enabled = $push, siren_enabled = $siren WHERE id = 1";
    
    if (mysqli_query($link, $sql)) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Channels updated'
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update']);
    }
}

else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}

mysqli_close($link);
?>
