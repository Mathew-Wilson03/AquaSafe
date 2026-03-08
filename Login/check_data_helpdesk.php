<?php
require 'config.php';
$res = mysqli_query($link, 'SELECT * FROM helpdesk_requests ORDER BY id DESC LIMIT 20');
$data = mysqli_fetch_all($res, MYSQLI_ASSOC);
file_put_contents('helpdesk_data.json', json_encode($data, JSON_PRETTY_PRINT));
echo "Dumped " . count($data) . " records to helpdesk_data.json";
?>
