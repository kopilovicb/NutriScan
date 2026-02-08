<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/../auth.php';

header("Content-Type: application/json");

// auth.php je već validirao token
if (!isset($user_data['id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

// Čitanje JSON body-ja
$data = json_decode(file_get_contents("php://input"), true);

if (
    !isset($data['comment_id']) ||
    !isset($data['tekst']) ||
    empty(trim($data['tekst']))
) {
    http_response_code(400);
    echo json_encode(["error" => "Nedostaju podaci"]);
    exit;
}

$comment_id = (int)$data['comment_id'];
$novi_tekst = trim($data['tekst']);
$korisnik_id = (int)$user_data['id'];

try {
    // 1️⃣ Provera da li komentar postoji i ko je autor
    $check = $pdo->prepare("
        SELECT korisnik_id 
        FROM komentari 
        WHERE id = :id
    ");
    $check->execute([':id' => $comment_id]);
    $comment = $check->fetch(PDO::FETCH_ASSOC);

    if (!$comment) {
        http_response_code(404);
        echo json_encode(["error" => "Komentar ne postoji"]);
        exit;
    }

    if ((int)$comment['korisnik_id'] !== $korisnik_id) {
        http_response_code(403);
        echo json_encode(["error" => "Nemaš pravo da menjaš ovaj komentar"]);
        exit;
    }

    // 2️⃣ Update komentara
    $update = $pdo->prepare("
        UPDATE komentari
        SET tekst = :tekst
        WHERE id = :id
    ");
    $update->execute([
        ':tekst' => $novi_tekst,
        ':id' => $comment_id
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Komentar uspešno izmenjen"
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Greška na serveru"
    ]);
}
