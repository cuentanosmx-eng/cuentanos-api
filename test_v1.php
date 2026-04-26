<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo json_encode(['message' => 'API funcionando', 'path' => $_SERVER['REQUEST_URI']]);
?>
