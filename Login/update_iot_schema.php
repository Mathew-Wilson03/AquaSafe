<?php
require_once 'config.php';

echo "<h3>Updating flood_data Schema...</h3>";

// 1. Alter sensor_id to VARCHAR(64)
$q1 = "ALTER TABLE flood_data MODIFY sensor_id VARCHAR(64) NOT NULL";
if (mysqli_query($link, $q1)) {
    echo "<p style='color:green;'>SUCCESS: sensor_id altered to VARCHAR.</p>";
} else {
    echo "<p style='color:red;'>ERROR: " . mysqli_error($link) . "</p>";
}

// 2. Add location column
$q2 = "ALTER TABLE flood_data ADD COLUMN location VARCHAR(255) DEFAULT 'Unknown Cluster' AFTER sensor_id";
if (mysqli_query($link, $q2)) {
    echo "<p style='color:green;'>SUCCESS: location column added.</p>";
} else {
    // Check if column already exists
    if (strpos(mysqli_error($link), 'Duplicate column name') !== false) {
        echo "<p style='color:orange;'>NOTE: location column already exists.</p>";
    } else {
        echo "<p style='color:red;'>ERROR: " . mysqli_error($link) . "</p>";
    }
}

// 3. Fix existing data if possible (Setting existing 0 values to SNS-001)
$q3 = "UPDATE flood_data SET sensor_id = 'SNS-001' WHERE sensor_id = '0'";
if (mysqli_query($link, $q3)) {
    echo "<p style='color:green;'>SUCCESS: Legacy data updated.</p>";
}

echo "<p style='color:blue;'>Schema update complete.</p>";
?>
