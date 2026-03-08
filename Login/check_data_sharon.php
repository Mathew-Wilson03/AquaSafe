<?php
require 'config.php';
$res = mysqli_query($link, "SELECT * FROM helpdesk_requests WHERE user_email = 'sharonshiju03@gmail.com' ORDER BY id DESC");
$data = mysqli_fetch_all($res, MYSQLI_ASSOC);
file_put_contents('helpdesk_sharon.json', json_encode($data, JSON_PRETTY_PRINT));
echo "Dumped " . count($data) . " records to helpdesk_sharon.json";
?>
