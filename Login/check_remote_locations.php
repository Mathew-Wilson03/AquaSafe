<?php
require_once 'config.php';

echo "Locations in flood_data:\n";
$result = mysqli_query($link, "SELECT DISTINCT location FROM flood_data");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "- " . ($row['location'] ?: '[NULL]') . "\n";
    }
} else {
    echo "Error: " . mysqli_error($link) . "\n";
}

echo "\nLatest data with location:\n";
$result = mysqli_query($link, "SELECT created_at, level, location FROM flood_data ORDER BY created_at DESC LIMIT 10");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "- " . $row['created_at'] . " | Level: " . $row['level'] . " | Location: " . $row['location'] . "\n";
    }
}
?>
