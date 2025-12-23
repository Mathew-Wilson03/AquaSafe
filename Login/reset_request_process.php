<?php
// reset_request_process.php
ob_start();
session_start();
require_once 'config.php';

// Detect table like other files
$table = 'users'; 
try { $r = mysqli_query($link, "SHOW TABLES LIKE 'user'"); if ($r && mysqli_num_rows($r) > 0) $table = 'user'; } catch (Throwable $e) {}

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_request_btn'])){
    $email = trim($_POST['email']);
    
    // Check if email exists
    $sql = "SELECT id FROM `$table` WHERE email = ?";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if(mysqli_stmt_num_rows($stmt) == 1){
            // Generate Token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Add columns if they don't exist (Quick DB Migration)
            // Ideally this should be done once manually, but fitting the 'self-healing' approach
            try {
                mysqli_query($link, "ALTER TABLE `$table` ADD COLUMN reset_token VARCHAR(255) NULL");
                mysqli_query($link, "ALTER TABLE `$table` ADD COLUMN reset_expiry DATETIME NULL");
            } catch (Throwable $e) { /* Assume columns might exist */ }
            
            // Update User with Token
            $update = "UPDATE `$table` SET reset_token = ?, reset_expiry = ? WHERE email = ?";
            if($ustmt = mysqli_prepare($link, $update)){
                mysqli_stmt_bind_param($ustmt, "sss", $token, $expiry, $email);
                if(mysqli_stmt_execute($ustmt)){
                    // SIMULATE EMAIL SENDING
                    // In a real app, use mail() or PHPMailer here.
                    // For local dev, we redirect to a page showing the link.
                    header("Location: reset_simulation.php?token=$token&email=" . urlencode($email));
                    exit;
                } else {
                    header("Location: forgot_password.php?error=Database error. Please try again.");
                }
            }
        } else {
            // Email not found - For security, normally we don't tell. 
            // But for this project, let's be explicit or just generic.
            header("Location: forgot_password.php?error=No account found with that email.");
        }
    } else {
         header("Location: forgot_password.php?error=System error.");
    }
}
ob_end_flush();
?>
