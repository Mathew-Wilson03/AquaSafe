<?php
require_once 'config.php';

echo "Updating helpdesk_requests schema...\n";

// 1. Update Status Enum
$sql1 = "ALTER TABLE helpdesk_requests MODIFY COLUMN status ENUM('Pending', 'In Progress', 'Responded', 'Resolved') DEFAULT 'Pending'";
if (mysqli_query($link, $sql1)) {
    echo "Status ENUM updated successfully.\n";
} else {
    echo "Error updating Status ENUM: " . mysqli_error($link) . "\n";
}

// 2. Add Priority Column
$sql2 = "ALTER TABLE helpdesk_requests ADD IF NOT EXISTS priority ENUM('Normal', 'Emergency') DEFAULT 'Normal' AFTER status";
if (mysqli_query($link, $sql2)) {
    echo "Priority column added/verified successfully.\n";
} else {
    echo "Error adding priority: " . mysqli_error($link) . "\n";
}

// 3. Add Location Column
$sql3 = "ALTER TABLE helpdesk_requests ADD IF NOT EXISTS location VARCHAR(255) DEFAULT '' AFTER user_email";
if (mysqli_query($link, $sql3)) {
    echo "Location column added/verified successfully.\n";
} else {
    echo "Error adding location: " . mysqli_error($link) . "\n";
}

// Re-check schema
echo "\nUpdated Schema:\n";
$q = mysqli_query($link, "DESCRIBE helpdesk_requests");
while($r = mysqli_fetch_assoc($q)){
    echo $r['Field'] . " | " . $r['Type'] . "\n";
}

mysqli_close($link);
?>
