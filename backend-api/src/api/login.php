<?php

// ===================
// HEADERS
// ===================
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ===================
// DEPENDENCIES
// ===================
require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Firebase\JWT\JWT;

// ===================
// INPUT
// ===================
$data = json_decode(file_get_contents("php://input"), true);

$email = $data['email'] ?? null;
$lozinka = $data['lozinka'] ?? null;

if (!$email || !$lozinka) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Email i lozinka su obavezni"
    ]);
    exit;
}

// ===================
// USER CHECK
// ===================
$stmt = $pdo->prepare("
    SELECT id, ime, email, lozinka_hash, je_admin ,je_banovan
    FROM korisnici 
    WHERE email = ?
");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if ($user && $user['je_banovan']) {
    echo json_encode([
        "success" => false,
        "error" => "Vaš nalog je banovan. Kontaktirajte administratora."
    ]);
    exit;
}

if (!$user || !password_verify($lozinka, $user['lozinka_hash'])) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "error" => "Pogrešan email ili lozinka"
    ]);
    exit;
}

// ===================
// JWT
// ===================
$payload = [
    "iss" => "nutriscan",
    "iat" => time(),
    "exp" => time() + 60 * 60 * 24 * 7, // 7 dana
    "data" => [
        "id" => $user['id'],
        "email" => $user['email'],
        "je_admin" => $user['je_admin']
    ]
];

$secret_key = "supersecretkey123";
$jwt = JWT::encode($payload, $secret_key, 'HS256');

// ===================
// RESPONSE
// ===================
http_response_code(200);
echo json_encode([
    "success" => true,
    "token" => $jwt,
    "user" => [
        "id" => $user['id'],
        "ime" => $user['ime'],
        "email" => $user['email'],
        "je_admin" => $user['je_admin']
    ]
]);