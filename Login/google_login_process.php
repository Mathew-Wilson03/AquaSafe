<?php
// google_login_process.php
// Verify Google ID token via Google's tokeninfo endpoint and route users by role
session_start();
require_once 'config.php'; // provides DB connection ($link) and GOOGLE_CLIENT_ID

// Check for token (support both ID token and Access Token)
$email = '';
$name = '';

if (isset($_POST['id_token']) && !empty($_POST['id_token'])) {
    // --- ID TOKEN FLOW (One Tap) ---
    $idToken = $_POST['id_token'];
    $tokeninfo_url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken);
    $response = @file_get_contents($tokeninfo_url);
    if ($response === false) die('Authentication Error: Unable to validate token with Google.');
    
    $tokenData = json_decode($response, true);
    if (!$tokenData || !isset($tokenData['email'])) die('Authentication Error: Invalid token data.');
    
    // Verify Audience
    if (defined('GOOGLE_CLIENT_ID') && GOOGLE_CLIENT_ID !== '420461254572-1s58305detpq2n08ukpgf5sl4c44jb1f.apps.googleusercontent.com') {
        if (isset($tokenData['aud']) && $tokenData['aud'] !== GOOGLE_CLIENT_ID) {
            die('Authentication Error: Token audience does not match.');
        }
    }
    
    $email = $tokenData['email'];
    $name = $_POST['name'] ?? ($tokenData['name'] ?? '');
    
    if (isset($tokenData['email_verified']) && $tokenData['email_verified'] !== 'true' && $tokenData['email_verified'] !== true) {
        die('Authentication Error: Google account email not verified.');
    }

} elseif (isset($_POST['access_token']) && !empty($_POST['access_token'])) {
    // --- ACCESS TOKEN FLOW (Popup) ---
    $accessToken = $_POST['access_token'];
    $userinfo_url = 'https://www.googleapis.com/oauth2/v3/userinfo?access_token=' . urlencode($accessToken);
    $response = @file_get_contents($userinfo_url);
    if ($response === false) die('Authentication Error: Unable to fetch user info from Google.');
    
    $userData = json_decode($response, true);
    if (!$userData || !isset($userData['email'])) die('Authentication Error: Invalid user data received.');
    
    $email = $userData['email'];
    $name = $userData['name'] ?? '';
    
    if (isset($userData['email_verified']) && $userData['email_verified'] !== true && $userData['email_verified'] !== 'true') {
        die('Authentication Error: Google account email not verified.');
    }

} else {
    die("Authentication Error: No invalid token received.");
}

// Table detection logic (copied from login_process.php)
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

$sql = "SELECT id, name, `$role_col` AS user_role FROM `" . $table . "` WHERE email = ? LIMIT 1";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) === 1) {
        mysqli_stmt_bind_result($stmt, $id, $db_name, $user_role);
        mysqli_stmt_fetch($stmt);
    } else {
        // If user not found, create a new user with default 'user' role
        // mysqli_stmt_close($stmt); // REMOVED: Do not close here, let it close at end of block
        
        // Use provided role if available, otherwise default to 'user'
        $new_role = isset($_POST['role']) ? $_POST['role'] : 'user';
        if (strtolower($new_role) === 'admin' || strtolower($new_role) === 'administrator') {
            $new_role = 'administrator';
        } else {
            $new_role = 'user';
        }

        // Try to insert using the dynamically detected role column
        $insert = "INSERT INTO `" . $table . "` (name, email, password, `$role_col`) VALUES (?, ?, '', ?)";
        if ($ins = mysqli_prepare($link, $insert)) {
            $nm = $name ?: $email;
            mysqli_stmt_bind_param($ins, 'sss', $nm, $email, $new_role);
            if (mysqli_stmt_execute($ins)) {
                $id = mysqli_insert_id($link);
                $user_role = $new_role;
                $db_name = $nm;
            } else {
                 die('Server Error: Database insert failed: ' . mysqli_error($link));
            }
            mysqli_stmt_close($ins);
        } else {
            die('Server Error: Database insert prepare failed: ' . mysqli_error($link));
        }
    }
    mysqli_stmt_close($stmt);
} else {
    die('Server Error: Database query failed.');
}

// Set session and redirect based on role
$_SESSION['loggedin'] = true;
$_SESSION['id'] = $id;
$_SESSION['name'] = $db_name;
$_SESSION['email'] = $email;
$_SESSION['user_role'] = $user_role;

// Redirect based on role (allow common role name variations)
$role_lower = strtolower(trim((string)$user_role));
// Log role for debugging
error_log("[google_login_process] user={$email}, role={$role_lower}");
if (in_array($role_lower, ['administrator','admin','superadmin'], true)) {
    error_log("[google_login_process] redirecting {$email} to admin_dashboard.php");
    header('Location: admin_dashboard.php');
    exit;
} else {
    error_log("[google_login_process] redirecting {$email} to user_dashboard.php");
    header('Location: user_dashboard.php');
    exit;
}
?>