<?php
header('Content-Type: application/json');
require_once 'config.php';

// Allow any admin to manage sensors
if (!isset($_SESSION['email'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Ensure table exists (Auto-Migration)
$table_check = "CREATE TABLE IF NOT EXISTS `sensor_status` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sensor_id` VARCHAR(50) UNIQUE NOT NULL,
    `location_name` VARCHAR(100) NOT NULL,
    `status` VARCHAR(20) DEFAULT 'Active',
    `battery_level` INT DEFAULT 100,
    `last_ping` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($link, $table_check);


if ($action === 'add') {
    $sensor_id = trim($_POST['sensor_id'] ?? '');
    $location = trim($_POST['location_name'] ?? '');
    $status = $_POST['status'] ?? 'Active';
    
    // VALIDATION
    if (empty($sensor_id) || empty($location)) {
        echo json_encode(['status' => 'error', 'message' => 'Sensor ID and Location Name are required.']);
        exit;
    }

    if (preg_match('/[^a-zA-Z0-9-]/', $sensor_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Sensor ID can only contain letters, numbers, and hyphens.']);
        exit;
    }

    // Check Duplicate
    $check = mysqli_prepare($link, "SELECT id FROM sensor_status WHERE sensor_id = ?");
    mysqli_stmt_bind_param($check, "s", $sensor_id);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);
    if(mysqli_stmt_num_rows($check) > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Sensor ID already exists!']);
        exit;
    }

    // Insert
    $stmt = mysqli_prepare($link, "INSERT INTO sensor_status (sensor_id, location_name, status, last_ping) VALUES (?, ?, ?, NOW())");
    mysqli_stmt_bind_param($stmt, "sss", $sensor_id, $location, $status);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['status' => 'success', 'message' => 'Sensor added successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . mysqli_error($link)]);
    }
    exit;
}

if ($action === 'delete') {
    $id = $_POST['sensor_id'] ?? '';
    if(empty($id)) {
         echo json_encode(['status' => 'error', 'message' => 'ID required']); 
         exit;
    }
    
    $stmt = mysqli_prepare($link, "DELETE FROM sensor_status WHERE sensor_id = ?");
    mysqli_stmt_bind_param($stmt, "s", $id);
    if(mysqli_stmt_execute($stmt)) {
        echo json_encode(['status' => 'success', 'message' => 'Sensor removed.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Delete failed.']);
    }
    exit;
}

// Fallback: Fetch (for compatibility if needed, though manage_alerts.php handles it mostly)
if ($action === 'fetch' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    $res = mysqli_query($link, "SELECT * FROM sensor_status ORDER BY sensor_id ASC");
    $data = [];
    while($row = mysqli_fetch_assoc($res)) $data[] = $row;
    echo json_encode(['status' => 'success', 'data' => $data]);
    exit;
}
?>
