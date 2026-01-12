<?php
require_once 'config.php';

echo "REPAIR START\n";

// 1. Force fix the status column if it got corrupted
$sql = "ALTER TABLE helpdesk_requests MODIFY COLUMN status ENUM('Pending', 'In Progress', 'Resolved') DEFAULT 'Pending'";
if(mysqli_query($link, $sql)) echo "Column status restored to ENUM.\n";
else echo "Error restoring column: " . mysqli_error($link) . "\n";

// 2. Clean up corrupted records
$sql = "UPDATE helpdesk_requests SET 
        user_email = TRIM(REPLACE(REPLACE(user_email, '\n', ''), '\r', '')),
        status = 'Pending' 
        WHERE status NOT IN ('Pending', 'In Progress', 'Resolved')";
if(mysqli_query($link, $sql)) echo "Corrupted records sanitized.\n";
else echo "Error cleaning records: " . mysqli_error($link) . "\n";

echo "REPAIR COMPLETE\n";
?>
