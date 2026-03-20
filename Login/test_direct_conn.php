<?php
$host = 'metro.proxy.rlwy.net';
$user = 'root';
$pass = 'PIwdbvMIwcphvFcpDooqIiXVZEQkatHv';
$db   = 'railway';
$port = 37624;

echo "Connecting to $host:$port as $user...\n";
$link = mysqli_init();
mysqli_options($link, MYSQLI_OPT_CONNECT_TIMEOUT, 10);
mysqli_options($link, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);

// Try with SSL
echo "Attempting with SSL...\n";
if (@mysqli_real_connect($link, $host, $user, $pass, $db, $port, null, MYSQLI_CLIENT_SSL)) {
    echo "Connected successfully with SSL!\n";
    exit;
} else {
    echo "SSL Connection failed: " . mysqli_connect_error() . "\n";
}

// Try without SSL
echo "Attempting without SSL...\n";
if (@mysqli_real_connect($link, $host, $user, $pass, $db, $port)) {
    echo "Connected successfully without SSL!\n";
} else {
    echo "Plain Connection failed: " . mysqli_connect_error() . "\n";
}
?>
