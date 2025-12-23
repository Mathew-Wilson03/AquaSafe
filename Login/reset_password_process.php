<?php
// reset_password_process.php
ob_start();
session_start();
require_once 'config.php';

$table = 'users'; 
try { $r = mysqli_query($link, "SHOW TABLES LIKE 'user'"); if ($r && mysqli_num_rows($r) > 0) $table = 'user'; } catch (Throwable $e) {}

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_btn'])){
    $token = $_POST['token'];
    $pass = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    
    if($pass !== $confirm){
        die("Passwords do not match."); // Simple die for now, better to redirect with error
    }
    
    // Double-check token validity (Security)
    $check = "SELECT id FROM `$table` WHERE reset_token = ? AND reset_expiry > NOW()";
    if($stmt = mysqli_prepare($link, $check)){
        mysqli_stmt_bind_param($stmt, "s", $token);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if(mysqli_stmt_num_rows($stmt) == 1){
            // Hash new password
            $hashed_password = password_hash($pass, PASSWORD_DEFAULT);
            
            // Update Password AND Clear Token
            $update = "UPDATE `$table` SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE reset_token = ?";
            if($ustmt = mysqli_prepare($link, $update)){
                mysqli_stmt_bind_param($ustmt, "ss", $hashed_password, $token);
                if(mysqli_stmt_execute($ustmt)){
                    // Success!
                    header("Location: login.php?reset=success");
                    exit;
                } else {
                    die("Error updating password.");
                }
            }
        } else {
            die("Invalid or expired token. Please try again.");
        }
    }
}
ob_end_flush();
?>
