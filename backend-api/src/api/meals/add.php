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
$data = json_decode(file_get_contents("php://input"), true);

// ✅ DEBUG
error_log("RECEIVED DATA: " . json_encode($data));
error_log("KOLICINA: " . ($data['kolicina_grama'] ?? 'NULL'));
error_log("KALORIJE: " . ($data['kalorije'] ?? 'NULL'));

$naziv = $data['naziv'] ?? null;
// 📥 podaci sa frontenda
$naziv = $data['naziv'] ?? null;
$barkod = $data['barkod'] ?? null;
$kalorije = $data['kalorije'] ?? 0;
$proteini = $data['proteini'] ?? 0;
$masti = $data['masti'] ?? 0;
$uh = $data['ugljeni_hidrati'] ?? 0;
$kolicina = $data['kolicina_grama'] ?? 100;

if (!$naziv || !$barkod) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Nedostaju podaci o proizvodu"
    ]);
    exit;
}

// =========================
// 1️⃣ PROVERI DA LI PROIZVOD POSTOJI
// =========================
$stmt = $pdo->prepare("
    SELECT id FROM proizvodi WHERE naziv = ?
");
$stmt->execute([$naziv]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if ($product) {
    $proizvod_id = $product['id'];
} else {
    // =========================
    // 2️⃣ UBACI NOV PROIZVOD
    // =========================
    $stmt = $pdo->prepare("
        INSERT INTO proizvodi (naziv, kalorije, proteini, masti, ugljeni_hidrati)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $naziv,
        $kalorije,
        $proteini,
        $masti,
        $uh
    ]);

    $proizvod_id = $pdo->lastInsertId();
}

// =========================
// 3️⃣ DODAJ U DNEVNIK ISHRANE
// =========================
$stmt = $pdo->prepare("
    INSERT INTO dnevnik_ishrane (korisnik_id, proizvod_id, kolicina_grama, datum_unosa)
    VALUES (?, ?, ?, CURDATE())
");
$stmt->execute([
    $user_id,
    $proizvod_id,
    $kolicina
]);

echo json_encode([
    "success" => true,
    "message" => "Obrok dodat u dnevnik ishrane"
]);
