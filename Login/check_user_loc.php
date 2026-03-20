<?php
require_once 'config.php';
$email = 'mathew.akashwilson@gmail.com';
$table = 'users'; 
$r = mysqli_query($link, "SHOW TABLES LIKE 'user'");
if ($r && mysqli_num_rows($r) > 0) $table = 'user';

$res = mysqli_query($link, "SELECT id, email, location FROM `$table` WHERE email = '$email'");
$row = mysqli_fetch_assoc($res);
print_r($row);
?>
