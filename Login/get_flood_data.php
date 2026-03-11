<?php
/**
 * get_flood_data.php
 * AquaSafe - Fetch Latest Water Level for Dashboard Display
 *
 * Call this via JavaScript fetch() to show the live sensor reading.
 *
 * Response JSON:
 * {
 *   "status": "success",
 *   "latest": {
 *     "id": 42,
 *     "level": 3.2,
 *     "status": "SAFE",
 *     "created_at": "2026-02-19 22:00:00"
 *   },
 *   "history": [ ... last 24 readings ... ]
 * }
 */

header('Content-Type: application/json');
require_once 'config.php';

if (!$link) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit;
}

// Force UTC for API consistency
date_default_timezone_set('UTC');

// -- 1. Get the single latest reading --
$latest_result = mysqli_query($link, "SELECT * FROM flood_data ORDER BY created_at DESC LIMIT 1");

if (!$latest_result || mysqli_num_rows($latest_result) === 0) {
    echo json_encode(['status' => 'error', 'message' => 'No flood data available yet.']);
    exit;
}

$latest = mysqli_fetch_assoc($latest_result);
if($latest) {
    $latest['timestamp'] = $latest['created_at'] . 'Z';
}

// -- 2. Get last 24 readings for the trend chart --
$history_result = mysqli_query($link, "SELECT level, status, created_at FROM flood_data ORDER BY created_at DESC LIMIT 24");
$history = [];
while ($row = mysqli_fetch_assoc($history_result)) {
    $row['timestamp'] = $row['created_at'] . 'Z';
    $history[] = $row;
}
// Reverse so chart goes oldest → newest
$history = array_reverse($history);

// -- 3. Get Active Emergency Signals --
$emergency_result = mysqli_query($link, "SELECT id, user_email, latitude, longitude, created_at FROM emergency_signals WHERE status = 'Active' ORDER BY id DESC");
$emergency_signals = [];
while ($row = mysqli_fetch_assoc($emergency_result)) {
    $row['timestamp'] = $row['created_at'] . 'Z';
    $emergency_signals[] = $row;
}

// -- 4. Return Result --
echo json_encode([
    'status'  => 'success',
    'latest'  => $latest,
    'history' => $history,
    'emergency_signals' => $emergency_signals
]);
?>
