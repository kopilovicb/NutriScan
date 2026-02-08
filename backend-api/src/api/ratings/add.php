<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once '../auth.php';
require_once '../connection.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['proizvod_id'], $data['ocena'])) {
    http_response_code(400);
    echo json_encode(["error" => "Nedostaju podaci"]);
    exit;
}

$korisnik_id = $user_data['id'];
$proizvod_id = (int)$data['proizvod_id'];
$ocena = (int)$data['ocena'];

try {
    $stmt = $pdo->prepare("
        INSERT INTO ocene (korisnik_id, proizvod_id, ocena)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$korisnik_id, $proizvod_id, $ocena]);

    echo json_encode(["success" => true, "message" => "Ocena dodata"]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
