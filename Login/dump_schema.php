<?php
require 'config.php';
$q = mysqli_query($link, 'DESCRIBE help_requests');
while($r = mysqli_fetch_assoc($q)){
    print_r($r);
}
?>
