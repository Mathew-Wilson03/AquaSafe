<?php
// Start output buffering
ob_start();
session_start();
require_once 'config.php';

// Detect table and role column (Reusing robust logic from login_process.php)
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

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signup_btn'])){
    
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Basic Validation
    if(empty($name) || empty($email) || empty($password)){
        header("Location: signup.php?role=$role&error=All fields are required");
        exit;
    }

    if($password !== $confirm_password){
        header("Location: signup.php?role=$role&error=Passwords do not match");
        exit;
    }

    if(strlen($password) < 6){
        header("Location: signup.php?role=$role&error=Password must be at least 6 characters");
        exit;
    }

    // Check if email already exists
    $sql_check = "SELECT id FROM `$table` WHERE email = ?";
    if($stmt = mysqli_prepare($link, $sql_check)){
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if(mysqli_stmt_num_rows($stmt) > 0){
            header("Location: signup.php?role=$role&error=Email already exists");
            exit;
        }
        mysqli_stmt_close($stmt);
    }

    // Insert new user
    // Securely hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    // Normalize role to match DB ENUM
    $clean_role = (strtolower($role) === 'admin' || strtolower($role) === 'administrator') ? 'administrator' : 'user';

    $sql = "INSERT INTO `$table` (name, email, password, `$role_col`) VALUES (?, ?, ?, ?)";
    
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $hashed_password, $clean_role);
        
        if(mysqli_stmt_execute($stmt)){
            // Success! Redirect to login
            header("Location: login.php?role=$clean_role&signup=success");
            exit;
        } else {
             header("Location: signup.php?role=$role&error=Database insert failed");
             exit;
        }
        mysqli_stmt_close($stmt);
    }
}
mysqli_close($link);
ob_end_flush();
?>
