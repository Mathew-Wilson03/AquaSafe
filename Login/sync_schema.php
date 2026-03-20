<?php
/**
 * AquaSafe Schema Synchronization Tool
 */
require_once 'config.php';

echo "<h2>AquaSafe Schema Sync</h2>";
echo "<p>Connected to: " . DB_SERVER . " | DB: " . DB_NAME . "</p>";

function checkAndFix($link, $table, $oldCol, $newCol, $definition) {
    echo "<h3>Checking table: $table</h3>";
    $res = mysqli_query($link, "SHOW COLUMNS FROM `$table` LIKE '$newCol'");
    if (mysqli_num_rows($res) > 0) {
        echo "<p style='color:green;'>✅ Column '$newCol' already exists in '$table'.</p>";
        return;
    }

    $resOld = mysqli_query($link, "SHOW COLUMNS FROM `$table` LIKE '$oldCol'");
    if (mysqli_num_rows($resOld) > 0) {
        echo "<p style='color:orange;'>⚠️ Column '$oldCol' found. Renaming to '$newCol'...</p>";
        $sql = "ALTER TABLE `$table` CHANGE `$oldCol` `$newCol` $definition";
        if (mysqli_query($link, $sql)) {
            echo "<p style='color:green;'>✅ SUCCESS: Renamed '$oldCol' to '$newCol'.</p>";
        } else {
            echo "<p style='color:red;'>❌ ERROR: " . mysqli_error($link) . "</p>";
        }
    } else {
        echo "<p style='color:blue;'>ℹ️ Neither '$oldCol' nor '$newCol' found. Adding '$newCol'...</p>";
        $sql = "ALTER TABLE `$table` ADD COLUMN `$newCol` $definition";
        if (mysqli_query($link, $sql)) {
            echo "<p style='color:green;'>✅ SUCCESS: Added column '$newCol'.</p>";
        } else {
            echo "<p style='color:red;'>❌ ERROR: " . mysqli_error($link) . "</p>";
        }
    }
}

checkAndFix($link, 'flood_data', 'water_level', 'level', "DECIMAL(10,2) NOT NULL");
checkAndFix($link, 'flood_data', 'timestamp', 'created_at', "TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

echo "<h3>Checking table: sensor_status</h3>";
$resSS = mysqli_query($link, "SHOW COLUMNS FROM `sensor_status` LIKE 'water_level'");
if (mysqli_num_rows($resSS) == 0) {
    echo "<p style='color:orange;'>⚠️ Column 'water_level' missing in sensor_status. Adding...</p>";
    if (mysqli_query($link, "ALTER TABLE sensor_status ADD COLUMN water_level DECIMAL(10,2) DEFAULT 0.00")) {
        echo "<p style='color:green;'>✅ SUCCESS: Added water_level to sensor_status.</p>";
    }
} else {
    echo "<p style='color:green;'>✅ Column 'water_level' exists in sensor_status.</p>";
}

echo "<hr><p><b>Sync complete.</b> Please check your dashboard.</p>";
?>
