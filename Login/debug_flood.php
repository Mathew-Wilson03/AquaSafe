<?php
require_once 'config.php';
$res = mysqli_query($link, "SELECT * FROM flood_data ORDER BY id DESC LIMIT 20");
$data = [];
while($row = mysqli_fetch_assoc($res)) {
    $data[] = $row;
}
echo json_encode($data);
?>
