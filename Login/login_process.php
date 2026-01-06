<?php
// Start output buffering to prevent header errors
ob_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session at the very beginning
session_start();

// Include the database connection file
require_once 'config.php';


// Determine table and role column dynamically to prevent crashes
$table = 'users'; 
try {
    $r = mysqli_query($link, "SHOW TABLES LIKE 'user'");
    if ($r && mysqli_num_rows($r) > 0) $table = 'user';
} catch (Throwable $e) {}

$role_col = 'role';
try {
    $r = mysqli_query($link, "SHOW COLUMNS FROM `$table` LIKE 'user_role'");
    if ($r && mysqli_num_rows($r) > 0) $role_col = 'user_role';
} catch (Throwable $e) {}

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login_btn'])){
    try {
        $email = trim($_POST['email']);
        $password = $_POST['password']; // Do not trim password to match signup process

        // Hard-through Email Validation
        $email_regex = "/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/";
        $disposable_domains = ['mailinator.com', 'yopmail.com', 'tempmail.com', '10minutemail.com', 'guerrillamail.com', 'sharklasers.com'];
        $domain = strtolower(substr(strrchr($email, "@"), 1));

        if(!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match($email_regex, $email) || in_array($domain, $disposable_domains)){
            $role_param = isset($_POST['role']) ? $_POST['role'] : '';
            header("Location: login.php?role=$role_param&error=Please use a valid official email address.");
            exit;
        }
        
        // Prepare a select statement
        $sql = "SELECT id, name, `$role_col` AS user_role, password FROM `$table` WHERE email = ?";
        
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            $param_email = $email;
            
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    mysqli_stmt_bind_result($stmt, $id, $name, $user_role, $hashed_password);
                    if(mysqli_stmt_fetch($stmt)){
                        if(password_verify($password, $hashed_password)){
                            // Password is correct, start a new session
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["name"] = $name;
                            $_SESSION["email"] = $email;
                            $_SESSION["user_role"] = $user_role;
                            
                            $role_lower = strtolower(trim((string)$user_role));
                            
                            // Direct redirect based on role
                            if (in_array($role_lower, ['administrator', 'admin', 'superadmin'], true)) {
                                $redirect_url = 'admin_dashboard.php';
                            } else {
                                $redirect_url = 'user_dashboard.php';
                            }
                            
                            // Debug logging
                            error_log("[login_process] Success. User: $email, Role: $role_lower -> $redirect_url");

                            if (!headers_sent()) {
                                header("Location: " . $redirect_url);
                                exit;
                            } else {
                                echo '<script>window.location.href="' . $redirect_url . '";</script>';
                                exit;
                            }
                        } else {
                            $role_param = isset($_POST['role']) ? $_POST['role'] : '';
                            header("Location: login.php?role=$role_param&error=Invalid email or password."); // Redirect on weak password
                            exit;
                        }
                    }
                } else {
                    $role_param = isset($_POST['role']) ? $_POST['role'] : '';
                    header("Location: login.php?role=$role_param&error=Invalid email or password."); // Redirect on user not found
                    exit;
                }
            } else {
                 $role_param = isset($_POST['role']) ? $_POST['role'] : '';
                 header("Location: login.php?role=$role_param&error=Database execution error.");
                 exit;
            }
            mysqli_stmt_close($stmt);
        } else {
             $role_param = isset($_POST['role']) ? $_POST['role'] : '';
             header("Location: login.php?role=$role_param&error=Database prepare error.");
             exit;
        }
    } catch (Throwable $e) {
        // Catch ANY fatal error and show it
        echo "CRITICAL SYSTEM ERROR: " . $e->getMessage();
    }
}
// Close connection
mysqli_close($link);

// Flush the buffer and send output
ob_end_flush();
