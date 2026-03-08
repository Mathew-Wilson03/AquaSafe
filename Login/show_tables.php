<?php
require 'config.php';
$q = mysqli_query($link, 'SHOW TABLES');
while($r = mysqli_fetch_row($q)){
    echo $r[0] . "\n";
}
?>
