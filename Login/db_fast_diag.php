<?php
require_once 'config.php';
header('Content-Type: text/plain');

function check_table($link, $table) {
    $start = microtime(true);
    $res = mysqli_query($link, "SELECT COUNT(*) as cnt FROM `$table` ");
    if (!$res) {
        echo "Error checking $table: " . mysqli_error($link) . "\n";
        return;
    }
    $row = mysqli_fetch_assoc($res);
    $time = microtime(true) - $start;
    echo "Table: $table | Count: {$row['cnt']} | Time: " . round($time, 4) . "s\n";
}

$tables = ['flood_data', 'sensor_status', 'sensor_alerts', 'notification_history', 'users', 'user'];
foreach ($tables as $t) {
    $res = mysqli_query($link, "SHOW TABLES LIKE '$t'");
    if (mysqli_num_rows($res) > 0) {
        check_table($link, $t);
    } else {
        echo "Table: $t | Result: Not Found\n";
    }
}

echo "\n--- PROCESS LIST ---\n";
$res = mysqli_query($link, "SHOW FULL PROCESSLIST");
while ($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>
