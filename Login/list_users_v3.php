<?php
require_once 'config.php';
$table = 'users';
$res = mysqli_query($link, "SELECT id, email, user_role, location FROM `$table` LIMIT 50");
if ($res) {
    echo "ID | Email | Role | Location\n";
    echo "---------------------------------\n";
    while ($row = mysqli_fetch_assoc($res)) {
        echo "{$row['id']} | {$row['email']} | {$row['user_role']} | " . ($row['location'] ?: 'N/A') . "\n";
    }
} else {
    echo "Error: " . mysqli_error($link);
}
?>
