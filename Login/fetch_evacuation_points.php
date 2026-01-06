<?php
header('Content-Type: application/json');
require_once 'config.php';

// Fetch all available/full evacuation points (excluding Closed if desired, or show all with status)
// Let's show all so users know if a nearby one is closed.
$sql = "SELECT * FROM evacuation_points ORDER BY id DESC";
$result = mysqli_query($link, $sql);

$points = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Add a dummy distance for now, or calculate if we had user coordinates
        // For UI demo, we can just randomize or leave empty
        $row['distance'] = rand(1, 15) . ' km'; 
        // Build a query string for Google Maps
        $row['query'] = urlencode($row['name'] . ' ' . $row['location']);
        $points[] = $row;
    }
}

echo json_encode(['status' => 'success', 'data' => $points]);
?>
