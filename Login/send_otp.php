<?php
// send_otp.php
ob_start();
session_start();
require_once 'config.php';

// Detect table
$table = 'users'; 
try { $r = mysqli_query($link, "SHOW TABLES LIKE 'user'"); if ($r && mysqli_num_rows($r) > 0) $table = 'user'; } catch (Throwable $e) {}

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_otp_btn'])){
    $email = trim($_POST['email']);
    
    // Check if email exists
    $sql = "SELECT id FROM `$table` WHERE email = ?";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if(mysqli_stmt_num_rows($stmt) == 1){
            // Generate 6-digit OTP
            $otp = rand(100000, 999999);
            $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            
            // Ensure columns exist (Self-healing DB)
            try {
                // Suppress errors if columns exist
                @mysqli_query($link, "ALTER TABLE `$table` ADD COLUMN reset_token VARCHAR(255) NULL");
                @mysqli_query($link, "ALTER TABLE `$table` ADD COLUMN reset_expiry DATETIME NULL");
            } catch (Throwable $e) {}
            
            // Store OTP
            // FIX: Use MySQL time (NOW()) for expiry to avoid Timezone mismatches between PHP and MySQL
            // Setting 24 HOUR expiry to be extremely generous for development
            $update = "UPDATE `$table` SET reset_token = ?, reset_expiry = DATE_ADD(NOW(), INTERVAL 24 HOUR) WHERE email = ?";
            if($ustmt = mysqli_prepare($link, $update)){
                mysqli_stmt_bind_param($ustmt, "ss", $otp, $email); // Removed expiry param, using SQL function
                if(mysqli_stmt_execute($ustmt)){
                    
                    // 1. Send Email via PHPMailer
                    require 'vendor/autoload.php';
                    $mailSent = false;
                    $mailError = '';

                    try {
                        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

                        // Server settings
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'mathewwilson2028@mca.ajce.in'; 
                        $mail->Password   = 'xocstgimffcjbvva';
                        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;

                        // Recipients
                        $mail->setFrom('mathewwilson2028@mca.ajce.in', 'AquaSafe Support');
                        $mail->addAddress($email);

                        // Content
                        $mail->isHTML(true);
                        $mail->Subject = 'Your AquaSafe Verification Code';
                        $mail->Body    = "
                            <div style='font-family: sans-serif; padding: 20px; background: #f4f4f4;'>
                                <div style='max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);'>
                                    <h2 style='color: #4ab5c4; text-align: center;'>Verification Code</h2>
                                    <p>Hello,</p>
                                    <p>We received a request to reset your password. Use the verification code below to proceed:</p>
                                    <div style='text-align: center; margin: 30px 0;'>
                                        <div style='display: inline-block; background: #f0f7f8; color: #3a97a5; padding: 15px 40px; border-radius: 8px; font-size: 32px; font-weight: bold; letter-spacing: 5px; border: 2px dashed #4ab5c4;'>
                                            $otp
                                        </div>
                                    </div>
                                    <p>This code expires in 24 hours.</p>
                                    <p style='color: #666; font-size: 13px;'>If you did not request this, please ignore this email.</p>
                                </div>
                            </div>
                        ";
                        $mail->AltBody = "Your AquaSafe verification code is: $otp (Expires in 24 hours)";

                        $mail->send();
                        $mailSent = true;

                    } catch (Exception $e) {
                        $mailError = "Mailer Error: {$mail->ErrorInfo}";
                    }
                    
                    // 2. Local Logging (Fallback for Dev/XAMPP)
                    $logFile = 'email_log.txt';
                    $logMessage = "[" . date('Y-m-d H:i:s') . "] To: $email | OTP: $otp | MailSent: " . ($mailSent ? 'Yes' : 'No') . ($mailSent ? '' : " | Error: $mailError") . "\n";
                    file_put_contents($logFile, $logMessage, FILE_APPEND);

                    // Redirect to verification page
                    header("Location: verify_otp.php?email=" . urlencode($email) . "&sent=true");
                    exit;
                    
                } else {
                    header("Location: forgot_password.php?error=Database error. Please try again.");
                }
            }
        } else {
             // Email not found
             header("Location: forgot_password.php?error=No account found with that email.");
        }
    } else {
         header("Location: forgot_password.php?error=System error.");
    }
}
ob_end_flush();
?>
