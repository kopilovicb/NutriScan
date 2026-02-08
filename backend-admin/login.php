<?php
session_start();
header('Content-Type: application/json');

try {
    // ⬅️ UZIMA $pdo IZ db.php
    require_once __DIR__ . '/db.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
            'success' => false,
            'message' => 'Nevalidan zahtev'
        ]);
        exit;
    }

    $email = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        echo json_encode([
            'success' => false,
            'message' => 'Popunite sva polja'
        ]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT id, ime, email, lozinka_hash, je_admin
        FROM korisnici
        WHERE email = ?
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['lozinka_hash'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Pogrešan email ili lozinka'
        ]);
        exit;
    }

    if ((int)$user['je_admin'] !== 1) {
        echo json_encode([
            'success' => false,
            'message' => 'Nemate admin privilegije'
        ]);
        exit;
    }

    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['is_admin'] = true;

    echo json_encode([
        'success' => true,
        'redirect' => 'dashboard.php'
    ]);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Greška servera'
    ]);
    exit;
}
