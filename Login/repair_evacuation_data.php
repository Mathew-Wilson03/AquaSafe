<?php
require_once 'config.php';

// Function to deep clean strings from control characters and excessive whitespace
function deepClean($string) {
    // Remove control characters (including \r, \n, \t)
    $clean = preg_replace('/[\x00-\x1F\x7F]/', '', $string);
    // Trim and collapse multiple spaces
    $clean = preg_replace('/\s+/', ' ', $clean);
    return trim($clean);
}

$sql = "SELECT id, name, location, status FROM evacuation_points";
$result = mysqli_query($link, $sql);

if ($result) {
    echo "Found " . mysqli_num_rows($result) . " records to inspect.\n";
    while ($row = mysqli_fetch_assoc($result)) {
        $cleanName = deepClean($row['name']);
        $cleanLocation = deepClean($row['location']);
        $cleanStatus = deepClean($row['status']);
        
        if ($cleanName !== $row['name'] || $cleanLocation !== $row['location'] || $cleanStatus !== $row['status']) {
            $id = $row['id'];
            $updateSql = "UPDATE evacuation_points SET 
                          name = '" . mysqli_real_escape_string($link, $cleanName) . "', 
                          location = '" . mysqli_real_escape_string($link, $cleanLocation) . "',
                          status = '" . mysqli_real_escape_string($link, $cleanStatus) . "'
                          WHERE id = $id";
            
            if (mysqli_query($link, $updateSql)) {
                echo "Fixed ID $id: '{$row['name']}' -> '$cleanName'\n";
            } else {
                echo "Error fixing ID $id: " . mysqli_error($link) . "\n";
            }
        } else {
            echo "ID {$row['id']} is already clean.\n";
        }
    }
} else {
    echo "Error fetching records: " . mysqli_error($link) . "\n";
}

mysqli_close($link);
?>
