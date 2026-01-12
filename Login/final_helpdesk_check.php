<?php
require_once 'config.php';
$sql = "SELECT id, user_name, user_email, title, status, created_at FROM helpdesk_requests ORDER BY created_at DESC LIMIT 10";
$result = mysqli_query($link, $sql);
echo "HELPDESK RECORDS:\n";
while($row = mysqli_fetch_assoc($result)){
    print_r($row);
}
?>
