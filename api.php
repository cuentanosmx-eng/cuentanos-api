<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Database configuration
$db_host = 'localhost';
$db_user = 'u947809040_cuentanos';
$db_password = 'Cuentanos2026$';
$db_name = 'u947809040__cuentanosbase';

$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

if ($conn->connect_error) {
    echo json_encode(['error' => 'Error conectando a DB: ' . $conn->connect_error]);
    exit;
}

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$request = isset($_SERVER['REQUEST_URI']) ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '/';
$request = str_replace('/api', '', $request);

// Routes
switch ($request) {
    case '/test':
    case '/':
        echo json_encode(['message' => 'API funcionando']);
        break;
        
    case '/register':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $email = isset($data['email']) ? $data['email'] : '';
            $password = isset($data['password']) ? $data['password'] : '';
            
            if (empty($email) || empty($password)) {
                echo json_encode(['error' => 'Email y password son requeridos']);
                break;
            }
            
            // Check if email exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                echo json_encode(['error' => 'El usuario ya existe']);
            } else {
                // Insert new user
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
        break;
        
    case '/login':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $email = isset($data['email']) ? $data['email'] : '';
            $password = isset($data['password']) ? $data['password'] : '';
            
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
        break;
        
    case '/businesses':
        $stmt = $conn->prepare("SELECT id, name, category, rating, location, image FROM businesses");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $businesses = [];
        while ($row = $result->fetch_assoc()) {
            $businesses[] = $row;
        }
        echo json_encode($businesses);
        $stmt->close();
        break;
        
    case '/businesses/' . trim($request, '/businesses/'):
        $id = isset($data['id']) ? $data['id'] : 0;
        // This needs better handling for GET requests
        break;
        
    default:
        // Check if it's a business detail request
        if (preg_match('/^\/businesses\/(\d+)$/', $request, $matches)) {
            $id = intval($matches[1]);
            $stmt = $conn->prepare("SELECT * FROM businesses WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $business = $result->fetch_assoc();
                
                // Get reviews
                $stmt2 = $conn->prepare("SELECT user, comment, rating FROM reviews WHERE business_id = ?");
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
        } else {
            echo json_encode(['error' => 'Endpoint no encontrado']);
        }
        break;
}

$conn->close();
?>