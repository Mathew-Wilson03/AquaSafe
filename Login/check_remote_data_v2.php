<?php
require_once 'config.php';

echo "Latest Flood Data in Railway DB (Fixed Columns):\n";
$result = mysqli_query($link, "SELECT * FROM flood_data ORDER BY created_at DESC LIMIT 5");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "- Created: " . $row['created_at'] . " | Level: " . $row['level'] . " | Status: " . $row['status'] . "\n";
    }
} else {
    echo "Error: " . mysqli_error($link) . "\n";
}
?>
