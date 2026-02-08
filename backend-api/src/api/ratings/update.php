<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Auth + konekcija (SIGURNE putanje)
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../connection.php';

// JSON body
$data = json_decode(file_get_contents("php://input"), true);

// Validacija
if (!isset($data['id'], $data['ocena'])) {
    http_response_code(400);
    echo json_encode([
        "error" => "Nedostaju podaci"
    ]);
    exit;
}

$korisnik_id = $user_data['id'];
$id    = (int) $data['id'];
$ocena = (int) $data['ocena'];

// Update
try {
    $stmt = $pdo->prepare("
        UPDATE ocene 
        SET ocena = ?
        WHERE id = ? AND korisnik_id = ?
    ");
    $stmt->execute([$ocena, $id, $korisnik_id]);

    if ($stmt->rowCount() === 0) {
        echo json_encode([
            "error" => "Ocena nije pronađena ili nemaš pravo da menjaš"
        ]);
    } else {
        echo json_encode([
            "success" => true,
            "message" => "Ocena uspešno ažurirana"
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Greška na serveru"
    ]);
}
