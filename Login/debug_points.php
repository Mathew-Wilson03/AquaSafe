<?php
require_once 'config.php';
header('Content-Type: text/plain');
$sql = "SELECT * FROM evacuation_points";
$result = mysqli_query($link, $sql);
if (!$result) {
    die("Error: " . mysqli_error($link));
}
echo "Total Points: " . mysqli_num_rows($result) . "\n\n";
while ($row = mysqli_fetch_assoc($result)) {
    echo "ID: " . $row['id'] . "\n";
    echo "Name: " . $row['name'] . "\n";
    echo "Lat: [" . $row['latitude'] . "]\n";
    echo "Lng: [" . $row['longitude'] . "]\n";
    echo "Status: " . $row['status'] . "\n";
    echo "-------------------\n";
}
?>
