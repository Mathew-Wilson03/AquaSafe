<?php
require_once 'config.php';

// Fix ENUM to include 'Responded'
$sql = "ALTER TABLE helpdesk_requests MODIFY COLUMN status ENUM('Pending', 'In Progress', 'Responded', 'Resolved') DEFAULT 'Pending'";

if (mysqli_query($link, $sql)) {
    echo "ENUM updated successfully.\n";
} else {
    echo "Error updating ENUM: " . mysqli_error($link) . "\n";
}

mysqli_close($link);
?>
