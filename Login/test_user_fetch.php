<?php
session_start();
// Mock user login
$_SESSION['email'] = 'test@example.com';
$_SESSION['id'] = 1; // assume user id 1 exists
require 'get_user_safety_data.php';
?>
