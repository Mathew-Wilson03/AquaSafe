<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);
require_once 'config.php';
header('Content-Type: application/json');

// Check if user is logged in and is an admin
if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_role = $_SESSION['user_role'] ?? 'user';
$role_lower = strtolower(trim((string)$user_role));
if (!in_array($role_lower, ['administrator', 'admin', 'superadmin'], true)) {
    echo json_encode(['success' => false, 'message' => 'Admin privileges required']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode(file_get_contents("php://input"), true);
    $user_id = $data['user_id'] ?? null;
    $new_role = $data['new_role'] ?? null;

    if (!$user_id || !$new_role) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
        exit;
    }

    // Determine table and role column dynamically (consistent with login_process.php)
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

    // Update the role
    $sql = "UPDATE `$table` SET `$role_col` = ? WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "si", $new_role, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database update failed']);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database prepare failed']);
    }
}
mysqli_close($link);
?>
