<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Check login
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

$user_id = $_SESSION["id"];

if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    // Validate inputs
    $newName = trim($_POST['name'] ?? '');
    $newLocation = trim($_POST['location'] ?? '');
    
    if(empty($newName) || empty($newLocation)){
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }
    
    // Validate location against allowed list (optional but safer)
    $allowed_locations = ["Central City", "North District", "South Reservoir", "West Bank", "East Valley"];
    if(!in_array($newLocation, $allowed_locations)){
        echo json_encode(['status' => 'error', 'message' => 'Invalid location selected.']);
        exit;
    }

    // Update DB
    $sql = "UPDATE users SET name = ?, location = ? WHERE id = ?";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "ssi", $newName, $newLocation, $user_id);
        
        if(mysqli_stmt_execute($stmt)){
            // Update Session
            $_SESSION['name'] = $newName; 
            // Location is re-fetched on dashboard load usually, but good to have if stored in session
            $_SESSION['location'] = $newLocation; 
            
            echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($link)]);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Preparation error.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
mysqli_close($link);
?>
