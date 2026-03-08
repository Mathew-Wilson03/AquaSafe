<?php
require_once 'config.php';
$res = mysqli_query($link, 'SELECT id, email, location FROM users');
while($row = mysqli_fetch_assoc($res)) {
    echo $row['id'] . ' : ' . $row['email'] . ' : ' . $row['location'] . "\n";
}
?>
