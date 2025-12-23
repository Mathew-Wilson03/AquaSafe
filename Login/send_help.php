<?php
session_start();
if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true){
    header('Location: login.php');
    exit;
}

$title = trim($_POST['title'] ?? '');
$details = trim($_POST['details'] ?? '');
if ($title === '' || $details === '') {
    header('Location: user_dashboard.php#section-help&help_sent=0');
    exit;
}

$file = __DIR__ . '/help_requests.json';
$list = [];
if (file_exists($file)) {
    $json = @file_get_contents($file);
    $decoded = @json_decode($json, true);
    if (is_array($decoded)) $list = $decoded;
}

$entry = [
    'id' => uniqid('req_'),
    'user' => $_SESSION['name'] ?? 'Unknown',
    'email' => $_SESSION['email'] ?? '',
    'title' => htmlspecialchars($title),
    'details' => htmlspecialchars($details),
    'time' => date('Y-m-d H:i:s'),
    'status' => 'open'
];
$list[] = $entry;
file_put_contents($file, json_encode($list, JSON_PRETTY_PRINT));

header('Location: user_dashboard.php#section-help?help_sent=1');
exit;

?>
