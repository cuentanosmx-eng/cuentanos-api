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
elseif ($path === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        echo json_encode(['error' => 'Email y password son requeridos']);
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode(['error' => 'El usuario ya existe']);
        } else {
            $stmt = $conn->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $email, $password);
            if ($stmt->execute()) {
                echo json_encode(['message' => 'Usuario registrado correctamente']);
            } else {
                echo json_encode(['error' => 'Error al registrar usuario']);
            }
        }
        $stmt->close();
    }
}
elseif ($path === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    
    $stmt = $conn->prepare("SELECT id, email FROM users WHERE email = ? AND password = ?");
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $token = bin2hex(random_bytes(32));
        echo json_encode(['token' => $token, 'user_id' => $user['id'], 'email' => $user['email']]);
    } else {
        echo json_encode(['error' => 'Credenciales inválidas']);
    }
    $stmt->close();
}
elseif ($path === 'reviews' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $business_id = intval($data['business_id'] ?? 0);
    $user_id = intval($data['user_id'] ?? 0);
    $user_name = $data['user_name'] ?? 'Anónimo';
    $rating = intval($data['rating'] ?? 0);
    $comment = trim($data['comment'] ?? '');

    if ($business_id <= 0) {
        echo json_encode(['error' => 'ID de negocio es requerido']);
    } elseif ($rating < 1 || $rating > 5) {
        echo json_encode(['error' => 'Rating debe ser entre 1 y 5']);
    } elseif (strlen($comment) < 10) {
        echo json_encode(['error' => 'El comentario debe tener al menos 10 caracteres']);
    } else {
        $stmt = $conn->prepare("INSERT INTO reviews (business_id, user_id, user_name, rating, comment) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisis", $business_id, $user_id, $user_name, $rating, $comment);
        if ($stmt->execute()) {
            echo json_encode(['id' => $conn->insert_id, 'message' => 'Reseña publicada correctamente']);
        } else {
            echo json_encode(['error' => 'Error al publicar reseña']);
        }
        $stmt->close();
    }
}
else {
    echo json_encode(['error' => 'Endpoint no encontrado', 'path' => $path]);
}

$conn->close();
?>
