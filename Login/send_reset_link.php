<?php
// send_reset_link.php
ob_start();
session_start();
require_once 'config.php';

// Detect table name (self-healing)
$table = 'users'; 
try { $r = mysqli_query($link, "SHOW TABLES LIKE 'user'"); if ($r && mysqli_num_rows($r) > 0) $table = 'user'; } catch (Throwable $e) {}

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_link_btn'])){
    $email = trim($_POST['email']);
    
    // Check if email exists
    $sql = "SELECT id FROM `$table` WHERE email = ?";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        // Anti-enumeration: We will show the same success message whether the email exists or not.
        // But we only actually generate a token if the email exists.
        
        if(mysqli_stmt_num_rows($stmt) == 1){
            // 1. Generate Secure Token (32 bytes hex = 64 chars)
            $token = bin2hex(random_bytes(32));
            
            // 2. Ensure columns exist (Self-healing DB)
            try {
                @mysqli_query($link, "ALTER TABLE `$table` ADD COLUMN reset_token VARCHAR(255) NULL");
                @mysqli_query($link, "ALTER TABLE `$table` ADD COLUMN reset_expiry DATETIME NULL");
            } catch (Throwable $e) {}
            
            // 3. Store Token + Expiry (15 mins)
            $update = "UPDATE `$table` SET reset_token = ?, reset_expiry = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE email = ?";
            if($ustmt = mysqli_prepare($link, $update)){
                mysqli_stmt_bind_param($ustmt, "ss", $token, $email);
                if(mysqli_stmt_execute($ustmt)){
                    
                    // 4. Construct Link
                    // Auto-detect host to work on any XAMPP setup
                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                    $host = $_SERVER['HTTP_HOST']; // localhost
                    $path = dirname($_SERVER['PHP_SELF']); // /AquaSafe/Login
                    $resetLink = "$protocol://$host$path/reset_password.php?token=$token";
                    
                    // 5. Send Email via PHPMailer (Gmail SMTP)
                    // Load Composer's autoloader
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
                        $mail->Password   = 'pemz qqqx aotl ntfu';
                        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;

                        // Recipients
                        $mail->setFrom('mathewwilson2028@mca.ajce.in', 'AquaSafe Support');
                        $mail->addAddress($email);

                        // Content
                        $mail->isHTML(true);
                        $mail->Subject = 'Reset Your Password - AquaSafe';
                        $mail->Body    = "
                            <div style='font-family: sans-serif; padding: 20px; background: #f4f4f4;'>
                                <div style='max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);'>
                                    <h2 style='color: #4ab5c4; text-align: center;'>Reset Password</h2>
                                    <p>Hello,</p>
                                    <p>We received a request to reset your password. Click the button below to proceed:</p>
                                    <div style='text-align: center; margin: 30px 0;'>
                                        <a href='$resetLink' style='background: #4ab5c4; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Reset Password</a>
                                    </div>
                                    <p style='color: #666; font-size: 13px;'>If the button doesn't work, copy this link:<br>$resetLink</p>
                                    <p>This link expires in 15 minutes.</p>
                                </div>
                            </div>
                        ";
                        $mail->AltBody = "Click here to reset your password: $resetLink (Expires in 15 mins)";

                        $mail->send();
                        $mailSent = true;

                    } catch (Exception $e) {
                         // Mail failed - we catch it so we can still log it for localhost testing
                         $mailError = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                    }
                    
                    // 6. LOGGING (Backup & Localhost Verification)
                    $logFile = 'email_log.txt';
                    $logEntry = str_repeat("-", 50) . "\n";
                    $logEntry .= "[" . date('Y-m-d H:i:s') . "] Reset Link Generated\n";
                    $logEntry .= "Email: $email\n";
                    $logEntry .= "Link: $resetLink\n";
                    $logEntry .= "SMTP Status: " . ($mailSent ? "Sent Successfully" : "Failed (Config needed)") . "\n";
                    if(!$mailSent) $logEntry .= "Error: $mailError\n";
                    $logEntry .= str_repeat("-", 50) . "\n\n";
                    file_put_contents($logFile, $logEntry, FILE_APPEND);
                }
            }
        }
        
        // Show success message regardless (Security Best Practice)
        header("Location: forgot_password.php?error=If this email is registered, a reset link has been sent.");
        exit;
        
    } else {
        header("Location: forgot_password.php?error=System error. Please try again.");
    }
} else {
    header("Location: forgot_password.php");
}
ob_end_flush();
?>
