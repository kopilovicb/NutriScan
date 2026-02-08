<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE");
header("Access-Control-Allow-Headers: Authorization, Content-Type");

require_once '../auth.php';
require_once '../connection.php';

// Uzimanje JSON inputa
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'])) {
    http_response_code(400);
    echo json_encode(["error" => "ID obroka je obavezan"]);
    exit;
}

$korisnik_id = $user_data['id']; // iz JWT
$obrok_id = $data['id'];

try {
    $stmt = $pdo->prepare("DELETE FROM dnevnik_ishrane WHERE id = ? AND korisnik_id = ?");
    $stmt->execute([$obrok_id, $korisnik_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => true, "message" => "Obrok je uspešno obrisan"]);
    } else {
        http_response_code(404);
        echo json_encode(["error" => "Obrok nije pronađen ili ne pripada korisniku"]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Greška pri brisanju obroka: ".$e->getMessage()]);
}
