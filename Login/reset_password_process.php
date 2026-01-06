<?php
// reset_password_process.php
ob_start();
session_start();
require_once 'config.php';

$table = 'users'; 
try { $r = mysqli_query($link, "SHOW TABLES LIKE 'user'"); if ($r && mysqli_num_rows($r) > 0) $table = 'user'; } catch (Throwable $e) {}

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_btn'])){
    $token = isset($_POST['token']) ? trim($_POST['token']) : "";
    $pass = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    $session_email = isset($_SESSION['reset_verified_email']) ? $_SESSION['reset_verified_email'] : "";
    
    if($pass !== $confirm){
        header("Location: reset_password.php" . ($token ? "?token=$token" : "") . (strpos($token, '?') === false ? "?" : "&") . "error=Passwords do not match.");
        exit;
    }

    // Password Strength Validation
    $password_regex = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/";
    if(!preg_match($password_regex, $pass)){
        header("Location: reset_password.php" . ($token ? "?token=$token" : "") . (strpos($token, '?') === false ? "?" : "&") . "error=Password must be at least 8 characters and include uppercase, lowercase, number, and special character.");
        exit;
    }
    
    $hashed_password = password_hash($pass, PASSWORD_DEFAULT);
    $success = false;

    if(!empty($session_email)){
        // 1. Update via Session (OTP Flow)
        $update = "UPDATE `$table` SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE email = ?";
        if($ustmt = mysqli_prepare($link, $update)){
            mysqli_stmt_bind_param($ustmt, "ss", $hashed_password, $session_email);
            if(mysqli_stmt_execute($ustmt)){
                $success = true;
                unset($_SESSION['reset_verified_email']);
            }
        }
    } elseif(!empty($token)) {
        // 2. Update via Token (Link Flow)
        // Double-check token validity (Security)
        $check = "SELECT id FROM `$table` WHERE reset_token = ? AND reset_expiry > NOW()";
        if($stmt = mysqli_prepare($link, $check)){
            mysqli_stmt_bind_param($stmt, "s", $token);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            
            if(mysqli_stmt_num_rows($stmt) == 1){
                $update = "UPDATE `$table` SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE reset_token = ?";
                if($ustmt = mysqli_prepare($link, $update)){
                    mysqli_stmt_bind_param($ustmt, "ss", $hashed_password, $token);
                    if(mysqli_stmt_execute($ustmt)){
                        $success = true;
                    }
                }
            }
        }
    }

    if($success){
        header("Location: login.php?reset=success");
        exit;
    } else {
        header("Location: forgot_password.php?error=Invalid session or expired token. Please try again.");
        exit;
    }
}
ob_end_flush();
?>
