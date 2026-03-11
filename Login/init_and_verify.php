<?php
require_once 'config.php';

// Check if REC-001 exists
$res = mysqli_query($link, "SELECT * FROM sensor_status WHERE sensor_id = 'REC-001'");
if (mysqli_num_rows($res) == 0) {
    echo "Initializing REC-001 in sensor_status...\n";
    $sql = "INSERT INTO sensor_status (sensor_id, location_name, role, status, water_level) 
            VALUES ('REC-001', 'Idukki Hub', 'Receiver ESP', 'SAFE', 0.00)";
    if (mysqli_query($link, $sql)) {
        echo "REC-001 initialized successfully.\n";
    } else {
        echo "Error: " . mysqli_error($link) . "\n";
    }
} else {
    echo "REC-001 already exists.\n";
}

// Run the test simulation
echo "\n--- Simulating IoT POST ---\n";
// Dynamically set URL to work on both localhost and Azure
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$url = $protocol . '://' . $host . '/AquaSafe/Login/receive_iot_data.php';
if (strpos($host, 'localhost') === false) {
    $url = $protocol . '://' . $host . '/Login/receive_iot_data.php';
}
$data = ['sensor_id' => 1, 'level' => 18.75, 'status' => 'CRITICAL'];
$options = [
    'http' => [
        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
        'method' => 'POST',
        'content' => http_build_query($data),
    ],
];
$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);
echo "Result: $result\n";

// Check the results
echo "\n--- Verifying Database State ---\n";
echo "1. Daily Peak (from get_dashboard_stats.php logic):\n";
$peak_res = mysqli_query($link, "SELECT MAX(level) as peak FROM flood_data WHERE DATE(created_at) = CURDATE()");
echo "Current Peak: " . mysqli_fetch_assoc($peak_res)['peak'] . " ft\n";

echo "\n2. Device Statuses:\n";
$res = mysqli_query($link, "SELECT sensor_id, status, last_ping FROM sensor_status WHERE sensor_id IN ('SNS-001', 'REC-001')");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>
