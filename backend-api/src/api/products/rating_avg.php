<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../connection.php';

// =========================
// INPUT
// =========================
$naziv = $_GET['naziv'] ?? null;

if (!$naziv) {
    http_response_code(400);
    echo json_encode(["error" => "Nedostaje naziv"]);
    exit;
}

// =========================
// FIND PRODUCT
// =========================
$stmt = $pdo->prepare("SELECT id FROM proizvodi WHERE naziv = ?");
$stmt->execute([$naziv]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo json_encode([
        "success" => true,
        "avg" => 0,
        "total" => 0
    ]);
    exit;
}

$proizvod_id = $product['id'];

// =========================
// PROSEČNA OCENA - POJEDNOSTAVLJENA
// =========================
$stmt = $pdo->prepare("
    SELECT 
        ROUND(AVG(ocena), 1) AS avg_rating,
        COUNT(*) AS total
    FROM ocene
    WHERE proizvod_id = ?
");

$stmt->execute([$proizvod_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

// =========================
// RESPONSE
// =========================
echo json_encode([
    "success" => true,
    "avg" => (float)($row['avg_rating'] ?? 0),
    "total" => (int)($row['total'] ?? 0),
]);