<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// ===================
// GET AUTH HEADER (ROBUST)
// ===================
$headers = getallheaders();

$authHeader = null;

if (isset($headers['Authorization'])) {
    $authHeader = $headers['Authorization'];
} elseif (isset($headers['authorization'])) {
    $authHeader = $headers['authorization'];
} elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
}

if (!$authHeader) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "error" => "Authorization header missing"
    ]);
    exit;
}

// ===================
// EXTRACT TOKEN
// ===================
$token = str_replace('Bearer ', '', $authHeader);

$secret_key = "supersecretkey123";

try {
    $decoded = JWT::decode($token, new Key($secret_key, 'HS256'));
    $auth_user = $decoded->data; // id, email, je_admin
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "error" => "Invalid token"
    ]);
    exit;
}