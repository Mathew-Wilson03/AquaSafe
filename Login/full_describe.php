<?php
require_once 'config.php';
$res = mysqli_query($link, "DESCRIBE helpdesk_requests");
while($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>
