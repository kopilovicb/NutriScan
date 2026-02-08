<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../connection.php';

$data = json_decode(file_get_contents("php://input"), true);

$proizvod_id = $data['proizvod_id'] ?? null;
$kolicina = $data['kolicina'] ?? null;
$datum = $data['datum'] ?? null;

if(!$proizvod_id || !$kolicina || !$datum){
    http_response_code(400);
    echo json_encode(["error" => "Nedostaju podaci"]);
    exit;
}

$korisnik_id = $user_data['id'];

try {

    // Proveri da li proizvod postoji
    $check = $pdo->prepare("SELECT id FROM proizvodi WHERE id = ?");
    $check->execute([$proizvod_id]);

    if(!$check->fetch()){
        http_response_code(404);
        echo json_encode(["error" => "Proizvod ne postoji"]);
        exit;
    }

    // Dodaj u dnevnik
    $stmt = $pdo->prepare("
        INSERT INTO dnevnik_ishrane 
        (korisnik_id, proizvod_id, kolicina_grama, datum_unosa)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        $korisnik_id,
        $proizvod_id,
        $kolicina,
        $datum
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Obrok dodat u dnevnik"
    ]);

} catch(PDOException $e){

    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);

}
