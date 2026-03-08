<?php
require 'config.php';
$res = mysqli_query($link, 'DESCRIBE helpdesk_requests');
file_put_contents('schema.json', json_encode(mysqli_fetch_all($res, MYSQLI_ASSOC), JSON_PRETTY_PRINT));
echo "Done";
?>
