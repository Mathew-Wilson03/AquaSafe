<?php
require_once 'config.php';
$sql = "SELECT COUNT(*) as count FROM helpdesk_requests";
$result = mysqli_query($link, $sql);
$row = mysqli_fetch_assoc($result);
echo "Total requests in DB: " . $row['count'] . "\n";

$sql = "SELECT id, user_email, title, status FROM helpdesk_requests ORDER BY created_at DESC LIMIT 5";
$result = mysqli_query($link, $sql);
echo "Latest 5 requests:\n";
while($row = mysqli_fetch_assoc($result)){
    print_r($row);
}
?>
