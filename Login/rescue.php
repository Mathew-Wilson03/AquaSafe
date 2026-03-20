<?php
/**
 * AquaSafe Rescue Script - Lite Schema & Connection Check
 * Run this on the hosted site to see if the DB is even reachable.
 */

// 1. Minimum config for testing
$host = getenv('MYSQLHOST') ?: 'metro.proxy.rlwy.net';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: 'PIwdbvMIwcphvFcpDooqIiXVZEQkatHv';
$db   = getenv('MYSQLDATABASE') ?: 'railway';
$port = getenv('MYSQLPORT') ?: 37624;

echo "<h2>Rescue Diagnostic</h2>";
echo "Trying host: $host (Port: $port)<br>";

$start = microtime(true);
$link = mysqli_init();
mysqli_options($link, MYSQLI_OPT_CONNECT_TIMEOUT, 5);

if (@mysqli_real_connect($link, $host, $user, $pass, $db, (int)$port)) {
    $end = microtime(true);
    echo "<p style='color:green;'>✅ CONNECTED in " . round($end - $start, 3) . "s</p>";
    
    echo "<h3>Tables:</h3>";
    $res = mysqli_query($link, "SHOW TABLES");
    while($row = mysqli_fetch_array($res)) {
        echo "- " . $row[0] . "<br>";
    }
} else {
    echo "<p style='color:red;'>❌ CONNECTION FAILED after " . round(microtime(true) - $start, 3) . "s</p>";
    echo "Error: " . mysqli_connect_error();
}
?>
