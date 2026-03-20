<?php
// Mock session
session_start();
$_SESSION['email'] = 'mathew.akashwilson@gmail.com'; // From user screenshot
$_SESSION['id'] = 1; // Assuming id 1 is the user

// Output buffering to capture any errors
ob_start();
include 'get_user_safety_data.php';
$output = ob_get_clean();

echo "Response from get_user_safety_data.php:\n";
echo $output . "\n";

// Try to decode
$data = json_decode($output, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "JSON Error: " . json_last_error_msg() . "\n";
} else {
    echo "Decoded Success!\n";
    print_r($data);
}
?>
