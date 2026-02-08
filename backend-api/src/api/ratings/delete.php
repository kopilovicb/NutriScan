<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once '../auth.php';
require_once '../connection.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Nedostaje ID"]);
    exit;
}

$korisnik_id = $user_data['id'];
$id = (int)$data['id'];

$stmt = $pdo->prepare("
    DELETE FROM ocene 
    WHERE id = ? AND korisnik_id = ?
");
$stmt->execute([$id, $korisnik_id]);

if ($stmt->rowCount() === 0) {
    echo json_encode(["error" => "Ocena nije pronađena ili nemaš pravo"]);
} else {
    echo json_encode(["success" => true, "message" => "Ocena obrisana"]);
}
