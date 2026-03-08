<?php
require_once 'config.php';

$range = $_GET['range'] ?? '24h';
$area = $_GET['area'] ?? 'All';

$interval = "INTERVAL 1 DAY";
if ($range === '7d') $interval = "INTERVAL 7 DAY";
if ($range === '30d') $interval = "INTERVAL 30 DAY";

$loc_filter = "";
if ($area !== 'All') {
    $loc_filter = " AND location LIKE '%$area%'";
}

$filename = "AquaSafe_Report_" . date('Ymd_His') . ".csv";

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Time', 'Event', 'Location', 'Severity', 'Status']);

$sql = "SELECT timestamp, message, location, severity, 'Resolved' as status 
        FROM sensor_alerts 
        WHERE timestamp >= NOW() - $interval $loc_filter
        ORDER BY timestamp DESC";

$res = mysqli_query($link, $sql);
while ($row = mysqli_fetch_assoc($res)) {
    fputcsv($output, $row);
}

fclose($output);
exit;
?>
