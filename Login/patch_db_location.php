<?php
require_once 'config.php';

// Check if location column exists
$check = mysqli_query($link, "SHOW COLUMNS FROM sensor_alerts LIKE 'location'");
if (mysqli_num_rows($check) == 0) {
    // Add column
    $sql = "ALTER TABLE sensor_alerts ADD COLUMN location VARCHAR(100) DEFAULT 'System Wide' AFTER message";
    if (mysqli_query($link, $sql)) {
        echo "✅ Successfully added 'location' column to sensor_alerts table.\n";
    } else {
        echo "❌ Error adding column: " . mysqli_error($link) . "\n";
    }
} else {
    echo "ℹ️ 'location' column already exists.\n";
}

mysqli_close($link);
?>
