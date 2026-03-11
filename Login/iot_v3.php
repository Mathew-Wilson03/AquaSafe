<?php
/**
 * AquaSafe IoT Receiver v5 (The Sleep Fix)
 * Parameter: "payload" (format: id,level,status)
 */
require_once 'config.php';
require_once 'alert_utils.php';

// Diagnostic Header
header("X-AquaSafe-System: IoT-V5-Active");

if (!$link) {
    die(json_encode(['status' => 'error', 'msg' => 'DB FAIL']));
}

// Get the data from "payload" (Works with browser and ESP32)
$raw_data = $_REQUEST['payload'] ?? ($_REQUEST['d'] ?? null);

if ($raw_data) {
    // Support both comma and hyphen as dividers
    $divider = (strpos($raw_data, '-') !== false) ? '-' : ',';
    $parts = explode($divider, $raw_data);
    
    if (count($parts) >= 3) {
        $v1 = $parts[0]; // ID
        $v2 = $parts[1]; // Level
        $v3 = $parts[2]; // Status

        $level  = (float)$v2;
        $status = strtoupper(trim($v3));
        $raw_id = (int)$v1;
        $sensor_id = ($raw_id == 1) ? "SNS-001" : "SNS-" . str_pad($raw_id, 3, '0', STR_PAD_LEFT);

        require_once 'notification_logic.php';
        $chkLoc = mysqli_query($link, "SELECT location_name FROM sensor_status WHERE sensor_id = '$sensor_id' LIMIT 1");
        $locRow = mysqli_fetch_assoc($chkLoc);
        $location_name = $locRow ? $locRow['location_name'] : "Unknown Cluster";

        processIoTNotification($link, $sensor_id, $location_name, $level);

        // Update Real-time
        $updateStatus = mysqli_prepare($link, "UPDATE sensor_status SET water_level = ?, status = ?, last_ping = NOW() WHERE sensor_id = ?");
        mysqli_stmt_bind_param($updateStatus, "dss", $level, $status, $sensor_id);
        mysqli_stmt_execute($updateStatus);
        mysqli_stmt_close($updateStatus);

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
}

// Fallback: Just show simple check
echo json_encode(['status' => 'online', 'msg' => 'IoT V5 Ready. Send payload=id,level,status']);
?>
