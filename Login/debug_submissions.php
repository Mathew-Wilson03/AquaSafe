<?php
require_once 'config.php';
$result = mysqli_query($link, "SELECT * FROM helpdesk_requests ORDER BY created_at DESC LIMIT 5");
echo "LAST 5 REQUESTS:\n";
while($row = mysqli_fetch_assoc($result)) {
    echo "[" . $row['id'] . "] " . $row['user_email'] . " - " . $row['title'] . " (" . $row['status'] . ") @ " . $row['created_at'] . "\n";
}
?>
