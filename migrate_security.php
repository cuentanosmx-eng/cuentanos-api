<?php
header('Content-Type: application/json');

$db_host = 'localhost';
$db_user = 'u947809040_cuentanos';
$db_password = 'Cuentanos2026$';
$db_name = 'u947809040_cuentanosbase';

$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

if ($conn->connect_error) {
    echo json_encode(['error' => 'Connection failed: ' . $conn->connect_error]);
    exit;
}

$messages = [];

// 1. Create tokens table
$sql = "CREATE TABLE IF NOT EXISTS tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_type ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === TRUE) {
    $messages[] = "Tokens table created";
} else {
    $messages[] = "Tokens table error: " . $conn->error;
}

// 2. Create admins table
$sql = "CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === TRUE) {
    $messages[] = "Admins table created";
} else {
    $messages[] = "Admins table error: " . $conn->error;
}

// 3. Check if password_hash column exists in users
$result = $conn->query("DESCRIBE users");
$has_password_hash = false;
while ($row = $result->fetch_assoc()) {
    if ($row['Field'] === 'password_hash') {
        $has_password_hash = true;
        break;
    }
}

if (!$has_password_hash) {
    // Add password_hash column
    $sql = "ALTER TABLE users ADD COLUMN password_hash VARCHAR(255) AFTER password";
    if ($conn->query($sql) === TRUE) {
        $messages[] = "Added password_hash column to users";
    } else {
        $messages[] = "Error adding password_hash: " . $conn->error;
    }
    
    // Migrate existing passwords (hash them)
    $result = $conn->query("SELECT id, password FROM users WHERE password_hash IS NULL OR password_hash = ''");
    $migrated = 0;
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['password'])) {
            $hash = password_hash($row['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->bind_param("si", $hash, $row['id']);
            $stmt->execute();
            $stmt->close();
            $migrated++;
        }
    }
    $messages[] = "Migrated $migrated users to secure passwords";
} else {
    $messages[] = "Users already have password_hash";
}

// 4. Create initial admin if not exists
$admin_email = 'admin@cuentanos.mx';
$admin_password = 'Cuentanos2026$';

$result = $conn->query("SELECT id FROM admins WHERE email = '$admin_email'");
if ($result->num_rows === 0) {
    $hash = password_hash($admin_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO admins (email, password_hash) VALUES (?, ?)");
    $stmt->bind_param("ss", $admin_email, $hash);
    if ($stmt->execute()) {
        $messages[] = "Admin user created: $admin_email / $admin_password";
    } else {
        $messages[] = "Error creating admin: " . $conn->error;
    }
    $stmt->close();
} else {
    $messages[] = "Admin user already exists";
}

echo json_encode([
    'success' => true,
    'messages' => $messages
]);

$conn->close();
?>
