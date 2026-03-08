<?php
require_once 'config.php';
header('Content-Type: text/plain');

echo "--- MySQL PROCESS LIST ---\n";
$res = mysqli_query($link, "SHOW FULL PROCESSLIST");
if (!$res) {
    echo "Error: " . mysqli_error($link) . "\n";
} else {
    while ($row = mysqli_fetch_assoc($res)) {
        printf("ID: %d | User: %s | Host: %s | DB: %s | Command: %s | Time: %d | State: %s | Info: %s\n",
            $row['Id'], $row['User'], $row['Host'], $row['db'], $row['Command'], $row['Time'], $row['State'], $row['Info']);
    }
}

echo "\n--- InnoDB Status ---\n";
$res = mysqli_query($link, "SHOW ENGINE INNODB STATUS");
if ($res) {
    $row = mysqli_fetch_assoc($res);
    echo $row['Status'];
}
?>
