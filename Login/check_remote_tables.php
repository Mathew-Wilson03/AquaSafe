<?php
require_once 'config.php';

echo "Tables in " . DB_NAME . ":\n";
$result = mysqli_query($link, "SHOW TABLES");
if ($result) {
    while ($row = mysqli_fetch_array($result)) {
        echo "- " . $row[0] . "\n";
    }
} else {
    echo "Error: " . mysqli_error($link) . "\n";
}
?>
