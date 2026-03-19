<?php
require_once 'config.php';
$tables = ['users', 'user'];
foreach ($tables as $table) {
    echo "--- Table: $table ---\n";
    $r = mysqli_query($link, "SHOW TABLES LIKE '$table'");
    if ($r && mysqli_num_rows($r) > 0) {
        $res = mysqli_query($link, "SELECT id, email, location FROM `$table` LIMIT 20");
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                echo "ID: {$row['id']} | Email: {$row['email']} | Location: " . ($row['location'] ?: '[NULL]') . "\n";
            }
        } else {
            echo "Error querying $table: " . mysqli_error($link) . "\n";
        }
    } else {
        echo "Table $table does not exist.\n";
    }
}
?>
