<?php
require_once 'config.php';
$result = mysqli_query($link, 'SELECT * FROM notification_settings');
if ($result) {
    while($row = mysqli_fetch_assoc($result)) {
        var_dump($row);
    }
} else {
    echo "Query failed: " . mysqli_error($link);
    // Try system_settings if notification_settings doesn't exist
    $result2 = mysqli_query($link, 'SELECT * FROM system_settings');
    if ($result2) {
        while($row = mysqli_fetch_assoc($result2)) {
            var_dump($row);
        }
    }
}
?>
