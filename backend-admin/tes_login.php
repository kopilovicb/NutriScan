<?php
require_once __DIR__ . '/db.php';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    echo "✅ Povezan sa bazom!<br>";

    $stmt = $pdo->query("SELECT id, ime, email, je_admin, lozinka_hash FROM korisnici WHERE je_admin = 1");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "<h3>Admin nalog:</h3>";
    echo "<pre>";
    print_r($admin);
    echo "</pre>";

    // Test sa hash-om IZ BAZE
    $hash_iz_baze = $admin['lozinka_hash'];
    $test_password = 'admin123';

    echo "<h3>Test sa hash-om IZ BAZE:</h3>";
    echo "Hash iz baze: " . $hash_iz_baze . "<br>";
    echo "Dužina hash-a: " . strlen($hash_iz_baze) . " karaktera<br>";

    if (password_verify($test_password, $hash_iz_baze)) {
        echo "✅ Password verify radi sa hash-om IZ BAZE!";
    } else {
        echo "❌ Password verify NE radi sa hash-om IZ BAZE!<br>";

        // Generiši NOVI hash
        echo "<br><h3>Hajde da napravimo NOVI hash:</h3>";
        $novi_hash = password_hash('admin123', PASSWORD_DEFAULT);
        echo "Novi hash: <strong>" . $novi_hash . "</strong><br>";

        // Test novog hash-a
        if (password_verify('admin123', $novi_hash)) {
            echo "✅ NOVI hash radi!<br>";
            echo "<br><h3>🔧 URADI OVO U phpMyAdmin:</h3>";
            echo "<code style='background: #f0f0f0; padding: 10px; display: block;'>";
            echo "UPDATE korisnici SET lozinka_hash = '" . $novi_hash . "' WHERE email = 'admin@nutriscan.com';";
            echo "</code>";
        }
    }

} catch(PDOException $e) {
    echo "❌ Greška: " . $e->getMessage();
}
?>