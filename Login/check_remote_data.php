<?php
require_once 'config.php';

echo "Latest Flood Data in Railway DB:\n";
$result = mysqli_query($link, "SELECT * FROM flood_data ORDER BY timestamp DESC LIMIT 5");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "- Timestamp: " . $row['timestamp'] . " | Level: " . $row['water_level'] . " | Status: " . $row['status'] . "\n";
    }
} else {
    echo "Error: " . mysqli_error($link) . "\n";
}
?>
