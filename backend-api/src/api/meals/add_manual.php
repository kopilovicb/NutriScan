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

$naziv = trim($data['naziv'] ?? '');
$kalorije = (int)($data['kalorije'] ?? 0);
$proteini = (float)($data['proteini'] ?? 0);
$masti = (float)($data['masti'] ?? 0);
$uh = (float)($data['ugljeni_hidrati'] ?? 0);
$kolicina = (int)($data['kolicina_grama'] ?? 100);

if (!$naziv) {
    http_response_code(400);
    echo json_encode(["error" => "Naziv proizvoda je obavezan"]);
    exit;
}

// 🔍 Proveri da li proizvod već postoji
$stmt = $pdo->prepare("SELECT id FROM proizvodi WHERE naziv = ?");
$stmt->execute([$naziv]);
$proizvod = $stmt->fetch(PDO::FETCH_ASSOC);

if ($proizvod) {
    $proizvod_id = $proizvod['id'];
} else {
    // ➕ Kreiraj novi proizvod
    $stmt = $pdo->prepare("
        INSERT INTO proizvodi (naziv, kalorije, proteini, masti, ugljeni_hidrati)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$naziv, $kalorije, $proteini, $masti, $uh]);
    $proizvod_id = $pdo->lastInsertId();
}

// 📝 Dodaj u dnevnik ishrane
$stmt = $pdo->prepare("
    INSERT INTO dnevnik_ishrane (korisnik_id, proizvod_id, kolicina_grama, datum_unosa)
    VALUES (?, ?, ?, CURDATE())
");
$stmt->execute([$user_id, $proizvod_id, $kolicina]);

echo json_encode(["success" => true]);
