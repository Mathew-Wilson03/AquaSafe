<?php
/**
 * AquaSafe IoT Receiver (The Final Invincible Version)
 * Location: Login/receive_iot_data.php
 * Parameter: "payload" (format: id,level,status)
 */
require_once 'config.php';
require_once 'alert_utils.php';

// Diagnostic Header
header("X-AquaSafe-System: Invincible-V5-Active");

if (!$link) {
    die(json_encode(['status' => 'error', 'msg' => 'DB FAIL']));
}

// Get the data from "payload" (Firewall-Crushed) OR individual params
$raw_payload = $_REQUEST['payload'] ?? null;
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_REQUEST;

if ($raw_payload) {
    // Split: 1,12.5,CRITICAL (supports both , and -)
    $divider = (strpos($raw_payload, '-') !== false) ? '-' : ',';
    $parts = explode($divider, $raw_payload);
    
    if (count($parts) >= 3) {
        $v1 = $parts[0]; // ID
        $v2 = $parts[1]; // Level
        $v3 = $parts[2]; // Status
    }
} else {
    // Fallback to individual parameters (sid, lvl, st)
    $v1 = $input['sid'] ?? ($input['sensor_id'] ?? 1);
    $v2 = $input['lvl'] ?? ($input['level'] ?? null);
    $v3 = $input['st']  ?? ($input['status'] ?? null);
}

// If we have level and status, record it!
if ($v2 !== null && $v3 !== null) {
    $level  = (float)$v2;
    $status = strtoupper(trim($v3));
    $raw_id = (string)$v1; // Cast to string to handle 'REC-001'

    // Handle Receiver/Gateway Heartbeat
    if ($raw_id === "REC-001" || strtoupper($raw_id) === "GATEWAY" || $raw_id === "0") {
        mysqli_query($link, "UPDATE sensor_status SET last_ping = NOW(), status = 'Active' WHERE sensor_id = 'REC-001'");
        echo json_encode(['status' => 'success', 'recorded' => false, 'msg' => 'GATEWAY_PULSE_OK']);
        exit;
    }

    $sensor_id = (is_numeric($raw_id) && (int)$raw_id == 1) ? "SNS-001" : 
                 ((is_numeric($raw_id)) ? "SNS-" . str_pad($raw_id, 3, '0', STR_PAD_LEFT) : $raw_id);

    require_once 'notification_logic.php';
    $chkLoc = mysqli_query($link, "SELECT location_name FROM sensor_status WHERE sensor_id = '$sensor_id' LIMIT 1");
    $locRow = mysqli_fetch_assoc($chkLoc);
    $location_name = $locRow ? $locRow['location_name'] : "Unknown Cluster";

    processIoTNotification($link, $sensor_id, $location_name, $level);

    // Update Real-time Status
    $updateStatus = mysqli_prepare($link, "UPDATE sensor_status SET water_level = ?, status = ?, last_ping = NOW() WHERE sensor_id = ?");
    mysqli_stmt_bind_param($updateStatus, "dss", $level, $status, $sensor_id);
    mysqli_stmt_execute($updateStatus);
    mysqli_stmt_close($updateStatus);

    // FIX: Also update the Receiver (Gateway) status whenever ANY sensor sends data
    // This confirms the bridge is working.
    mysqli_query($link, "UPDATE sensor_status SET last_ping = NOW(), status = 'Active' WHERE sensor_id = 'REC-001'");

    // Insert History
    $stmt = mysqli_prepare($link, "INSERT INTO flood_data (sensor_id, location, level, status) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssds", $sensor_id, $location_name, $level, $status);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['status' => 'success', 'recorded' => true, 'lvl' => $level, 'st' => $status]);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'DB ERR']);
    }
    mysqli_stmt_close($stmt);
    exit;
}

// Fallback: Show History (for dashboard polling)
$sql    = "SELECT * FROM flood_data ORDER BY created_at DESC LIMIT 20";
$result = mysqli_query($link, $sql);
$rows = [];
while ($row = mysqli_fetch_assoc($result)) {
    $rows[] = $row;
}
echo json_encode(['status' => 'success', 'data' => $rows]);
exit;
?>
