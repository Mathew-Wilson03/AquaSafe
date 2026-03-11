<?php
/**
 * AquaSafe IoT Receiver (The INVINCIBLE v9 Version)
 * Location: App Root / ping.php
 * This version uses "Path Info" to bypass ALL Query-String firewalls.
 */
require_once 'Login/config.php';

if (!$link) {
    die("DB_FAIL");
}

// 1. Try to get data from Path Info (e.g. /ping.php/11050C)
$p = $_SERVER['PATH_INFO'] ?? null;
if ($p) $p = ltrim($p, '/');

// 2. Fallback to Query String (e.g. ?p=11050C)
if (!$p) $p = $_REQUEST['p'] ?? null;

if ($p) {
    // Detect the separator (none, comma, underscore, hyphen, or X)
    $chars = [',', '_', '-', 'X'];
    $parts = null;
    
    foreach ($chars as $char) {
        $temp = explode($char, $p);
        if (count($temp) >= 3) {
            $parts = $temp;
            break;
        }
    }
    
    // If no separator, it's the fixed-length block
    if (!$parts && strlen($p) >= 6) {
        $parts = [
            $p[0],             // ID
            substr($p, 1, 4),  // LVL_CM
            $p[5]              // STATUS
        ];
    }
    
    if ($parts) {
        $raw_id     = (int)$parts[0];
        $level_cm   = (int)$parts[1];
        $status_char= strtoupper(trim($parts[2]));

        $level      = $level_cm / 100.0;
        $status     = "SAFE";
        if ($status_char === 'W' || $status_char === 'WARNING')  $status = "WARNING";
        if ($status_char === 'C' || $status_char === 'CRITICAL') $status = "CRITICAL";

        $sensor_id  = ($raw_id == 1) ? "SNS-001" : "SNS-" . str_pad($raw_id, 3, '0', STR_PAD_LEFT);

        // Update Real-time Status
        $updateStatus = mysqli_prepare($link, "UPDATE sensor_status SET water_level = ?, status = ?, last_ping = NOW() WHERE sensor_id = ?");
        mysqli_stmt_bind_param($updateStatus, "dss", $level, $status, $sensor_id);
        mysqli_stmt_execute($updateStatus);
        mysqli_stmt_close($updateStatus);

        // Insert History
        $stmt = mysqli_prepare($link, "INSERT INTO flood_data (sensor_id, location, level, status) VALUES (?, 'Nellimala Cluster', ?, ?)");
        mysqli_stmt_bind_param($stmt, "sds", $sensor_id, $level, $status);

        if (mysqli_stmt_execute($stmt)) {
            echo "SUCCESS_RECORDED_VAL_".$level;
            exit;
        }
    }
}

echo "SERVER_READY_WAITING_V9_PATH_SUPPORT";
?>
