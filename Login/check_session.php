<?php
session_start();
echo "SESSION EMAIL: [" . ($_SESSION['email'] ?? 'NOT SET') . "]\n";
echo "SESSION ID: [" . ($_SESSION['id'] ?? 'NOT SET') . "]\n";
echo "SESSION ROLE: [" . ($_SESSION['user_role'] ?? 'NOT SET') . "]\n";
?>
