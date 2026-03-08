<?php
require_once 'config.php';

echo "--- flood_data Schema ---\n";
$res = mysqli_query($link, "DESCRIBE flood_data");
while($row = mysqli_fetch_assoc($res)) {
    echo "{$row['Field']} ({$row['Type']})\n";
}

echo "\n--- Recent flood_data (no filter) ---\n";
$res = mysqli_query($link, "SELECT * FROM flood_data ORDER BY id DESC LIMIT 5");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}

echo "\n--- PHP Date: " . date('Y-m-d') . " ---\n";
$res = mysqli_query($link, "SELECT COUNT(*) as cnt FROM flood_data WHERE DATE(created_at) = '" . date('Y-m-d') . "'");
echo "Rows with PHP Date: " . mysqli_fetch_assoc($res)['cnt'] . "\n";

echo "\n--- MySQL Date ---\n";
$res = mysqli_query($link, "SELECT CURDATE() as curdate, COUNT(*) as cnt FROM flood_data WHERE DATE(created_at) = CURDATE()");
$row = mysqli_fetch_assoc($res);
echo "MySQL CURDATE: " . $row['curdate'] . "\n";
echo "Rows with MySQL Date: " . $row['cnt'] . "\n";

?>
