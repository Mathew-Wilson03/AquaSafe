<?php
require_once 'config.php';
$res = mysqli_query($link, 'SELECT * FROM sensor_status LIMIT 1');
$fields = mysqli_fetch_fields($res);
foreach ($fields as $field) {
    echo $field->name . PHP_EOL;
}
?>
