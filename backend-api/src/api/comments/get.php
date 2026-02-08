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

// validacija parametra
if (!isset($_GET['proizvod_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Nedostaje proizvod_id"]);
    exit;
}

$proizvod_id = (int) $_GET['proizvod_id'];

try {
    $stmt = $pdo->prepare("
        SELECT 
            k.id,
            k.tekst,
            k.datum,
            u.id AS korisnik_id,
            u.ime
        FROM komentari k
        JOIN korisnici u ON k.korisnik_id = u.id
        WHERE k.proizvod_id = :proizvod_id
        ORDER BY k.datum DESC
    ");

    $stmt->execute([
        ':proizvod_id' => $proizvod_id
    ]);

    echo json_encode([
        "success" => true,
        "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Greška na serveru"
    ]);
}
