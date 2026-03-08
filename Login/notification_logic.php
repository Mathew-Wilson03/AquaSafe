<?php
// notification_logic.php - IoT Intelligence Center Logic

require_once 'config.php';
require_once 'alert_utils.php';

/**
 * Fetches notification settings from the database.
 * 
 * @param mysqli $link Database connection
 * @return array Associative array of settings
 */
function getNotificationSettings($link) {
    $settings = [];
    $result = mysqli_query($link, "SELECT setting_key, setting_value FROM notification_settings");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    // Defaults if not found
    return array_merge([
        'threshold_safe_max' => '10.00',
        'threshold_warning_max' => '18.00',
        'channel_sms' => '1',
        'channel_email' => '1',
        'channel_push' => '1',
        'channel_siren' => '1'
    ], $settings);
}

/**
 * Classifies severity based on water level and dynamic thresholds.
 * 
 * @param float $level Water level in ft
 * @param array $settings Threshold settings
 * @return string Safe, Warning, or Critical
 */
function classifySeverity($level, $settings) {
    $safe_max = floatval($settings['threshold_safe_max']);
    $warning_max = floatval($settings['threshold_warning_max']);

    if ($level <= $safe_max) {
        return 'Safe';
    } elseif ($level <= $warning_max) {
        return 'Warning';
    } else {
        return 'Critical';
    }
}

/**
 * Processes a new IoT data point and logs a notification if status changes.
 * 
 * @param mysqli $link Database connection
 * @param string $sensor_id Sensor ID
 * @param string $location Location name
 * @param float $level Water level
 */
function processIoTNotification($link, $sensor_id, $location, $level) {
    $settings = getNotificationSettings($link);
    $new_severity = classifySeverity($level, $settings);

    // 1. Get previous severity to detect change
    $prev_sql = "SELECT severity FROM notification_history WHERE sensor_id = ? ORDER BY created_at DESC LIMIT 1";
    $prev_stmt = mysqli_prepare($link, $prev_sql);
    mysqli_stmt_bind_param($prev_stmt, "s", $sensor_id);
    mysqli_stmt_execute($prev_stmt);
    $prev_res = mysqli_stmt_get_result($prev_stmt);
    $prev_row = mysqli_fetch_assoc($prev_res);
    $prev_severity = $prev_row ? $prev_row['severity'] : 'Unknown';
    mysqli_stmt_close($prev_stmt);

    // 2. Only handle if severity changed OR first record
    if ($new_severity !== $prev_severity) {
        $message = "Water level at $location is now $new_severity ($level ft).";
        
        // 3. Log to notification_history
        $log_sql = "INSERT INTO notification_history (sensor_id, location, water_level, severity, message) VALUES (?, ?, ?, ?, ?)";
        $log_stmt = mysqli_prepare($link, $log_sql);
        mysqli_stmt_bind_param($log_stmt, "ssdss", $sensor_id, $location, $level, $new_severity, $message);
        mysqli_stmt_execute($log_stmt);
        mysqli_stmt_close($log_stmt);

        // 4. Trigger Alerts via Channels
        // If it's Warning or Critical, we might want to send external alerts
        if ($new_severity !== 'Safe') {
            // Check delivery channels
            if ($settings['channel_email'] == '1') {
                // We use sendBroadcast from alert_utils.php but wrap it with channel checks
                // For now, sendBroadcast handles its own recipient list and levels.
                // We can extend it or use it as is if it matches requirements.
                sendBroadcast($link, $location, $message, strtoupper($new_severity), 'IoT');
            }
            
            // Note: SMS, Push, and Siren are stubs for now or can be logged
            if ($settings['channel_sms'] == '1') error_log("SMS Alert: $message");
            if ($settings['channel_push'] == '1') error_log("Push Alert: $message");
            if ($settings['channel_siren'] == '1') error_log("Siren Alert: $message");
        }
    }
}
?>
