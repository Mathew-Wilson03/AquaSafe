<?php
require_once 'Login/config.php';

$test_user_email = 'test_user@example.com';
$test_admin_email = 'admin_test@example.com';
$password_hash = password_hash('Password123!', PASSWORD_DEFAULT);

// Identify table
$table = 'user';
$res = mysqli_query($link, "SHOW TABLES LIKE 'users'");
if ($res && mysqli_num_rows($res) > 0) $table = 'users';

// Identify role column
$role_col = 'role';
$res = mysqli_query($link, "SHOW COLUMNS FROM `$table` LIKE 'user_role'");
if ($res && mysqli_num_rows($res) > 0) $role_col = 'user_role';

echo "Using table: $table, Role column: $role_col\n";

// Ensure User
$stmt = mysqli_prepare($link, "SELECT id FROM `$table` WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $test_user_email);
mysqli_stmt_execute($stmt);
if (mysqli_stmt_get_result($stmt)->num_rows == 0) {
    echo "Creating test user...\n";
    $ins = mysqli_prepare($link, "INSERT INTO `$table` (name, email, password, `$role_col`) VALUES ('Test User', ?, ?, 'user')");
    mysqli_stmt_bind_param($ins, "ss", $test_user_email, $password_hash);
    mysqli_stmt_execute($ins);
}

// Ensure Admin
$stmt = mysqli_prepare($link, "SELECT id FROM `$table` WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $test_admin_email);
mysqli_stmt_execute($stmt);
if (mysqli_stmt_get_result($stmt)->num_rows == 0) {
    echo "Creating test admin...\n";
    $ins = mysqli_prepare($link, "INSERT INTO `$table` (name, email, password, `$role_col`) VALUES ('Admin Test', ?, ?, 'admin')");
    mysqli_stmt_bind_param($ins, "ss", $test_admin_email, $password_hash);
    mysqli_stmt_execute($ins);
} else {
    echo "Promoting existing user to admin...\n";
    $upd = mysqli_prepare($link, "UPDATE `$table` SET `$role_col` = 'admin' WHERE email = ?");
    mysqli_stmt_bind_param($upd, "s", $test_admin_email);
    mysqli_stmt_execute($upd);
}

echo "Test data prepared successfully.\n";
?>
