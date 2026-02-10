<?php
// manage_community.php
session_start();
require_once 'config.php';
file_put_contents('debug_log.txt', "Request received: " . print_r($_POST, true) . "\n", FILE_APPEND);

// Super Admin Check
file_put_contents('debug_log.txt', "Session Email: " . ($_SESSION['email'] ?? 'NULL') . "\n", FILE_APPEND);
file_put_contents('debug_log.txt', "Expected Admin: " . SUPER_ADMIN_EMAIL . "\n", FILE_APPEND);

// Permission Check: Super Admin OR Admin Role
$user_role = $_SESSION['user_role'] ?? 'user';
$user_email = $_SESSION['email'] ?? '';
$is_super = ($user_email === SUPER_ADMIN_EMAIL);
$is_admin = (in_array(strtolower(trim($user_role)), ['admin', 'administrator']));

file_put_contents('debug_log.txt', "Auth Check: Email=$user_email, Role=$user_role, Super=$is_super, Admin=$is_admin\n", FILE_APPEND);

if (!$is_super && !$is_admin) {
    file_put_contents('debug_log.txt', "Auth Failed: 403\n", FILE_APPEND);
    header('HTTP/1.1 403 Forbidden');
    die("Permission Denied: Only Admins can perform this action.");
}
file_put_contents('debug_log.txt', "Auth Success. Proceeding...\n", FILE_APPEND);

