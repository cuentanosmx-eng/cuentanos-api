<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$db_host = 'localhost';
$db_user = 'u947809040_cuentanos';
$db_password = 'Cuentanos2026$';
$db_name = 'u947809040_cuentanosbase';

$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

if ($conn->connect_error) {
    echo json_encode(['error' => 'Error conectando a DB']);
    exit;
}

$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);
$path = str_replace('/v1.php', '', $path);
$path = str_replace('/v1_simple.php', '', $path);
$path = str_replace('/api', '', $path);
$path = trim($path, '/');

if ($path === '' || $path === 'test') {
    echo json_encode(['message' => 'API funcionando']);
}
elseif ($path === 'businesses' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = $conn->query("SELECT id, name, category, rating, location, image FROM businesses");
    $businesses = [];
    while ($row = $result->fetch_assoc()) {
        $businesses[] = $row;
    }
    echo json_encode($businesses);
}
elseif (preg_match('/^businesses\/(\d+)$/', $path, $matches)) {
    $id = intval($matches[1]);
    $stmt = $conn->prepare("SELECT * FROM businesses WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $business = $result->fetch_assoc();
        
        $stmt2 = $conn->prepare("SELECT * FROM reviews WHERE business_id = ?");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $reviews_result = $stmt2->get_result();
        
        $reviews = [];
        while ($row = $reviews_result->fetch_assoc()) {
            $reviews[] = $row;
        }
        $business['reviews'] = $reviews;
        $stmt2->close();
        
        echo json_encode($business);
    } else {
        echo json_encode(['error' => 'Negocio no encontrado']);
    }
    $stmt->close();
}
elseif ($path === 'reviews-all') {
    $result = $conn->query("SELECT r.*, b.name as business_name FROM reviews r LEFT JOIN businesses b ON r.business_id = b.id");
    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
    echo json_encode($reviews);
}
else {
    echo json_encode(['error' => 'Endpoint no encontrado', 'path' => $path]);
}

$conn->close();
?>
