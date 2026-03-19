<?php
/**
 * get_user_alerts.php - High-performance API for user alert polling
 * Location: Login/get_user_alerts.php
 */
header('Content-Type: application/json');
require_once 'config.php';

// Safe session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. ETag Caching to minimize redundant processing
$user_id = $_SESSION['id'] ?? 0;
if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Generate an ETag based on user ID and a 10-second time window
$etag = '"' . md5($user_id . floor(time() / 10)) . '"';
header('ETag: ' . $etag);
header('Cache-Control: private, no-cache');

if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag) {
    http_response_code(304);
    exit;
}

// 2. Fetch User Location for filtering
$user_location = 'System Wide';
$stmt = mysqli_prepare($link, "SELECT location FROM users WHERE id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $loc);
    if (mysqli_stmt_fetch($stmt)) {
        if (!empty($loc)) $user_location = $loc;
    }
    mysqli_stmt_close($stmt);
}

// 3. Query Alerts with optimized filtering and indexing
$loc_esc = mysqli_real_escape_string($link, $user_location);

// Selection logic: MATCHING location OR System Wide OR All
$sql = "SELECT id, severity, message, location, alert_type, timestamp 
        FROM sensor_alerts 
        WHERE (location = '$loc_esc' OR location = 'System Wide' OR location = 'All' OR location = 'System Broadcast')
        ORDER BY timestamp DESC 
        LIMIT 20";

$result = mysqli_query($link, $sql);
$alerts = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Standardize timestamp as UTC for frontend
    $row['timestamp'] = $row['timestamp'] . 'Z';
    $alerts[] = $row;
}

echo json_encode([
    'status' => 'success',
    'location' => $user_location,
    'data' => $alerts
]);
?>
