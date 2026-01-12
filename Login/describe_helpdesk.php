<?php
require_once 'config.php';
$res = mysqli_query($link, 'DESCRIBE helpdesk_requests');
echo "TABLE SCHEMA:\n";
while($row = mysqli_fetch_assoc($res)) {
    echo str_pad($row['Field'], 15) . " | " . str_pad($row['Type'], 20) . " | " . $row['Null'] . "\n";
}
?>
