<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/../auth.php';

header("Content-Type: application/json");

// ⬇️ auth.php je već validirao token
if (!isset($user_data['id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

// Read JSON body
$data = json_decode(file_get_contents("php://input"), true);

if (
    !isset($data['proizvod_id']) ||
    !isset($data['tekst']) ||
    empty(trim($data['tekst']))
) {
    http_response_code(400);
    echo json_encode(["error" => "Nedostaju podaci"]);
    exit;
}

$korisnik_id = (int)$user_data['id'];
$proizvod_id = (int)$data['proizvod_id'];
$tekst = trim($data['tekst']);

try {
    $stmt = $pdo->prepare("
        INSERT INTO komentari (korisnik_id, proizvod_id, tekst, datum)
        VALUES (:korisnik_id, :proizvod_id, :tekst, NOW())
    ");

    $stmt->execute([
        ':korisnik_id' => $korisnik_id,
        ':proizvod_id' => $proizvod_id,
        ':tekst' => $tekst
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Komentar uspešno dodat"
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Greška na serveru"
    ]);
}
