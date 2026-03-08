<?php
header('Content-Type: application/json');
session_start();
require_once 'config.php';

if (!isset($_SESSION['email'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_email = $_SESSION['email'];
$action = $_POST['action'] ?? '';

if ($action === 'save') {
    $lat = $_POST['lat'] ?? null;
    $lng = $_POST['lng'] ?? null;

    if (!$lat || !$lng) {
        echo json_encode(['status' => 'error', 'message' => 'Missing coordinates']);
        exit;
    }

    // Clear previous active signals for this user to avoid clutter
    $clear_sql = "UPDATE emergency_signals SET status = 'Cleared' WHERE user_email = ? AND status = 'Active'";
    $stmt = mysqli_prepare($link, $clear_sql);
    mysqli_stmt_bind_param($stmt, "s", $user_email);
    mysqli_stmt_execute($stmt);

    // Insert new signal
    $insert_sql = "INSERT INTO emergency_signals (user_email, latitude, longitude, status) VALUES (?, ?, ?, 'Active')";
    $stmt = mysqli_prepare($link, $insert_sql);
    mysqli_stmt_bind_param($stmt, "sdd", $user_email, $lat, $lng);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['status' => 'success', 'message' => 'Location shared with emergency responders.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($link)]);
    }
} elseif ($action === 'acknowledge') {
    $email = $_POST['email'] ?? '';
    
    if (!$email) {
        echo json_encode(['status' => 'error', 'message' => 'Missing user email']);
        exit;
    }

    // 1. Update the signal status
    $update_sql = "UPDATE emergency_signals SET status = 'Acknowledged' WHERE user_email = ? AND status = 'Active'";
    $stmt = mysqli_prepare($link, $update_sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    
    if (mysqli_stmt_execute($stmt)) {
        // 2. Notify the user via notification_history
        $notif_sql = "INSERT INTO notification_history (location, severity, message, sensor_id) 
                      VALUES ('System Wide', 'CRITICAL', ?, 'SOS-ACK')";
        $msg = "SOS Received for $email: Help is being dispatched. Stay calm and follow safety protocols.";
        $stmt_notif = mysqli_prepare($link, $notif_sql);
        mysqli_stmt_bind_param($stmt_notif, "s", $msg);
        mysqli_stmt_execute($stmt_notif);

        echo json_encode(['status' => 'success', 'message' => 'Emergency acknowledged. User notified.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($link)]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}

mysqli_close($link);
?>
