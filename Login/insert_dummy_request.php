<?php
require_once 'config.php';

// 1. Ensure table is correct
$sql = "CREATE TABLE IF NOT EXISTS helpdesk_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_name VARCHAR(100) NOT NULL,
    user_email VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    details TEXT NOT NULL,
    admin_reply TEXT DEFAULT NULL,
    status ENUM('Pending', 'In Progress', 'Resolved') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
mysqli_query($link, $sql);

// 2. Insert a dummy pending request to BE SURE there is something to see
$sql = "INSERT INTO helpdesk_requests (user_name, user_email, title, details, status) 
        VALUES ('System Verification', 'verify@aquasafe.com', 'Final Sync Test', 'This is a test request to verify the admin view.', 'Pending')";
if(mysqli_query($link, $sql)) {
    echo "DUMMY REQUEST CREATED.\n";
} else {
    echo "ERROR CREATING DUMMY: " . mysqli_error($link) . "\n";
}
?>
