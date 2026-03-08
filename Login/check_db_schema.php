<?php
require_once 'config.php';
$tables = ['sensor_alerts', 'sensor_status', 'flood_data', 'system_notifications', 'notification_history'];
foreach ($tables as $table) {
    echo "--- Table: $table ---\n";
    $result = mysqli_query($link, "DESCRIBE $table");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            print_r($row);
        }
    } else {
        echo "Table does not exist.\n";
    }
}
?>
