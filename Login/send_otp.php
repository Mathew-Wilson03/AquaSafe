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
                    
                    // 1. Send Email
                    $subject = "Your AquaSafe Verification Code";
                    $message = "Your verification code is: $otp\n\nThis code expires in 24 hours.";
                    $headers = "From: no-reply@aquasafe.com\r\n";
                    $headers .= "Reply-To: support@aquasafe.com\r\n";
                    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                    
                    $mailSent = @mail($email, $subject, $message, $headers);
                    
                    // 2. Local Logging (Fallback for Dev/XAMPP)
                    $logFile = 'email_log.txt';
                    $logMessage = "[" . date('Y-m-d H:i:s') . "] To: $email | OTP: $otp | MailSent: " . ($mailSent ? 'Yes' : 'No') . "\n";
                    file_put_contents($logFile, $logMessage, FILE_APPEND);

                    // Redirect to verification page
                    // Pass email so the user doesn't have to re-type it
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
