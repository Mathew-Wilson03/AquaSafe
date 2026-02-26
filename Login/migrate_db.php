<?php
require_once 'config.php';
$sql = "ALTER TABLE sensor_alerts ADD COLUMN alert_type VARCHAR(20) DEFAULT 'Admin' AFTER severity";
if (mysqli_query($link, $sql)) {
    echo "Column added successfully";
} else {
    echo "Error adding column: " . mysqli_error($link);
}
?>
