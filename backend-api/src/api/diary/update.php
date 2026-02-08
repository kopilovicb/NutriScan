<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Uključi auth i konekciju
require_once '../auth.php';
require_once '../connection.php';

// Dohvati JSON telo request-a
$data = json_decode(file_get_contents("php://input"), true);

// Validacija ulaznih podataka
if(!isset($data['id'], $data['kolicina'], $data['datum'])){
    http_response_code(400);
    echo json_encode(["error" => "Nedostaju podaci"]);
    exit;
}

$id = (int)$data['id'];
$kolicina = (float)$data['kolicina'];
$datum = $data['datum'];
$korisnik_id = $user_data['id']; // uzimamo iz JWT tokena

try {
    $stmt = $pdo->prepare("
        UPDATE dnevnik_ishrane 
        SET kolicina_grama = ?, datum_unosa = ?
        WHERE id = ? AND korisnik_id = ?
    ");
    $stmt->execute([$kolicina, $datum, $id, $korisnik_id]);

    if($stmt->rowCount() > 0){
        echo json_encode([
            "success" => true,
            "message" => "Obrok ažuriran"
        ]);
    } else {
        echo json_encode([
            "error" => "Obrok nije pronađen ili nemaš pravo da menjaš"
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Greška pri ažuriranju obroka: " . $e->getMessage()
    ]);
}
