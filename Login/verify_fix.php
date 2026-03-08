<?php
require_once 'config.php';

echo "<h3>Verifying IoT Data Flow...</h3>";

// 1. Simulate POST from sensor (SNS-001 is Churakullam)
$_POST = [
    'sensor_id' => 1,
    'level' => 12.50,
    'status' => 'WARNING'
];

echo "<p>Simulating sensor data for SNS-001 (Level: 12.50)...</p>";
include 'receive_iot_data.php'; 
// Note: receive_iot_data.php exits internally if not careful, but we handled its redirects/exits in previous edits for API usage.
// Let's assume it works or we'll trace it.

// 2. Check flood_data for the new entry
$res = mysqli_query($link, "SELECT * FROM flood_data ORDER BY created_at DESC LIMIT 1");
$row = mysqli_fetch_assoc($res);

if ($row && $row['sensor_id'] === 'SNS-001' && $row['location'] !== 'Unknown Cluster') {
    echo "<p style='color:green;'>SUCCESS: Entry found in flood_data with Location: " . $row['location'] . "</p>";
} else {
    echo "<p style='color:red;'>FAILED: Entry mismatch or missing location.</p>";
    print_r($row);
}

// 3. Verify get_user_safety_data.php response (Mocking Session)
// We'll just run the query logic since we can't easily mock session in a simple include without side effects
echo "<p>Checking dashboard sync logic...</p>";
$loc_esc = mysqli_real_escape_string($link, $row['location']);
$iot_sql = "SELECT level, status, created_at FROM flood_data 
            WHERE (location = '$loc_esc' OR location = 'System Wide' OR location = 'Unknown Cluster')
            ORDER BY created_at DESC LIMIT 1";
$iot_res = mysqli_query($link, $iot_sql);
$dash_row = mysqli_fetch_assoc($iot_res);

if ($dash_row) {
    echo "<p style='color:green;'>SUCCESS: Dashboard logic can find the data.</p>";
} else {
    echo "<p style='color:red;'>FAILED: Dashboard query returned no data.</p>";
}
?>
