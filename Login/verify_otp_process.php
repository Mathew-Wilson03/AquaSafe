<?php
// verify_otp_process.php
ob_start();
session_start();
require_once 'config.php';

$table = 'users'; 
try { $r = mysqli_query($link, "SHOW TABLES LIKE 'user'"); if ($r && mysqli_num_rows($r) > 0) $table = 'user'; } catch (Throwable $e) {}

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_btn'])){
    $email = trim($_POST['email']);
    $otp = trim($_POST['otp']);
    
    // Validate OTP
    $sql = "SELECT id FROM `$table` WHERE email = ? AND reset_token = ? AND reset_expiry > NOW()";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "ss", $email, $otp);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if(mysqli_stmt_num_rows($stmt) == 1){
            // Code is valid!
            // Mark session as verified for this email
            $_SESSION['reset_verified_email'] = $email;
            
            // Redirect to reset password (no token needed now, we trust session)
            header("Location: reset_password.php");
            exit;
        } else {
             header("Location: verify_otp.php?email=".urlencode($email)."&error=Invalid or expired code.");
        }
    } else {
         header("Location: verify_otp.php?email=".urlencode($email)."&error=System error.");
    }
}
ob_end_flush();
?>
