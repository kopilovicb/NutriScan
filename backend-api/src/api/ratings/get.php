<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once '../auth.php';
require_once '../connection.php';

// Ako je prosleđen proizvod_id → ocene za taj proizvod
if (isset($_GET['proizvod_id'])) {
    $proizvod_id = (int)$_GET['proizvod_id'];

    $stmt = $pdo->prepare("
        SELECT o.*, k.email
        FROM ocene o
        LEFT JOIN korisnici k ON o.korisnik_id = k.id
        WHERE o.proizvod_id = ?
    ");
    $stmt->execute([$proizvod_id]);

    echo json_encode($stmt->fetchAll());
    exit;
}

// Inače → sve ocene svih proizvoda
$stmt = $pdo->query("
    SELECT o.*, k.email
    FROM ocene o
    LEFT JOIN korisnici k ON o.korisnik_id = k.id
");

echo json_encode($stmt->fetchAll());
