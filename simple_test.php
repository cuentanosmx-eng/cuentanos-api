<?php
echo "PHP is working\n";
echo "Version: " . phpversion() . "\n";

if (function_exists('mysqli_connect')) {
    echo "mysqli: available\n";
} else {
    echo "mysqli: NOT available\n";
}

echo "\nTrying database connection...\n";

$conn = @new mysqli('localhost', 'u947809040_cuentanos', 'Cuentanos2026$', 'u947809040_cuentanosbase');

if ($conn->connect_error) {
    echo "Connection failed: " . $conn->connect_error . "\n";
} else {
    echo "Connection successful!\n";
    $conn->close();
}
?>
