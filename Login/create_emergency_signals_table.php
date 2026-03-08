<?php
require_once 'config.php';

$sql = "CREATE TABLE IF NOT EXISTS emergency_signals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_email VARCHAR(255) NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    status ENUM('Active', 'Cleared') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (mysqli_query($link, $sql)) {
    echo json_encode(["status" => "success", "message" => "Table emergency_signals created or already exists."]);
} else {
    echo json_encode(["status" => "error", "message" => mysqli_error($link)]);
}

mysqli_close($link);
?>
