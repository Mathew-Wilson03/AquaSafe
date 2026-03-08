<?php
require_once 'config.php';

header('Content-Type: text/plain');

$tables = ['flood_data', 'sensor_status', 'sensor_alerts', 'notification_history', 'users', 'user'];

foreach ($tables as $table) {
    echo "--- Table: $table ---\n";
    $res = mysqli_query($link, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($res) == 0) {
        echo "Table does not exist.\n\n";
        continue;
    }

    $count_res = mysqli_query($link, "SELECT COUNT(*) as cnt FROM `$table` ");
    $count = mysqli_fetch_assoc($count_res)['cnt'];
    echo "Row Count: $count\n";

    echo "Indexes:\n";
    $index_res = mysqli_query($link, "SHOW INDEX FROM `$table` ");
    while ($row = mysqli_fetch_assoc($index_res)) {
        echo "  - {$row['Key_name']} ({$row['Column_name']})\n";
    }
    echo "\n";
}

echo "--- Recent Queries Test ---\n";
$start = microtime(true);
mysqli_query($link, "SELECT * FROM flood_data ORDER BY created_at DESC LIMIT 20");
echo "flood_data (20 recent): " . (microtime(true) - $start) . "s\n";

$start = microtime(true);
mysqli_query($link, "SELECT * FROM sensor_alerts ORDER BY timestamp DESC LIMIT 1");
echo "sensor_alerts (recent): " . (microtime(true) - $start) . "s\n";

?>
