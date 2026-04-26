<?php
header('Content-Type: text/plain');

if (function_exists('mysqli_connect')) {
    echo "mysqli is available\n";
} else {
    echo "mysqli is NOT available\n";
}

if (extension_loaded('mysqli')) {
    echo "mysqli extension loaded\n";
} else {
    echo "mysqli extension NOT loaded\n";
}

try {
    $conn = new mysqli('localhost', 'u947809040_cuentanos', 'Cuentanos2026$', 'u947809040_cuentanosbase');
    echo "Database connected successfully\n";
    $conn->close();
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>
