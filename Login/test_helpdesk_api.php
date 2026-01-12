<?php
$_SESSION = [
    'loggedin' => true,
    'email' => 'admin@aquasafe.com',
    'name' => 'Admin User',
    'user_role' => 'admin'
];
$_REQUEST['action'] = 'fetch_all';

// Capturing output
ob_start();
include 'manage_helpdesk.php';
$output = ob_get_clean();

echo "API Output:\n";
echo $output;
?>