// 1. Auto-Create Table & Schema Migration
$table_sql = "CREATE TABLE IF NOT EXISTS `community_alerts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `location` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20),
    `address` TEXT,
    `latitude` DECIMAL(10, 8),
    `longitude` DECIMAL(11, 8),
    `age` INT,
    `gender` VARCHAR(20),
    `household_size` INT DEFAULT 1,
    `special_needs` TEXT,
    `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($link, $table_sql);

// Auto-Migration: Add columns if they are missing (for existing tables)
$columns = ['address' => 'TEXT', 'latitude' => 'DECIMAL(10, 8)', 'longitude' => 'DECIMAL(11, 8)', 
            'age' => 'INT', 'gender' => 'VARCHAR(20)', 'household_size' => 'INT DEFAULT 1', 'special_needs' => 'TEXT'];

foreach ($columns as $col => $type) {
    try {
        $check = mysqli_query($link, "SHOW COLUMNS FROM `community_alerts` LIKE '$col'");
        if(mysqli_num_rows($check) == 0) {
            mysqli_query($link, "ALTER TABLE `community_alerts` ADD COLUMN `$col` $type");
        }
    } catch (Exception $e) { /* Ignore if exists */ }
}

// Helper: Response
function jsonResponse($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

// 2. Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // --- IMPORT CSV ---
    if ($action === 'upload_csv') {
        if (!isset($_FILES['censusFile']) || $_FILES['censusFile']['error'] !== UPLOAD_ERR_OK) {
            jsonResponse(false, 'File upload failed.');
        }

        $file = $_FILES['censusFile']['tmp_name'];
        $handle = fopen($file, "r");
        
        $stats = ['processed' => 0, 'existing_users' => 0, 'new_contacts' => 0, 'errors' => 0];
        
        $firstRow = fgetcsv($handle); // Skip header
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $stats['processed']++;
            // Required Fields
            $name = trim($data[0] ?? '');
            $email = trim($data[1] ?? '');
            $location = trim($data[2] ?? '');
            $phone = trim($data[3] ?? '');
            
            // Extended Fields
            $address = trim($data[4] ?? '');
            $latitude = !empty($data[5]) ? floatval($data[5]) : NULL;
            $longitude = !empty($data[6]) ? floatval($data[6]) : NULL;
            $age = !empty($data[7]) ? intval($data[7]) : NULL;
            $gender = trim($data[8] ?? '');
            $household = !empty($data[9]) ? intval($data[9]) : 1;
            $needs = trim($data[10] ?? '');

            if (empty($email)) continue;

            $checkUser = mysqli_prepare($link, "SELECT id FROM users WHERE email = ?");
            mysqli_stmt_bind_param($checkUser, "s", $email);
            mysqli_stmt_execute($checkUser);
            mysqli_stmt_store_result($checkUser);

            if (mysqli_stmt_num_rows($checkUser) > 0) {
                $stats['existing_users']++;
            } else {
                $checkComm = mysqli_prepare($link, "SELECT id FROM community_alerts WHERE email = ?");
                mysqli_stmt_bind_param($checkComm, "s", $email);
                mysqli_stmt_execute($checkComm);
                mysqli_stmt_store_result($checkComm);

                if (mysqli_stmt_num_rows($checkComm) == 0) {
                    $insert = mysqli_prepare($link, "INSERT INTO community_alerts (name, email, location, phone, address, latitude, longitude, age, gender, household_size, special_needs) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    mysqli_stmt_bind_param($insert, "ssssssdisis", $name, $email, $location, $phone, $address, $latitude, $longitude, $age, $gender, $household, $needs);
                    if (mysqli_stmt_execute($insert)) $stats['new_contacts']++;
                    else $stats['errors']++;
                } else {
                    $stats['existing_users']++;
                }
            }
        }
        fclose($handle);
        jsonResponse(true, "Processing Complete!", $stats);
    }

    // --- BROADCAST ALERT ---
    if ($action === 'broadcast_alert') {
        $targetArea = $_POST['area'] ?? 'All'; // e.g., 'South Reservoir'
        $message = $_POST['message'] ?? 'Emergency Alert!';
        $severity = $_POST['severity'] ?? 'Warning';

        // Gather all recipients
        $recipients = [];
        
        // 1. App Users
        $sqlUser = "SELECT email, name FROM users";
        if($targetArea !== 'All') $sqlUser .= " WHERE location = '$targetArea'";
        $resU = mysqli_query($link, $sqlUser);
        while($r = mysqli_fetch_assoc($resU)) $recipients[] = $r['email'];

        // 2. Offline Contacts
        $sqlComm = "SELECT email, name FROM community_alerts";
        if($targetArea !== 'All') $sqlComm .= " WHERE location = '$targetArea'";
        $resC = mysqli_query($link, $sqlComm);
        while($r = mysqli_fetch_assoc($resC)) $recipients[] = $r['email'];

        // --- FIX: also insert into sensor_alerts for User Dashboard visibility ---
        $insertAlert = mysqli_prepare($link, "INSERT INTO sensor_alerts (severity, message, location) VALUES (?, ?, ?)");
        $dbLocation = ($targetArea === 'All') ? 'System Wide' : $targetArea;
        mysqli_stmt_bind_param($insertAlert, "sss", $severity, $message, $dbLocation);
        mysqli_stmt_execute($insertAlert);
        mysqli_stmt_close($insertAlert);
        // -------------------------------------------------------------------------

        // --- OPTIMIZATION: Respond to User IMMEDIATELY, then send emails in background ---
        ob_end_clean(); // Clean any previous output
        ignore_user_abort(true); // Keep running if browser closes
        ob_start(); // Buffer output

        // Send Success Response
        jsonResponse(true, "Alert posted! Emails are being queued in background.");
        
        $size = ob_get_length();
        header("Content-Length: $size");
        header("Connection: close");
        ob_end_flush();
        flush(); // Force output to browser
        
        // --- BACKGROUND PROCESS STARTS HERE ---
        // (Browser has stopped waiting, but PHP is still running)
        
        require 'vendor/autoload.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        // ... mail setup ...
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->isHTML(true);
        $mail->Subject = "⚠️ $severity Alert: $targetArea";

        foreach(array_unique($recipients) as $toEmail) {
            try {
                $mail->clearAddresses();
                $mail->addAddress($toEmail);
                $mail->Body = "<h2>$severity Alert for $targetArea</h2><p>$message</p><p>Stay Safe,<br>AquaSafe Team</p>";
                $mail->send();
                file_put_contents('email_log.txt', "[" . date('Y-m-d H:i:s') . "] To: $toEmail | Status: Sent\n", FILE_APPEND);
            } catch (Exception $e) {
                file_put_contents('email_log.txt', "[" . date('Y-m-d H:i:s') . "] To: $toEmail | Status: Failed | Error: " . $mail->ErrorInfo . "\n", FILE_APPEND);
            }
            // Sleep tiny bit to be nice to SMTP server
            usleep(100000); 
        }
        exit; // script ends here for real
    }
}

// 3. GET Actions
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    // --- FETCH ALL ---
    if ($action === 'fetch_all') {
        $merged = [];
        $resUsers = mysqli_query($link, "SELECT name, email, location, 'App User' as status FROM users");
        if($resUsers) while($row = mysqli_fetch_assoc($resUsers)) $merged[] = $row;
        
        $resComm = mysqli_query($link, "SELECT name, email, location, address, age, special_needs, 'Offline Contact' as status FROM community_alerts");
        if($resComm) while($row = mysqli_fetch_assoc($resComm)) $merged[] = $row;

        jsonResponse(true, '', $merged);
    }

    // --- EXPORT CSV (SAMPLE TEMPLATE) ---
    if ($action === 'export_csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="census_template_extended_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        // Extended Headers
        fputcsv($output, ['Name', 'Email', 'Location (Zone)', 'Phone', 'Address (Full)', 'Latitude', 'Longitude', 'Age', 'Gender', 'Household Size', 'Special Needs']);
        
        // Sample Data
        fputcsv($output, ['John Doe', 'john@example.com', 'South Reservoir', '555-0101', '123 River Rd', '10.8505', '76.2711', '45', 'Male', '4', 'None']);
        fputcsv($output, ['Jane Smith', 'jane@example.com', 'North District', '555-0102', '456 Hilltop Ln', '10.8600', '76.2800', '70', 'Female', '2', 'Mobility Impaired']);

        fclose($output);
        exit;
    }
}
?>
