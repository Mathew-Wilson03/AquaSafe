<?php
header('Content-Type: application/json');
require_once 'config.php';

// Allow only POST requests (except for fetch which might be GET, but we can do all in POST for simplicity or standard REST)
// Let's support both.

$method = $_SERVER['REQUEST_METHOD'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($method === 'GET' && $action === 'fetch_all') {
    $sql = "SELECT * FROM evacuation_points ORDER BY id DESC";
    $result = mysqli_query($link, $sql);
    $points = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $points[] = $row;
    }
    echo json_encode(['status' => 'success', 'data' => $points]);
    exit;
}

if ($method === 'POST') {
    if ($action === 'add') {
        $name = mysqli_real_escape_string($link, $_POST['name']);
        $location = mysqli_real_escape_string($link, $_POST['location']);
        $latitude = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : 0.0;
        $longitude = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : 0.0;
        $capacity = (int)$_POST['capacity'];
        $status = mysqli_real_escape_string($link, $_POST['status']);
        $sensor = mysqli_real_escape_string($link, $_POST['sensor']);

        $sql = "INSERT INTO evacuation_points (name, location, latitude, longitude, capacity, status, assigned_sensor) VALUES ('$name', '$location', $latitude, $longitude, $capacity, '$status', '$sensor')";
        if (mysqli_query($link, $sql)) {
            echo json_encode(['status' => 'success', 'message' => 'Evacuation point added successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => mysqli_error($link)]);
        }
        exit;
    }

    if ($action === 'update') {
        $id = (int)$_POST['id'];
        $name = mysqli_real_escape_string($link, $_POST['name']);
        $location = mysqli_real_escape_string($link, $_POST['location']);
        $latitude = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : 0.0;
        $longitude = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : 0.0;
        $capacity = (int)$_POST['capacity'];
        $status = mysqli_real_escape_string($link, $_POST['status']);
        $sensor = mysqli_real_escape_string($link, $_POST['sensor']);

        $sql = "UPDATE evacuation_points SET name='$name', location='$location', latitude=$latitude, longitude=$longitude, capacity=$capacity, status='$status', assigned_sensor='$sensor' WHERE id=$id";
        if (mysqli_query($link, $sql)) {
            echo json_encode(['status' => 'success', 'message' => 'Evacuation point updated successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => mysqli_error($link)]);
        }
        exit;
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $sql = "DELETE FROM evacuation_points WHERE id=$id";
        if (mysqli_query($link, $sql)) {
            echo json_encode(['status' => 'success', 'message' => 'Evacuation point deleted successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => mysqli_error($link)]);
        }
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action or request method. Action received: ' . $action]);
exit;

?>
