<?php
require 'Login/config.php';
echo "--- DESCRIBE flood_data ---\n";
$res = mysqli_query($link, "DESCRIBE flood_data");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        print_r($row);
    }
} else {
    echo "Error: " . mysqli_error($link) . "\n";
}

echo "\n--- DESCRIBE sensor_status ---\n";
$res = mysqli_query($link, "DESCRIBE sensor_status");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        print_r($row);
    }
} else {
    echo "Error: " . mysqli_error($link) . "\n";
}

echo "\n--- DESCRIBE users ---\n";
$res = mysqli_query($link, "DESCRIBE users");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        print_r($row);
    }
} else {
    echo "Error: " . mysqli_error($link) . "\n";
}
?>
