<?php
require_once 'config.php';

$sql = "CREATE TABLE IF NOT EXISTS helpdesk_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_name VARCHAR(100) NOT NULL,
    user_email VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    details TEXT NOT NULL,
    admin_reply TEXT DEFAULT NULL,
    status ENUM('Pending', 'In Progress', 'Resolved') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (mysqli_query($link, $sql)) {
    echo "Table 'helpdesk_requests' created successfully.\n";
} else {
    echo "Error creating table: " . mysqli_error($link) . "\n";
}

mysqli_close($link);
?>
