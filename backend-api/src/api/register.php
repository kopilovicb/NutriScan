<?php

// ===================
// HEADERS
// ===================
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
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

// ===================
// INPUT
// ===================
$input = json_decode(file_get_contents("php://input"), true);

$ime = $input["ime"] ?? null;
$email = $input["email"] ?? null;
$lozinka = $input["lozinka"] ?? null;

if (!$ime || !$email || !$lozinka) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Sva polja su obavezna"
    ]);
    exit;
}

// ===================
// PASSWORD HASH
// ===================
$lozinka_hash = password_hash($lozinka, PASSWORD_BCRYPT);

try {
    $stmt = $pdo->prepare("
        INSERT INTO korisnici (ime, email, lozinka_hash, datum_registracije, je_admin)
        VALUES (?, ?, ?, NOW(), 0)
    ");

    $stmt->execute([$ime, $email, $lozinka_hash]);

    http_response_code(201);
    echo json_encode([
        "success" => true,
        "message" => "Registracija uspešna"
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Email već postoji ili greška u bazi"
        // za debug:
        // "details" => $e->getMessage()
    ]);
}