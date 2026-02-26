<?php
require_once 'config.php';
$result = mysqli_query($link, 'SELECT id, level, status, created_at FROM flood_data ORDER BY created_at DESC LIMIT 5');
if ($result) {
    while($row = mysqli_fetch_assoc($result)) {
        echo "[{$row['created_at']}] ID: {$row['id']} Level: {$row['level']} Status: {$row['status']}\n";
    }
} else {
    echo "Query failed: " . mysqli_error($link);
}
?>
