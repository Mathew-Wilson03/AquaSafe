<?php
require_once 'config.php';

// Prepare a test POST to receive_iot_data.php
// Dynamically set URL to work on both localhost and Azure
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$url = $protocol . '://' . $host . '/AquaSafe/Login/receive_iot_data.php';

// In case the project root is mapped directly to Login folder on Azure
if (strpos($host, 'localhost') === false) {
    $url = $protocol . '://' . $host . '/Login/receive_iot_data.php'; 
}
$data = [
    'sensor_id' => 1,
    'level' => 15.5,
    'status' => 'WARNING'
];

echo "Simulating IoT POST to $url...\n";

$options = [
    'http' => [
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($data),
    ],
];

$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
    echo "ERROR: Request failed.\n";
} else {
    echo "RESPONSE: $result\n";
}

echo "\nChecking sensor_status for SNS-001...\n";
$res = mysqli_query($link, "SELECT * FROM sensor_status WHERE sensor_id = 'SNS-001'");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>
