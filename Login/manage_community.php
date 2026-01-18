<?php
// manage_community.php
session_start();
require_once 'config.php';

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

        // Send Emails (Using PHPMailer or Log Fallback)
        require 'vendor/autoload.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $sentCount = 0;

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'mathewwilson2028@mca.ajce.in';
            $mail->Password = 'pemz qqqx aotl ntfu';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->setFrom('mathewwilson2028@mca.ajce.in', 'AquaSafe Alert System');
            $mail->isHTML(true);
            $mail->Subject = "⚠️ $severity Alert: $targetArea";

            // Loop and send (BCC for privacy or individual)
            // For demo, we'll just log or send one by one
            foreach(array_unique($recipients) as $toEmail) {
                $mail->clearAddresses();
                $mail->addAddress($toEmail);
                $mail->Body = "<h2>$severity Alert for $targetArea</h2><p>$message</p><p>Stay Safe,<br>AquaSafe Team</p>";
                $mail->send();
                $sentCount++;
            }
        } catch (Exception $e) {
            // Log error
            file_put_contents('email_log.txt', "Error: " . $mail->ErrorInfo . "\n", FILE_APPEND);
        }

        jsonResponse(true, "Alert sent to $sentCount recipients (App Users + Offline Community).");
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
