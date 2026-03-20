<?php
require_once 'config.php';

$res = mysqli_query($link, "SELECT * FROM evacuation_points LIMIT 1");
if ($res) {
    $row = mysqli_fetch_assoc($res);
    echo "Columns in evacuation_points:\n";
    print_r(array_keys($row));
} else {
    echo "Error: " . mysqli_error($link) . "\n";
}
?>
