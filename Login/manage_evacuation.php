<?php
header('Content-Type: application/json');
require_once 'config.php';

// Allow only POST requests (except for fetch which might be GET, but we can do all in POST for simplicity or standard REST)
// Let's support both.

$method = $_SERVER['REQUEST_METHOD'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (!$link) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . mysqli_connect_error()]);
    exit;
}

// Aggressive sanitation to strip control characters (\r, \n, etc) that break JSON/Map
function deepClean($str) {
    if (!$str) return '';
    // Strip control characters
    $str = preg_replace('/[\x00-\x1F\x7F]/', '', $str);
    // Collapse multiple spaces/whitespace
    $str = preg_replace('/\s+/', ' ', $str);
    return trim($str);
}

if ($method === 'GET' && $action === 'fetch_all') {
    $sql = "SELECT * FROM evacuation_points ORDER BY id DESC";
    $result = mysqli_query($link, $sql);
    $points = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Sanitize outputs to prevent hidden characters from breaking JSON/Map
        $row['name'] = deepClean($row['name']);
        $row['location'] = deepClean($row['location']);
        $row['status'] = deepClean($row['status']);
        $row['assigned_sensor'] = deepClean($row['assigned_sensor']);
        $points[] = $row;
    }
    echo json_encode(['status' => 'success', 'data' => $points]);
    exit;
}

if ($method === 'POST') {
    if ($action === 'add') {
        $name = deepClean(mysqli_real_escape_string($link, $_POST['name'] ?? ''));
        $location = deepClean(mysqli_real_escape_string($link, $_POST['location'] ?? ''));
        $latitude = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : 0.0;
        $longitude = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : 0.0;
        $capacity = (int)($_POST['capacity'] ?? 0);
        $status = deepClean(mysqli_real_escape_string($link, $_POST['status'] ?? 'Available'));
        $sensor = deepClean(mysqli_real_escape_string($link, $_POST['sensor'] ?? ''));

        if (empty($name) || empty($location)) {
            echo json_encode(['status' => 'error', 'message' => 'Name and location are required']);
            exit;
        }

        $sql = "INSERT INTO evacuation_points (name, location, latitude, longitude, capacity, status, assigned_sensor) VALUES ('$name', '$location', $latitude, $longitude, $capacity, '$status', '$sensor')";
        if (mysqli_query($link, $sql)) {
            echo json_encode(['status' => 'success', 'message' => 'Evacuation point added successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => mysqli_error($link)]);
        }
        exit;
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $name = deepClean(mysqli_real_escape_string($link, $_POST['name'] ?? ''));
        $location = deepClean(mysqli_real_escape_string($link, $_POST['location'] ?? ''));
        $latitude = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : 0.0;
        $longitude = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : 0.0;
        $capacity = (int)($_POST['capacity'] ?? 0);
        $status = deepClean(mysqli_real_escape_string($link, $_POST['status'] ?? 'Available'));
        $sensor = deepClean(mysqli_real_escape_string($link, $_POST['sensor'] ?? ''));

        if (!$id || empty($name) || empty($location)) {
            echo json_encode(['status' => 'error', 'message' => 'ID, name, and location are required']);
            exit;
        }

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

    if ($action === 'geocode') {
        $address = $_POST['address'] ?? '';
        if (empty($address)) {
            echo json_encode(['status' => 'error', 'message' => 'No address provided']);
            exit;
        }

        // Append Kerala context if not present for better accuracy
        if (stripos($address, 'Kerala') === false) {
            $address .= ", Kerala, India";
        }

        $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($address) . "&limit=1";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'AquaSafe-Admin-System/1.0 (Mathew-Wilson03/AquaSafe)');
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $data = json_decode($response, true);
            if (!empty($data)) {
                echo json_encode(['status' => 'success', 'data' => $data[0]]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Location not found. Try adding more details like a district or city.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'External search service returned error code: ' . $httpCode]);
        }
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action or request method. Action received: ' . $action]);
exit;

?>
