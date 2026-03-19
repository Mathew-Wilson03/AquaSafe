<?php
require_once 'config.php';
$res = mysqli_query($link, "SELECT * FROM users LIMIT 1");
$fields = mysqli_fetch_fields($res);
foreach ($fields as $field) {
    echo $field->name . "\n";
}
?>
