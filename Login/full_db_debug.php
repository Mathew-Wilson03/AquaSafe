<?php
require_once 'config.php';

$tables = ['sensor_status', 'sensor_locations', 'flood_data', 'sensor_alerts'];

foreach ($tables as $table) {
    echo "\n=== TABLE: $table ===\n";
    $res = mysqli_query($link, "DESCRIBE $table");
    if ($res) {
        while($row = mysqli_fetch_assoc($res)) {
            echo "{$row['Field']} ({$row['Type']}) - {$row['Key']}\n";
        }
    } else {
        echo "Table does not exist or error: " . mysqli_error($link) . "\n";
    }

    echo "\n--- DATA (Latest 3) ---\n";
    $res = mysqli_query($link, "SELECT * FROM $table ORDER BY 1 DESC LIMIT 3");
    if ($res) {
        while($row = mysqli_fetch_assoc($res)) {
            print_r($row);
        }
    }
}
?>
