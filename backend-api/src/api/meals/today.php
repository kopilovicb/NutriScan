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

$user_id = $auth_user->id;

$stmt = $pdo->prepare("
    SELECT 
        p.naziv,
        di.kolicina_grama,
        ROUND(p.kalorije * di.kolicina_grama / 100, 2) AS kalorije
    FROM dnevnik_ishrane di
    JOIN proizvodi p ON di.proizvod_id = p.id
    WHERE di.korisnik_id = ?
      AND di.datum_unosa = CURDATE()
    ORDER BY di.id DESC
");

$stmt->execute([$user_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
foreach ($items as $i) {
    $total += (float)$i['kalorije'];
}

echo json_encode([
    "success" => true,
    "items" => $items,
    "totalCalories" => round($total, 2)
]);
