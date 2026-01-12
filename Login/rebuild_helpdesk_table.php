<?php
require_once 'config.php';

echo "REBUILDING HELPDESK TABLE...\n";

// 1. Drop existing table
$sql = "DROP TABLE IF EXISTS helpdesk_requests";
if(mysqli_query($link, $sql)) echo "Table dropped.\n";
else echo "Error dropping table: " . mysqli_error($link) . "\n";

// 2. Recreate with robust structure
$sql = "CREATE TABLE helpdesk_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_name VARCHAR(100) NOT NULL,
    user_email VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    details TEXT NOT NULL,
    admin_reply TEXT DEFAULT NULL,
    status ENUM('Pending', 'In Progress', 'Resolved') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if(mysqli_query($link, $sql)) echo "Table recreated successfully.\n";
else echo "Error recreating table: " . mysqli_error($link) . "\n";

echo "REBUILD COMPLETE.\n";
?>
