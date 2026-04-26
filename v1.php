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

$conn->query("CREATE TABLE IF NOT EXISTS tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_type ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);
$path = str_replace('/v1.php', '', $path);
$path = str_replace('/api', '', $path);
$path = trim($path, '/');

function getAuthUser($conn) {
    $auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\s+(.+)$/i', $auth_header, $matches)) {
        return null;
    }
    $token = $matches[1];
    
    $stmt = $conn->prepare("SELECT t.*, a.email as admin_email FROM tokens t LEFT JOIN admins a ON t.user_id = a.id AND t.user_type = 'admin' WHERE t.token = ? AND t.expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return null;
    }
    
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data;
}

function requireAuth($conn) {
    $user = getAuthUser($conn);
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'No autenticado']);
        exit;
    }
    return $user;
}

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
elseif (preg_match('/^businesses\/(\d+)$/', $path, $matches) && $_SERVER['REQUEST_METHOD'] === 'GET') {
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
elseif ($path === 'businesses' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = requireAuth($conn);
    $data = json_decode(file_get_contents('php://input'), true);
    $name = trim($data['name'] ?? '');
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
            echo json_encode(['id' => $conn->insert_id, 'message' => 'Negocio creado correctamente']);
        } else {
            echo json_encode(['error' => 'Error al crear negocio']);
        }
        $stmt->close();
    }
}
elseif (preg_match('/^businesses\/(\d+)$/', $path, $matches) && $_SERVER['REQUEST_METHOD'] === 'PUT') {
    requireAuth($conn);
    $id = intval($matches[1]);
    $data = json_decode(file_get_contents('php://input'), true);
    $name = trim($data['name'] ?? '');
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
elseif (preg_match('/^businesses\/(\d+)$/', $path, $matches) && $_SERVER['REQUEST_METHOD'] === 'DELETE') {
    requireAuth($conn);
    $id = intval($matches[1]);
    $stmt = $conn->prepare("DELETE FROM businesses WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['message' => 'Negocio eliminado correctamente']);
    } else {
        echo json_encode(['error' => 'Error al eliminar negocio']);
    }
    $stmt->close();
}
elseif ($path === 'reviews-all') {
    requireAuth($conn);
    $result = $conn->query("SELECT r.*, b.name as business_name FROM reviews r LEFT JOIN businesses b ON r.business_id = b.id");
    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
    echo json_encode($reviews);
}
elseif ($path === 'reviews' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = requireAuth($conn);
    $data = json_decode(file_get_contents('php://input'), true);
    $business_id = intval($data['business_id'] ?? 0);
    $rating = intval($data['rating'] ?? 0);
    $comment = trim($data['comment'] ?? '');
    $user_name = $user['email'] ?? 'Anónimo';
    $user_id = intval($user['user_id']);

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
elseif ($path === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = trim(strtolower($data['email'] ?? ''));
    $password = $data['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        echo json_encode(['error' => 'Email y password son requeridos']);
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['error' => 'Email inválido']);
    } elseif (strlen($password) < 6) {
        echo json_encode(['error' => 'La contraseña debe tener al menos 6 caracteres']);
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode(['error' => 'El usuario ya existe']);
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO users (email, password_hash) VALUES (?, ?)");
            $stmt->bind_param("ss", $email, $password_hash);
            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+7 days'));
                
                $stmt_token = $conn->prepare("INSERT INTO tokens (user_id, user_type, token, expires_at) VALUES (?, 'user', ?, ?)");
                $stmt_token->bind_param("iss", $user_id, $token, $expires);
                $stmt_token->execute();
                $stmt_token->close();
                
                echo json_encode([
                    'token' => $token,
                    'user_id' => $user_id,
                    'email' => $email,
                    'expires_at' => $expires
                ]);
            } else {
                echo json_encode(['error' => 'Error al registrar usuario']);
            }
        }
        $stmt->close();
    }
}
elseif ($path === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = trim(strtolower($data['email'] ?? ''));
    $password = $data['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        echo json_encode(['error' => 'Email y password son requeridos']);
    } else {
        $stmt = $conn->prepare("SELECT id, email, password_hash FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password_hash'])) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+7 days'));
                
                $stmt_token = $conn->prepare("INSERT INTO tokens (user_id, user_type, token, expires_at) VALUES (?, 'user', ?, ?)");
                $stmt_token->bind_param("iss", $user['id'], $token, $expires);
                $stmt_token->execute();
                $stmt_token->close();
                
                echo json_encode([
                    'token' => $token,
                    'user_id' => $user['id'],
                    'email' => $user['email'],
                    'expires_at' => $expires
                ]);
            } else {
                echo json_encode(['error' => 'Credenciales inválidas']);
            }
        } else {
            echo json_encode(['error' => 'Credenciales inválidas']);
        }
        $stmt->close();
    }
}
elseif ($path === 'admin/login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = trim(strtolower($data['email'] ?? ''));
    $password = $data['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        echo json_encode(['error' => 'Email y password son requeridos']);
    } else {
        $stmt = $conn->prepare("SELECT id, email, password_hash FROM admins WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            if (password_verify($password, $admin['password_hash'])) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
                
                $stmt_token = $conn->prepare("INSERT INTO tokens (user_id, user_type, token, expires_at) VALUES (?, 'admin', ?, ?)");
                $stmt_token->bind_param("iss", $admin['id'], $token, $expires);
                $stmt_token->execute();
                $stmt_token->close();
                
                echo json_encode([
                    'token' => $token,
                    'admin_id' => $admin['id'],
                    'email' => $admin['email'],
                    'expires_at' => $expires
                ]);
            } else {
                echo json_encode(['error' => 'Credenciales inválidas']);
            }
        } else {
            echo json_encode(['error' => 'Credenciales inválidas']);
        }
        $stmt->close();
    }
}
elseif ($path === 'me' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $user = requireAuth($conn);
    echo json_encode([
        'user_id' => $user['user_id'],
        'user_type' => $user['user_type'],
        'email' => $user['email'] ?? $user['admin_email'] ?? null
    ]);
}
elseif ($path === 'logout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/^Bearer\s+(.+)$/i', $auth_header, $matches)) {
        $token = $matches[1];
        $stmt = $conn->prepare("DELETE FROM tokens WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->close();
    }
    echo json_encode(['message' => 'Sesión cerrada']);
}
else {
    echo json_encode(['error' => 'Endpoint no encontrado', 'path' => $path]);
}

$conn->close();
?>
