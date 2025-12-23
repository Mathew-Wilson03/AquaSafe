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
                            
                            // Debug logging
                            error_log("[login_process] Success. User: $email, Role: $role_lower");

                            // Always send user to the dashboard selector so their role is explicit
                            $redirect_url = 'dashboard_selector.php';
                            
                            // CLEAN REDIRECT
                            if (!headers_sent()) {
                                header("Location: " . $redirect_url);
                                exit;
                            } else {
                                // Script/HTML fallback
                                echo '<script>window.location.href="' . $redirect_url . '";</script>';
                                echo 'Login successful. <a href="' . $redirect_url . '">Click here to continue</a>.';
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
