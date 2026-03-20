<?php
require_once 'config.php';

echo "Columns in sensor_alerts:\n";
$result = mysqli_query($link, "DESCRIBE sensor_alerts");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "Error: " . mysqli_error($link) . "\n";
}
?>
