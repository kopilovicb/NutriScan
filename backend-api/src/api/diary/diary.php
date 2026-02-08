<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Authorization");

// Uključi auth i konekciju
require_once '../auth.php';
require_once '../connection.php';

$korisnik_id = $user_data['id']; // uzimamo iz JWT

try {
    $stmt = $pdo->prepare("
        SELECT d.id, p.naziv AS proizvod, d.kolicina_grama, d.datum_unosa, 
               (p.kalorije * d.kolicina_grama / 100) AS kalorije,
               (p.proteini * d.kolicina_grama / 100) AS proteini,
               (p.masti * d.kolicina_grama / 100) AS masti,
               (p.ugljeni_hidrati * d.kolicina_grama / 100) AS ugljeni_hidrati
        FROM dnevnik_ishrane d
        JOIN proizvodi p ON d.proizvod_id = p.id
        WHERE d.korisnik_id = ?
        ORDER BY d.datum_unosa DESC
    ");
    $stmt->execute([$korisnik_id]);
    $obroci = $stmt->fetchAll();

    echo json_encode($obroci);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Greška pri dohvatanju dnevnika: " . $e->getMessage()
    ]);
}
