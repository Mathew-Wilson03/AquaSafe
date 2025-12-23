<?php
require_once 'config.php';

echo "<h1>User Debug Tool</h1>";
echo "<p>Checking database connection...</p>";

if (!isset($link) || !$link) {
    echo "<p>Connection failed! Check config.php</p>";
    if (function_exists('mysqli_connect_error')) {
        die("Connection error: " . mysqli_connect_error());
    }
    die("Link variable not defined.");
}
echo "<p>Connected successfully.</p>";

// detect table
$table = 'users';
try {
    $r = mysqli_query($link, "SHOW TABLES LIKE 'user'");
    if ($r && mysqli_num_rows($r) > 0) $table = 'user';
} catch (Exception $e) {}
echo "<p>Using table: <strong>$table</strong></p>";

// detect role column
$role_col = 'role';
try {
    $r = mysqli_query($link, "SHOW COLUMNS FROM `$table` LIKE 'user_role'");
    if ($r && mysqli_num_rows($r) > 0) $role_col = 'user_role';
} catch (Exception $e) {}

echo "<p>Using role column: <strong>$role_col</strong></p>";

$sql_safe = "SELECT * FROM `$table`";
$result = mysqli_query($link, $sql_safe);

if ($result) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background:#ddd;'><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Password Status</th><th>OTP Code</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        $role_val = $row[$role_col] ?? 'N/A';
        $pass = $row['password'] ?? '';
        $is_hashed = (strlen($pass) > 20 && substr($pass, 0, 1) === '$'); 
        $hash_status = $is_hashed ? "<span style='color:green'>Hashed</span>" : "<strong style='color:red'>PLAINTEXT (Invalid)</strong>";
        $otp = $row['reset_token'] ?? '-';
        
        echo "<tr>";
        echo "<td>" . ($row['id'] ?? '?') . "</td>";
        echo "<td>" . ($row['name'] ?? '?') . "</td>";
        echo "<td>" . ($row['email'] ?? '?') . "</td>";
        echo "<td>" . $role_val . "</td>";
        echo "<td>" . $hash_status . "</td>";
        echo "<td style='background:#ffeaa7; font-weight:bold; color:#d35400;'>" . $otp . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error getting users: " . mysqli_error($link);
}
?>
<hr>
<h3>Create New Admin</h3>
<p>If you don't see a valid admin above, create one here:</p>
<form method="POST">
    <input type="text" name="new_email" placeholder="admin@aquasafe.com" value="admin@aquasafe.com" style="padding:5px;">
    <input type="text" name="new_pass" placeholder="Password" value="admin123" style="padding:5px;">
    <input type="submit" name="create_admin" value="Create Admin" style="padding:5px; background:blue; color:white; cursor:pointer;">
</form>

<?php
if (isset($_POST['create_admin'])) {
    $email = $_POST['new_email'];
    $pass = password_hash($_POST['new_pass'], PASSWORD_DEFAULT);
    $name = 'Admin User';
    $role = 'admin'; 
    
    // Check if exists
    $check_sql = "SELECT id FROM `$table` WHERE email = '$email'";
    $check_res = mysqli_query($link, $check_sql);
    if ($check_res && mysqli_num_rows($check_res) > 0) {
        // Update
        $sql_update = "UPDATE `$table` SET password = ?, `$role_col` = ? WHERE email = ?";
        $stmt = mysqli_prepare($link, $sql_update);
        mysqli_stmt_bind_param($stmt, "sss", $pass, $role, $email);
        if (mysqli_stmt_execute($stmt)) {
             echo "<h3 style='color:green'>Updated existing user $email to be admin with new password.</h3>";
             echo "<script>setTimeout(function(){ window.location.reload(); }, 2000);</script>";
        } else {
            echo "<h3 style='color:red'>Update failed: " . mysqli_stmt_error($stmt) . "</h3>";
        }
    } else {
        // Insert
        $sql_insert = "INSERT INTO `$table` (name, email, password, `$role_col`) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($link, $sql_insert);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $pass, $role);
            if (mysqli_stmt_execute($stmt)) {
                echo "<h3 style='color:green'>Success! Created user $email</h3>";
                echo "<script>setTimeout(function(){ window.location.reload(); }, 2000);</script>";
            } else {
                echo "<h3 style='color:red'>Insert failed: " . mysqli_stmt_error($stmt) . "</h3>";
            }
        } else {
             echo "Prepare failed: " . mysqli_error($link);
        }
    }
}
?>
