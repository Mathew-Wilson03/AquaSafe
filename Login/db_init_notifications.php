<?php
require_once 'config.php';

$queries = [
    "CREATE TABLE IF NOT EXISTS notification_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sensor_id VARCHAR(64) NOT NULL,
        location VARCHAR(255) NOT NULL,
        water_level DECIMAL(10,2) NOT NULL,
        severity ENUM('Safe', 'Warning', 'Critical') NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS notification_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(64) UNIQUE NOT NULL,
        setting_value TEXT NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

echo "<h3>Initializing Notification Database...</h3>";

foreach ($queries as $sql) {
    if (mysqli_query($link, $sql)) {
        echo "<p style='color:green;'>SUCCESS: Table operation successful.</p>";
    } else {
        echo "<p style='color:red;'>ERROR: " . mysqli_error($link) . "</p>";
    }
}

// Seed default settings if not exists
$defaults = [
    'threshold_safe_max' => '10.00',
    'threshold_warning_max' => '18.00',
    'channel_sms' => '1',
    'channel_email' => '1',
    'channel_push' => '1',
    'channel_siren' => '1'
];

foreach ($defaults as $key => $val) {
    $check = mysqli_query($link, "SELECT id FROM notification_settings WHERE setting_key = '$key'");
    if ($check && mysqli_num_rows($check) == 0) {
        mysqli_query($link, "INSERT INTO notification_settings (setting_key, setting_value) VALUES ('$key', '$val')");
    } elseif (!$check) {
        echo "<p style='color:red;'>ERROR fetching setting $key: " . mysqli_error($link) . "</p>";
    }
}

echo "<p style='color:blue;'>Default settings seeded.</p>";
?>
