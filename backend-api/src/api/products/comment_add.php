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
$tekst = $data['tekst'] ?? null;

if (!$naziv || !$tekst) {
    http_response_code(400);
    echo json_encode(["error" => "Nedostaju podaci"]);
    exit;
}

// 🔎 proizvod po NAZIVU
$stmt = $pdo->prepare("SELECT id FROM proizvodi WHERE naziv = ?");
$stmt->execute([$naziv]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);

if ($p) {
    $proizvod_id = $p['id'];
} else {
    // ako proizvod ne postoji, dodaj ga
    $stmt = $pdo->prepare("
        INSERT INTO proizvodi (naziv, kalorije, proteini, masti, ugljeni_hidrati)
        VALUES (?, 0, 0, 0, 0)
    ");
    $stmt->execute([$naziv]);
    $proizvod_id = $pdo->lastInsertId();
}

// ➕ komentar
$stmt = $pdo->prepare("
    INSERT INTO komentari (korisnik_id, proizvod_id, tekst, datum)
    VALUES (?, ?, ?, NOW())
");
$stmt->execute([$user_id, $proizvod_id, $tekst]);

echo json_encode(["success" => true]);
