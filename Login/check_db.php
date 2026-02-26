<?php
require_once 'config.php';
$res = mysqli_query($link, "DESCRIBE alert_history");
$cols = [];
while($row = mysqli_fetch_assoc($res)) {
    $cols[] = $row;
}
echo "<pre>";
print_r($cols);
echo "</pre>";
foreach($cols as $c) {
    echo $c['Field'] . " (" . $c['Type'] . ")\n";
}
?>
