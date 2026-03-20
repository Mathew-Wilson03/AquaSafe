<?php
require_once 'config.php';

echo "Users in Railway DB:\n";
$result = mysqli_query($link, "SELECT id, name, email, role FROM users LIMIT 5");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "- " . $row['name'] . " (" . $row['email'] . ") Role: " . $row['role'] . "\n";
    }
} else {
    // Fallback if table name is different
    $result = mysqli_query($link, "SELECT id, name, email, user_role FROM user LIMIT 5");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo "- " . $row['name'] . " (" . $row['email'] . ") Role: " . $row['user_role'] . "\n";
        }
    } else {
        echo "Error: " . mysqli_error($link) . "\n";
    }
}
?>
