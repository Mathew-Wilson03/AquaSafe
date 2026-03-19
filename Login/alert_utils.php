<?php
// alert_utils.php - Unified Alert System for AquaSafe

require_once 'config.php';

// Force UTC for system-wide consistency
date_default_timezone_set('UTC');

// NOTE: vendor/autoload.php (PHPMailer) is intentionally NOT loaded here.
// It is lazy-loaded inside MailHelper only when required.
// This saves ~50-150ms of Composer class-map parsing on every request.
require_once 'MailHelper.php';

/**
 * Sends a mass broadcast (In-App + Email)
 * 
 * @param mysqli $link Database connection
 * @param string $area Target Location
 * @param string $message Alert message
 * @param string $severity WARNING or CRITICAL
 */
function sendBroadcast($link, $area, $message, $severity, $type = 'Admin') {
    // 1. Log to sensor_alerts table (for In-App visibility)
    $insertAlert = mysqli_prepare($link, "INSERT INTO sensor_alerts (severity, message, location, alert_type) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($insertAlert, "ssss", $severity, $message, $area, $type);
    mysqli_stmt_execute($insertAlert);
    mysqli_stmt_close($insertAlert);

    // 2. Gather All Recipients (App Users + Offline Contacts)
    $recipients = [];
    
    // App Users
    $sqlU = "SELECT email FROM users";
    if($area !== 'All' && $area !== 'System Wide' && !empty($area)) {
        // If it's a specific area, send to users in that area OR global subscribers
        $sqlU .= " WHERE location = 'All' OR location = 'System Wide' OR '$area' LIKE CONCAT('%', location, '%')"; 
    }
    $resU = mysqli_query($link, $sqlU);
    if($resU) while($r = mysqli_fetch_assoc($resU)) $recipients[] = $r['email'];

    // Offline Contacts
    $sqlC = "SELECT email FROM community_alerts";
    $resC = mysqli_query($link, $sqlC);
    if($resC) while($r = mysqli_fetch_assoc($resC)) $recipients[] = $r['email'];

    $recipients = array_unique($recipients);

    // 3. Always log alert to file for audit trail
    $emailLogFile = __DIR__ . '/emergency_email_log.txt';
    $logContent  = "--- ALERT: " . date('Y-m-d H:i:s') . " ---\n";
    $logContent .= "Subject: ⚠️ $severity Alert: $area\n";
    $logContent .= "Recipients: " . implode(', ', $recipients) . "\n";
    $logContent .= "Body: $message\n";
    $logContent .= "--------------------------------------\n\n";
    file_put_contents($emailLogFile, $logContent, FILE_APPEND | LOCK_EX);

    // 4. Send non-blocking response if possible
    // This allows the admin dashboard to refresh immediately while PHP continues sending in the background.
    if (function_exists('fastcgi_finish_request')) {
        // IMPORTANT: Close session before background processing to avoid locking the UI for this user
        if (session_id()) session_write_close(); 
        
        $recepCount = count($recipients);
        error_log("[AquaSafe] Detaching request to send $recepCount emails in background...");
        
        fastcgi_finish_request();
    }

    // 5. Email send — Use MailHelper for optimized delivery
    if (empty($recipients)) {
        return;
    }

    $subject = "⚠️ $severity Alert: $area";
    $successCount = 0;
    $failCount = 0;

    foreach ($recipients as $toEmail) {
        $body = "
            <div style='font-family: Arial, sans-serif; border: 2px solid #e74c3c; padding: 20px; border-radius: 10px;'>
                <h2 style='color: #e74c3c;'>$severity Flood Alert</h2>
                <p><strong>Location:</strong> $area</p>
                <p><strong>Status:</strong> $message</p>
                <p style='background: #fdf2f2; padding: 10px; border-left: 5px solid #e74c3c;'>
                    Please stay tuned to local news and follow evacuation orders if issued.
                </p>
                <p>Stay Safe,<br><strong>AquaSafe Emergency System</strong></p>
            </div>";
        
        if (MailHelper::send($toEmail, $subject, $body)) {
            $successCount++;
        } else {
            $failCount++;
        }
    }

    // Background logging for audit
    $statusMsg = "[" . date('Y-m-d H:i:s') . "] Broadcast Complete. Success: $successCount, Failed: $failCount\n";
    file_put_contents(__DIR__ . '/broadcast_debug.log', $statusMsg, FILE_APPEND);
}

/**
 * Detects if an IoT alert should be triggered based on status change and cooldown.
 */
function handleIoTTrigger($link, $sensor_id, $current_status, $current_level) {
    file_put_contents('alert_debug.txt', "[" . date('Y-m-d H:i:s') . "] IoT Trigger: ID=$sensor_id, Status=$current_status, Level=$current_level\n", FILE_APPEND);
    // 1. Ignore SAFE status
    if ($current_status === 'SAFE') return;

    // 2. Lookup Location Name from sensor_status
    // Note: Since one sensor ID might cover multiple locations (Nellimala, etc), 
    // we just pick one or use a generic area name.
    $stmt = mysqli_prepare($link, "SELECT location_name FROM sensor_status WHERE sensor_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $sensor_id); 
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $loc_row = mysqli_fetch_assoc($res);
    $location = $loc_row ? $loc_row['location_name'] : "Idukki Cluster ($sensor_id)";
    mysqli_stmt_close($stmt);
    file_put_contents('alert_debug.txt', "[" . date('Y-m-d H:i:s') . "] Resolved Location: $location\n", FILE_APPEND);

    // 3. Check Cooldown (Don't spam if we already sent THIS status recently)
    // Cooldown: 30 minutes for same status
    $check_sql = "SELECT sent_at FROM alert_history WHERE sensor_id = ? AND status = ? AND sent_at > (NOW() - INTERVAL 30 MINUTE) LIMIT 1";
    $chk_stmt = mysqli_prepare($link, $check_sql);
    mysqli_stmt_bind_param($chk_stmt, "is", $sensor_id, $current_status);
    mysqli_stmt_execute($chk_stmt);
    mysqli_stmt_store_result($chk_stmt);
    
    if (mysqli_stmt_num_rows($chk_stmt) > 0) {
        // Alert was sent recently for this level
        mysqli_stmt_close($chk_stmt);
        return; 
    }
    mysqli_stmt_close($chk_stmt);

    // 4. Record the alert in history
    $log_stmt = mysqli_prepare($link, "INSERT INTO alert_history (sensor_id, status) VALUES (?, ?)");
    mysqli_stmt_bind_param($log_stmt, "is", $sensor_id, $current_status);
    mysqli_stmt_execute($log_stmt);
    mysqli_stmt_close($log_stmt);

    // 5. Trigger the Alert!
    $msg = "Water level has reached $current_level ft ($current_status).";
    file_put_contents('alert_debug.txt', "[" . date('Y-m-d H:i:s') . "] Triggering sendBroadcast (IoT) for $location\n", FILE_APPEND);
    sendBroadcast($link, $location, $msg, $current_status, 'IoT');

    // 6. 🚨 Log to Intelligence Feed 🚨
    $feed_severity = strtolower($current_status);
    if ($feed_severity === 'critical') $feed_severity = 'danger'; // Map to system_notifications schema
    logSystemNotification($link, 'flood', $feed_severity, $location, "Water level changed to $current_status ($current_level ft).", $current_level);
}

/**
 * Logs an event to the system_notifications table (Intelligence Feed)
 */
function logSystemNotification($link, $type, $severity, $location, $message, $water_level = null) {
    // Basic debounce: Don't log the exact same message for the same location within 5 minutes
    $check_sql = "SELECT id FROM system_notifications WHERE type=? AND location=? AND message=? AND timestamp > (NOW() - INTERVAL 5 MINUTE) LIMIT 1";
    $chk_stmt = mysqli_prepare($link, $check_sql);
    mysqli_stmt_bind_param($chk_stmt, "sss", $type, $location, $message);
    mysqli_stmt_execute($chk_stmt);
    mysqli_stmt_store_result($chk_stmt);
    if (mysqli_stmt_num_rows($chk_stmt) > 0) {
        mysqli_stmt_close($chk_stmt);
        return; // Already logged recently
    }
    mysqli_stmt_close($chk_stmt);

    $sql = "INSERT INTO system_notifications (type, severity, location, message, water_level) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($link, $sql);
    if($stmt) {
        mysqli_stmt_bind_param($stmt, "sssss", $type, $severity, $location, $message, $water_level);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}
?>
