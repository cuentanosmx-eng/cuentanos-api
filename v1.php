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
    echo json_encode(['error' => 'Error conectando a DB: ' . $conn->connect_error]);
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
        $token = bin2hex(random_bytes(32));
        echo json_encode(['token' => $token, 'message' => 'Login exitoso']);
    } else {
        echo json_encode(['error' => 'Credenciales inválidas']);
    }
    $stmt->close();
}
elseif ($path === 'businesses' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = $conn->query("SELECT id, name, category, rating, location, image FROM businesses");
    $businesses = [];
    while ($row = $result->fetch_assoc()) {
        $stmt_avg = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE business_id = ?");
        $stmt_avg->bind_param("i", $row['id']);
        $stmt_avg->execute();
        $avg_result = $stmt_avg->get_result();
        $avg_data = $avg_result->fetch_assoc();
        $stmt_avg->close();

        $row['rating'] = $avg_data['avg_rating'] ? round(floatval($avg_data['avg_rating']), 2) : floatval($row['rating']);
        $row['review_count'] = intval($avg_data['review_count']);
        $businesses[] = $row;
    }
    echo json_encode($businesses);
}
elseif ($path === 'businesses' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $name = $data['name'] ?? '';
    $description = $data['description'] ?? '';
    $category = $data['category'] ?? '';
    $rating = floatval($data['rating'] ?? 0);
    $location = $data['location'] ?? '';
    $image = $data['image'] ?? '';
    
    if (empty($name)) {
        echo json_encode(['error' => 'El nombre es requerido']);
    } else {
        $stmt = $conn->prepare("INSERT INTO businesses (name, description, category, rating, location, image) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdss", $name, $description, $category, $rating, $location, $image);
        
        if ($stmt->execute()) {
            $id = $conn->insert_id;
            echo json_encode(['id' => $id, 'message' => 'Negocio creado correctamente']);
        } else {
            echo json_encode(['error' => 'Error al crear negocio']);
        }
        $stmt->close();
    }
}
elseif (preg_match('/^businesses\/(\d+)$/', $path, $matches)) {
    $id = intval($matches[1]);
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $conn->prepare("SELECT * FROM businesses WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $business = $result->fetch_assoc();

            $stmt2 = $conn->prepare("SELECT * FROM reviews WHERE business_id = ? ORDER BY created_at DESC");
            $stmt2->bind_param("i", $id);
            $stmt2->execute();
            $reviews_result = $stmt2->get_result();

            $reviews = [];
            $total_rating = 0;
            while ($row = $reviews_result->fetch_assoc()) {
                $reviews[] = $row;
                $total_rating += intval($row['rating']);
            }
            $business['reviews'] = $reviews;
            $business['review_count'] = count($reviews);
            $business['rating'] = count($reviews) > 0 ? round($total_rating / count($reviews), 2) : floatval($business['rating']);
            $stmt2->close();

            echo json_encode($business);
        } else {
            echo json_encode(['error' => 'Negocio no encontrado']);
        }
        $stmt->close();
    }
    elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true);
        $name = $data['name'] ?? '';
        $description = $data['description'] ?? '';
        $category = $data['category'] ?? '';
        $rating = floatval($data['rating'] ?? 0);
        $location = $data['location'] ?? '';
        $image = $data['image'] ?? '';
        
        $stmt = $conn->prepare("UPDATE businesses SET name = ?, description = ?, category = ?, rating = ?, location = ?, image = ? WHERE id = ?");
        $stmt->bind_param("sssdssi", $name, $description, $category, $rating, $location, $image, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['message' => 'Negocio actualizado correctamente']);
        } else {
            echo json_encode(['error' => 'Error al actualizar negocio']);
        }
        $stmt->close();
    }
    elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $stmt = $conn->prepare("DELETE FROM businesses WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['message' => 'Negocio eliminado correctamente']);
        } else {
            echo json_encode(['error' => 'Error al eliminar negocio']);
        }
        $stmt->close();
    }
}
elseif ($path === 'reviews' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $business_id = intval($data['business_id'] ?? 0);
    $user_id = intval($data['user_id'] ?? 0);
    $user_name = $data['user_name'] ?? '';
    $rating = intval($data['rating'] ?? 0);
    $comment = trim($data['comment'] ?? '');

    if ($business_id <= 0) {
        echo json_encode(['error' => 'ID de negocio es requerido']);
    }
    elseif ($user_id <= 0) {
        echo json_encode(['error' => 'Usuario no autenticado']);
    }
    elseif ($rating < 1 || $rating > 5) {
        echo json_encode(['error' => 'Rating debe ser entre 1 y 5']);
    }
    elseif (strlen($comment) < 10) {
        echo json_encode(['error' => 'El comentario debe tener al menos 10 caracteres']);
    }
    else {
        $stmt = $conn->prepare("INSERT INTO reviews (business_id, user_id, user_name, rating, comment) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisis", $business_id, $user_id, $user_name, $rating, $comment);

        if ($stmt->execute()) {
            $id = $conn->insert_id;
            echo json_encode(['id' => $id, 'message' => 'Reseña publicada correctamente']);
        } else {
            echo json_encode(['error' => 'Error al publicar reseña']);
        }
        $stmt->close();
    }
}
elseif (preg_match('/^reviews\/(\d+)$/', $path, $matches) && $_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id = intval($matches[1]);
    $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['message' => 'Reseña eliminada correctamente']);
    } else {
        echo json_encode(['error' => 'Error al eliminar reseña']);
    }
    $stmt->close();
}
elseif (preg_match('/^reviews-business\/(\d+)$/', $path, $matches)) {
    $business_id = intval($matches[1]);
    $stmt = $conn->prepare("SELECT * FROM reviews WHERE business_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $business_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
    echo json_encode($reviews);
    $stmt->close();
}
elseif ($path === 'reviews-all') {
    $result = $conn->query("SELECT r.*, b.name as business_name FROM reviews r LEFT JOIN businesses b ON r.business_id = b.id ORDER BY r.created_at DESC");
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
