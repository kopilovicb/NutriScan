<?php


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/connection.php';

require_once __DIR__ . '/auth.php';

$user_id = $auth_user->id;


try {
    // =========================
    // USER DATA
    // =========================
    $stmt = $pdo->prepare("
        SELECT ime
        FROM korisnici
        WHERE id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode([
            "success" => false,
            "error" => "User not found"
        ]);
        exit;
    }

    // =========================
    // TODAY STATS
    // =========================
    $today = date('Y-m-d');

    $stmt = $pdo->prepare("
        SELECT 
            SUM(p.kalorije * (di.kolicina_grama / 100)) AS total_calories,
            COUNT(di.id) AS total_scans
        FROM dnevnik_ishrane di
        JOIN proizvodi p ON di.proizvod_id = p.id
        WHERE di.korisnik_id = ?
          AND di.datum_unosa = ?
    ");
    $stmt->execute([$user_id, $today]);
    $daily = $stmt->fetch(PDO::FETCH_ASSOC);

    // =========================
    // STREAK (poslednjih 7 dana)
    // =========================
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT datum_unosa) AS streak
        FROM dnevnik_ishrane
        WHERE korisnik_id = ?
          AND datum_unosa >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ");
    $stmt->execute([$user_id]);
    $streak = $stmt->fetch(PDO::FETCH_ASSOC);

    // =========================
    // RESPONSE
    // =========================
    echo json_encode([
        "success" => true,
        "name" => $user['ime'],
        "caloriesConsumed" => (int)($daily['total_calories'] ?? 0),
        "scansToday" => (int)($daily['total_scans'] ?? 0),
        "streak" => (int)($streak['streak'] ?? 0)
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
