<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

echo json_encode([
    'message' => 'API funcionando',
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'not set',
    'query_string' => $_SERVER['QUERY_STRING'] ?? 'not set',
    'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'not set'
]);
?>