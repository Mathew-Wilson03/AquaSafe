<?php
require_once 'config.php';

// 1. Simulate User Submission
$_SESSION = [
    'loggedin' => true,
    'email' => 'user_test@example.com',
    'name' => 'Test User',
    'user_role' => 'user'
];
$_POST = [
    'title' => 'Emergency Sync Test',
    'details' => 'Testing if admin receives this'
];
$_REQUEST['action'] = 'submit';

ob_start();
include 'manage_helpdesk.php';
$submit_res = ob_get_clean();
echo "SUBMISSION RESULT: $submit_res\n";

// 2. Simulate Admin Fetch
$_SESSION = [
    'loggedin' => true,
    'email' => 'admin_test@example.com',
    'name' => 'Admin User',
    'user_role' => 'admin'
];
$_REQUEST['action'] = 'fetch_all';

ob_start();
include 'manage_helpdesk.php';
$fetch_res = ob_get_clean();
echo "FETCH RESULT: $fetch_res\n";
?>
