<?php
// Enable error reporting for Azure debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

    // Hard-through Email Validation
    $email_regex = "/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/";
    $disposable_domains = ['mailinator.com', 'yopmail.com', 'tempmail.com', '10minutemail.com', 'guerrillamail.com', 'sharklasers.com'];
    
    $domain = strtolower(substr(strrchr($email, "@"), 1));

    if(!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match($email_regex, $email)){
        header("Location: signup.php?role=$role&error=Please enter a valid official email address");
        exit;
    }

    if(in_array($domain, $disposable_domains)){
        header("Location: signup.php?role=$role&error=Disposable email addresses are not allowed. Please use a permanent email.");
        exit;
    }

    if($password !== $confirm_password){
        header("Location: signup.php?role=$role&error=Passwords do not match");
        exit;
    }

    // Password Strength Validation
    // At least 8 characters, at least one uppercase, one lowercase, one number and one special character
    $password_regex = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/";
    if(!preg_match($password_regex, $password)){
        header("Location: signup.php?role=$role&error=Password must be at least 8 characters and include uppercase, lowercase, number, and special character");
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
    $location = isset($_POST['location']) ? trim($_POST['location']) : 'Central City';

    $sql = "INSERT INTO `$table` (id, name, email, password, `$role_col`, location) VALUES (?, ?, ?, ?, ?, ?)";
    
    // Get next available ID (workaround for Azure missing AUTO_INCREMENT)
    $maxResult = mysqli_query($link, "SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM `$table`");
    $nextId = 1;
    if ($maxResult) {
        $row = mysqli_fetch_assoc($maxResult);
        $nextId = (int)$row['next_id'];
    }

    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "isssss", $nextId, $name, $email, $hashed_password, $clean_role, $location);
        
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
