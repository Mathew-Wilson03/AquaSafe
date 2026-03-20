<?php
session_start();
$_SESSION['email'] = 'admin@aquasafe.com';
$_SESSION['id'] = 1;
$_SESSION['user_role'] = 'Administrator';

include 'get_user_safety_data.php';
?>
