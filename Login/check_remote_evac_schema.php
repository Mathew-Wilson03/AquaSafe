<?php
require_once 'config.php';

echo "Columns in evacuation_points:\n";
$result = mysqli_query($link, "DESCRIBE evacuation_points");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "Error: " . mysqli_error($link) . "\n";
}
?>
