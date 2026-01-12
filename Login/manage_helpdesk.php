<?php
ob_start(); // Start output buffering
session_start();
require_once 'config.php';

// Release session lock immediately after reading needed variables
$user_email = $_SESSION['email'] ?? 'unspecified';
$raw_role = $_SESSION['user_role'] ?? 'none';
$logged_in = $_SESSION['loggedin'] ?? false;
$user_name = $_SESSION['name'] ?? 'User';
session_write_close(); 

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

if (!$logged_in || $logged_in !== true) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_REQUEST['action'] ?? '';
$is_admin = (in_array(strtolower($raw_role), ['admin', 'administrator', 'superadmin']));

// Debugging: Log session state if fetch_all is called
if ($action === 'fetch_all') {
    error_log("[AquaSafe Debug] HelpDesk FetchAll triggered. User: $user_email, Role: $raw_role, IsAdmin: " . ($is_admin ? 'YES' : 'NO'));
}

if ($action === 'submit') {
    $title = $_POST['title'] ?? '';
    $details = $_POST['details'] ?? '';

    if (empty($title) || empty($details)) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Missing fields']);
        exit;
    }

    $stmt = mysqli_prepare($link, "INSERT INTO helpdesk_requests (user_name, user_email, title, details, status) VALUES (?, ?, ?, ?, 'Pending')");
    mysqli_stmt_bind_param($stmt, "ssss", $user_name, $user_email, $title, $details);
    
    ob_clean();
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['status' => 'success', 'message' => 'Request submitted']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($link)]);
    }
    mysqli_stmt_close($stmt);
} 
elseif ($action === 'fetch_user') {
    $stmt = mysqli_prepare($link, "SELECT * FROM helpdesk_requests WHERE user_email = ? ORDER BY created_at DESC");
    mysqli_stmt_bind_param($stmt, "s", $user_email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    ob_clean();
    echo json_encode(['status' => 'success', 'data' => $data]);
    mysqli_stmt_close($stmt);
} 
elseif ($action === 'fetch_all') {
    if (!$is_admin) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Admin access required']);
        exit;
    }
    $sql = "SELECT * FROM helpdesk_requests ORDER BY created_at DESC";
    $result = mysqli_query($link, $sql);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    ob_clean();
    echo json_encode(['status' => 'success', 'data' => $data]);
} 
elseif ($action === 'reply') {
    if (!$is_admin) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Admin access required']);
        exit;
    }
    $id = intval($_POST['id'] ?? 0);
    $reply = $_POST['reply'] ?? '';

    if (empty($reply)) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Empty reply']);
        exit;
    }

    $stmt = mysqli_prepare($link, "UPDATE helpdesk_requests SET admin_reply = ?, status = 'In Progress' WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $reply, $id);
    
    ob_clean();
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['status' => 'success', 'message' => 'Reply sent']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($link)]);
    }
    mysqli_stmt_close($stmt);
} 
elseif ($action === 'resolve') {
    if (!$is_admin) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Admin access required']);
        exit;
    }
    $id = intval($_POST['id'] ?? 0);
    $stmt = mysqli_prepare($link, "UPDATE helpdesk_requests SET status = 'Resolved' WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    ob_clean();
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['status' => 'success', 'message' => 'Request resolved']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($link)]);
    }
    mysqli_stmt_close($stmt);
} 
elseif ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    $stmt = mysqli_prepare($link, "DELETE FROM helpdesk_requests WHERE id = ? AND user_email = ? AND status = 'Pending'");
    mysqli_stmt_bind_param($stmt, "is", $id, $user_email);
    
    ob_clean();
    if (mysqli_stmt_execute($stmt)) {
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Request deleted']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Request not found or unauthorized']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($link)]);
    }
    mysqli_stmt_close($stmt);
}
else {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}

mysqli_close($link);
ob_end_flush();
