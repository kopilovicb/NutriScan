<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../connection.php';

$user_id = $auth_user->id;
$data = json_decode(file_get_contents("php://input"), true);

$naziv = $data['naziv'] ?? null;
$ocena = $data['ocena'] ?? null;

if (!$naziv || !$ocena || $ocena < 1 || $ocena > 5) {
    http_response_code(400);
    echo json_encode(["error" => "Neispravni podaci"]);
    exit;
}

// 🔎 proizvod po NAZIVU
$stmt = $pdo->prepare("SELECT id FROM proizvodi WHERE naziv = ?");
$stmt->execute([$naziv]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);

if ($p) {
    $proizvod_id = $p['id'];
} else {
    $stmt = $pdo->prepare("
        INSERT INTO proizvodi (naziv, kalorije, proteini, masti, ugljeni_hidrati)
        VALUES (?, 0, 0, 0, 0)
    ");
    $stmt->execute([$naziv]);
    $proizvod_id = $pdo->lastInsertId();
}

// ➕ ocena (jedna po korisniku po proizvodu)
$stmt = $pdo->prepare("
    INSERT INTO ocene (korisnik_id, proizvod_id, ocena)
    VALUES (?, ?, ?)
");
$stmt->execute([$user_id, $proizvod_id, $ocena]);

echo json_encode(["success" => true]);
