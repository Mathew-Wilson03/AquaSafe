<?php
require_once 'config.php';
$search = 'Mathew';
$res = mysqli_query($link, "SELECT email, user_role, location FROM users WHERE name LIKE '%$search%' OR email LIKE '%$search%'");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>
