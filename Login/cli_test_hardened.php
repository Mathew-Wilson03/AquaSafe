<?php
require_once 'config.php';

// 1. Simulate User Submission
$_SESSION = [
    'loggedin' => true,
    'email' => 'cli_test@aquasafe.com',
    'name' => 'CLI Tester',
    'user_role' => 'user'
];
$_POST = [
    'title' => 'CLI Reality Check',
    'details' => 'Testing the hardened backend'
];
$_REQUEST['action'] = 'submit';

ob_start();
include 'manage_helpdesk.php';
$submit_res = ob_get_clean();
echo "SUBMISSION: $submit_res\n";

// 2. Simulate Admin Fetch
$_SESSION = [
    'loggedin' => true,
    'email' => 'admin_test@aquasafe.com',
    'name' => 'Admin User',
    'user_role' => 'admin'
];
$_REQUEST['action'] = 'fetch_all';

ob_start();
include 'manage_helpdesk.php';
$fetch_res = ob_get_clean();
echo "FETCH: $fetch_res\n";
?>
