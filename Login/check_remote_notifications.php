<?php
require_once 'config.php';

echo "Columns in notification_history:\n";
$result = mysqli_query($link, "DESCRIBE notification_history");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "Error: " . mysqli_error($link) . "\n";
}

echo "\nLatest notifications:\n";
$result = mysqli_query($link, "SELECT * FROM notification_history ORDER BY created_at DESC LIMIT 5");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "- " . $row['created_at'] . " | " . $row['message'] . "\n";
    }
}
?>
