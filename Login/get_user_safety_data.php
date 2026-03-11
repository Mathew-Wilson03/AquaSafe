<?php
ob_start();
header('Content-Type: application/json');
require_once 'config.php';

// config.php already handles session_start() safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Force UTC for API consistency
date_default_timezone_set('UTC');

if (!isset($_SESSION['email'])) {
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {

$user_id = $_SESSION['id'] ?? 0;
$user_location = 'System Wide';

// 1. Get user location and role
if ($user_id) {
    $stmt = mysqli_prepare($link, "SELECT location, user_role FROM users WHERE id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $loc, $role);
        if (mysqli_stmt_fetch($stmt)) {
            if (!empty($loc)) $user_location = $loc;
            $_SESSION['user_role'] = $role; // Cache in session
        }
        mysqli_stmt_close($stmt);
    }
}

$user_role = $_SESSION['user_role'] ?? 'user';

$response = [
    'status' => 'success',
    'location' => $user_location,
    'iot' => null, // Changed from hero
    'adminAlert' => null, // Changed from alert
    'nearestEvac' => null, // Changed from evacuation
    'iqHistory' => [], // Changed from iq_feed
    'iot_history' => [] // Changed from intelligence
];

// Get last 4 readings to detect trend and populate intelligence widget
$loc_esc = mysqli_real_escape_string($link, $user_location);
$is_admin = (stripos($user_role, 'admin') !== false);

$where_cond = "1=1"; // Default for System Wide and unassigned
if (!$is_admin && $user_location !== 'System Wide' && !empty($user_location)) {
    // If user has a specific location AND is NOT an admin, show their location + system-wide alerts
    $where_cond = "(location = '$loc_esc' OR location = 'System Wide' OR location = 'Unknown Cluster')";
}

$iot_sql = "SELECT sensor_id, level, status, location, created_at FROM flood_data 
            WHERE $where_cond
            ORDER BY created_at DESC LIMIT 20";
$iot_res = mysqli_query($link, $iot_sql);

$readings = [];
while ($row = mysqli_fetch_assoc($iot_res)) {
    // Append Z to indicate UTC for JavaScript Date constructor
    $row['timestamp'] = $row['created_at'] . 'Z'; 
    $readings[] = $row;
}
error_log("[get_user_safety_data] Found " . count($readings) . " readings for location: " . $user_location);

if (!empty($readings)) {
    $current = $readings[0];
    $prev = count($readings) > 1 ? $readings[1] : null;
    
    $trend = 'Stable'; 
    if ($prev) {
        $diff = floatval($current['level']) - floatval($prev['level']);
        if ($diff > 0.05) $trend = 'Rising';
        elseif ($diff < -0.05) $trend = 'Falling';
    }

    $response['iot'] = [
        'level' => floatval($current['level']),
        'status' => $current['status'],
        'trend' => $trend,
        'location' => $current['location'] ?? $user_location, 
        'timestamp' => $current['created_at'] . 'Z',
        'formatted_time' => date('h:i A', strtotime($current['created_at']))
    ];
    $response['iot_history'] = $readings;
}

// 3. Fetch Latest Admin Alert
$alert_sql = "SELECT id, severity, message, timestamp FROM sensor_alerts 
              WHERE (location = '$loc_esc' OR location = 'System Wide' OR location = 'System Broadcast' OR location = 'All')
              AND alert_type = 'Admin'
              ORDER BY timestamp DESC LIMIT 1";
$alert_res = mysqli_query($link, $alert_sql);
if ($alert_res && $row = mysqli_fetch_assoc($alert_res)) {
    $row['timestamp'] = $row['timestamp'] . 'Z';
    $response['adminAlert'] = $row;
}

// 4. Fetch Nearest Evacuation Point
$evac_sql = "SELECT name, location, capacity, status, latitude, longitude 
             FROM evacuation_points 
             WHERE status != 'Closed'
             ORDER BY ABS(latitude - 9.5) + ABS(longitude - 77.0) ASC LIMIT 1"; 
             // Note: In a real app, we'd use the user's actual lat/lng
$evac_res = mysqli_query($link, $evac_sql);
if ($evac_res && $row = mysqli_fetch_assoc($evac_res)) {
    // Add distance mock if not calculated
    $row['distance'] = "Nearby";
    $response['nearestEvac'] = $row;
}

// 5. Fetch Recent IQ Notifications (State Changes)
$iq_sql = "SELECT severity, message, created_at FROM notification_history 
           WHERE (location = '$loc_esc' OR location = 'System Wide')
           ORDER BY created_at DESC LIMIT 5";
$iq_res = mysqli_query($link, $iq_sql);
while ($row = mysqli_fetch_assoc($iq_res)) {
    $row['timestamp'] = $row['created_at'] . 'Z'; // UTC with Z
    $row['formatted_time'] = date('h:i A', strtotime($row['created_at']));
    $response['iqHistory'][] = $row;
}

    ob_end_clean();
    echo json_encode($response);

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => 'System error: ' . $e->getMessage()]);
} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => 'Fatal error: ' . $e->getMessage()]);
}
