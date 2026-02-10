<?php
// test_email.php
require 'vendor/autoload.php';

if (!file_exists('config_secrets.php')) {
    die("Error: config_secrets.php not found.\n");
}

require_once 'config_secrets.php';

echo "Testing Email...\n";
echo "Host: " . SMTP_HOST . "\n";
echo "Port: " . SMTP_PORT . "\n";
echo "User: " . SMTP_USER . "\n";

$mail = new PHPMailer\PHPMailer\PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USER;
    $mail->Password = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = SMTP_PORT;
    $mail->setFrom(SMTP_FROM_EMAIL, 'AquaSafe Test');
    
    // Send to self/admin for test
    $mail->addAddress(SMTP_FROM_EMAIL); 
    
    $mail->isHTML(true);
    $mail->Subject = "Test Email from CLI";
    $mail->Body = "This is a test email sent from the command line debugging script.";

    $mail->send();
    echo "Message has been sent successfully.\n";
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}\n";
}
?>
