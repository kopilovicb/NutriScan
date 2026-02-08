<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../connection.php';

$naziv = $_GET['naziv'] ?? null;

if (!$naziv) {
    echo json_encode(["success" => false, "comments" => []]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT k.tekst, k.datum, u.ime
    FROM komentari k
    JOIN proizvodi p ON k.proizvod_id = p.id
    JOIN korisnici u ON k.korisnik_id = u.id
    WHERE p.naziv = ?
    ORDER BY k.id DESC
");
$stmt->execute([$naziv]);

echo json_encode([
    "success" => true,
    "comments" => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);
