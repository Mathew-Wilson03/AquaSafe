<?php
// test_mail_stabilization.php
require_once 'Login/config.php';
require_once 'Login/MailHelper.php';

echo "--- AquaSafe Email Stabilization Test ---\n";

// Mocking Railway Environment
$_SERVER['RAILWAY_ENVIRONMENT'] = 'production';
echo "Mocking RAILWAY_ENVIRONMENT=production\n";

// Test 1: Connectivity Check
echo "Testing SMTP Connectivity check...\n";
$reflection = new ReflectionClass('MailHelper');
$method = $reflection->getMethod('checkSmtpConnectivity');
$method->setAccessible(true);
$result = $method->invoke(null);

echo "Result: " . ($result ? "CONNECTED" : "UNREACHABLE") . "\n";
echo "Static isRailway: " . ($reflection->getStaticPropertyValue('isRailway') ? "Yes" : "No") . "\n";

// Test 2: Fallback logic
echo "\nTesting MailHelper::send fallback...\n";
// This should fail SMTP (due to mock environment/port block) and try HTTP fallback (if API key mocked)
$success = MailHelper::send('test@example.com', 'Test Subject', 'Test Body');
echo "Overall Send Success: " . ($success ? "TRUE" : "FALSE") . "\n";

echo "\nTest Complete. Check error_log for 'SMTP unreachable in Railway Environment' message.\n";
?>
