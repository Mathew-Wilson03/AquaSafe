<?php
// alert_utils.php - Unified Alert System for AquaSafe

require_once 'config.php';
require_once 'config_secrets.php'; 
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

    // 3. Email Send (Background Context)
    $emailLogFile = 'emergency_email_log.txt';
    $logContent = "--- ALERT: " . date('Y-m-d H:i:s') . " ---\n";
    $logContent .= "Subject: " . "⚠️ $severity Alert: $area" . "\n";
    $logContent .= "Recipients: " . implode(', ', $recipients) . "\n";
    $logContent .= "Body: " . $message . "\n";
    $logContent .= "--------------------------------------\n\n";
    
    // Always log for debugging/audit
    file_put_contents($emailLogFile, $logContent, FILE_APPEND);

    // If SMTP pass is default/placeholder, don't try to send via PHPMailer to avoid hangs/errors
    if (SMTP_PASS === 'your_app_password_here' || empty(SMTP_PASS)) {
        error_log("SMTP Credentials not configured. Alert logged to $emailLogFile");
        return; 
    }
    
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->isHTML(true);
        $mail->Subject = "⚠️ $severity Alert: $area";

        foreach($recipients as $toEmail) {
            try {
                $mail->clearAddresses();
                $mail->addAddress($toEmail);
                $mail->Body = "
                    <div style='font-family: Arial, sans-serif; border: 2px solid #e74c3c; padding: 20px; border-radius: 10px;'>
                        <h2 style='color: #e74c3c;'>$severity Flood Alert</h2>
                        <p><strong>Location:</strong> $area</p>
                        <p><strong>Status:</strong> $message</p>
                        <p style='background: #fdf2f2; padding: 10px; border-left: 5px solid #e74c3c;'>
                            Please stay tuned to local news and follow evacuation orders if issued.
                        </p>
                        <p>Stay Safe,<br><strong>AquaSafe Emergency System</strong></p>
                    </div>";
                $mail->send();
            } catch (Exception $e) {
                error_log("Email failed for $toEmail: " . $mail->ErrorInfo);
            }
        }
    } catch (Exception $e) {
        error_log("Mailer Setup failed: " . $e->getMessage());
    }
}

/**
 * Detects if an IoT alert should be triggered based on status change and cooldown.
 */
function handleIoTTrigger($link, $sensor_id, $current_status, $current_level) {
    file_put_contents('alert_debug.txt', "[" . date('Y-m-d H:i:s') . "] IoT Trigger: ID=$sensor_id, Status=$current_status, Level=$current_level\n", FILE_APPEND);
    // 1. Ignore SAFE status
    if ($current_status === 'SAFE') return;

    // 2. Lookup Location Name
    $stmt = mysqli_prepare($link, "SELECT location_name FROM sensor_locations WHERE sensor_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $sensor_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $loc_row = mysqli_fetch_assoc($res);
    $location = $loc_row ? $loc_row['location_name'] : "Unknown Sensor $sensor_id";
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
}
?>
