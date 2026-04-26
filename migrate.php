<?php
$db_host = 'localhost';
$db_user = 'u947809040_cuentanos';
$db_password = 'Cuentanos2026$';
$db_name = 'u947809040_cuentanosbase';

$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

$result = $conn->query("DESCRIBE reviews");
$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}

if (!in_array('user_id', $columns)) {
    $sql = "ALTER TABLE reviews ADD COLUMN user_id INT NOT NULL DEFAULT 1 AFTER business_id";
    if ($conn->query($sql)) {
        echo json_encode(['message' => 'Added user_id column']);
    } else {
        echo json_encode(['error' => 'Failed to add column: ' . $conn->error]);
    }
} else {
    echo json_encode(['message' => 'user_id column already exists']);
}

$conn->close();
?>
