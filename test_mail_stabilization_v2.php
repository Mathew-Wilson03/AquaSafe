<?php
// test_mail_stabilization_v2.php

// Define constants that config.php would normally define
if (!defined('SMTP_HOST')) define('SMTP_HOST', 'smtp.gmail.com');
if (!defined('SMTP_PORT')) define('SMTP_PORT', '587');
if (!defined('MAIL_TIMEOUT')) define('MAIL_TIMEOUT', 2);
if (!defined('ENABLE_EMAIL')) define('ENABLE_EMAIL', true);
if (!defined('MAIL_DRIVER')) define('MAIL_DRIVER', 'smtp');

require_once 'Login/MailHelper.php';

echo "--- AquaSafe Email Stabilization Test v2 ---\n";

// Mocking Railway Environment
$_SERVER['RAILWAY_ENVIRONMENT'] = 'production';
echo "Mocking RAILWAY_ENVIRONMENT=production\n";

// Test 1: Connectivity Check (Internal Method)
echo "Testing SMTP Connectivity check...\n";
$reflection = new ReflectionClass('MailHelper');
$method = $reflection->getMethod('checkSmtpConnectivity');
$method->setAccessible(true);
$result = $method->invoke(null);

echo "Result: " . ($result ? "CONNECTED" : "UNREACHABLE") . "\n";
$isRailway = $reflection->getProperty('isRailway');
$isRailway->setAccessible(true);
echo "Static isRailway: " . ($isRailway->getValue() ? "Yes" : "No") . "\n";

// Test 2: Fallback logic simulation
echo "\nTesting MailHelper::send fallback logic...\n";
// This should utilize the cached result and skip SMTP
$success = MailHelper::send('test@example.com', 'Test Subject', 'Test Body');
echo "Overall Send Result: " . ($success ? "TRUE (Handled/Fallback)" : "FALSE (Failed/Blocked)") . "\n";

echo "\nTest Complete. If 'UNREACHABLE' was shown and Overall Send was FALSE (because no API key), then logic is PERFECT.\n";
?>
