<?php


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$secret_key = "supersecretkey123";

// Uzmi Authorization header
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';

if (!$authHeader) {
    echo json_encode(["success" => false, "error" => "Token nije poslat"]);
    exit;
}

list($type, $jwt) = explode(" ", $authHeader, 2);

if ($type !== "Bearer" || !$jwt) {
    echo json_encode(["success" => false, "error" => "Neispravan token"]);
    exit;
}

try {
    $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
    $userId = $decoded->data->id;

    $stmt = $pdo->prepare("SELECT id AS userId, ime AS name, email FROM korisnici WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(["success" => false, "error" => "Korisnik ne postoji"]);
        exit;
    }

    // Vrati JSON
    echo json_encode([
        "success" => true,
        "user" => [
            "name" => $user['name'],
            "email" => $user['email'],
            "dailyLog" => 0 // Možeš ovde dodati iz baze ako postoji
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => "Nevažeći token: " . $e->getMessage()]);
}