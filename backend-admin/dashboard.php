<?php
session_start();

// Provera da li je korisnik prijavljen i da li je admin
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: index.html');
    exit;
}

require_once __DIR__ . '/db.php';

    // Statistika
    $stmt = $pdo->query("SELECT COUNT(*) FROM korisnici");
    $ukupno_korisnika = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM proizvodi");
    $ukupno_proizvoda = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM dnevnik_ishrane WHERE datum_unosa = CURDATE()");
    $unosa_danas = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM komentari");
    $ukupno_komentara = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM ocene");
    $ukupno_ocena = $stmt->fetchColumn();


$ime = $_SESSION['ime'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriScan - Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .dashboard {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 1000px;
            margin: 0 auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .dashboard h1 {
            color: #333;
            margin-bottom: 10px;
        }

        .welcome {
            color: #06b6d4;
            font-size: 18px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .logout-btn {
            background: #ff4757;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            color: white;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s;
            margin-top: 30px;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            background: #e84118;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .stat-card {
            background: linear-gradient(135deg, #06b6d4 0%, #10b981 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            font-size: 36px;
            margin: 10px 0;
            font-weight: 700;
        }

        .stat-card p {
            opacity: 0.95;
            font-size: 14px;
        }

        .quick-actions {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #f0f0f0;
        }

        .quick-actions h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 20px;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .action-btn {
            padding: 15px 20px;
            background: white;
            border: 2px solid #06b6d4;
            color: #06b6d4;
            border-radius: 8px;
            text-decoration: none;
            display: block;
            text-align: center;
            font-weight: 600;
            transition: all 0.2s;
        }

        .action-btn:hover {
            background: #06b6d4;
            color: white;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
<div class="container" style="max-width: 1000px; margin-top: 50px;">
    <div class="dashboard">
        <h1>🥗 NutriScan Admin Panel</h1>
        <p class="welcome">Dobrodošli, <strong><?php echo htmlspecialchars($ime); ?></strong>!</p>

        <div class="stats">
            <div class="stat-card">
                <p>👥 Korisnika</p>
                <h3><?php echo $ukupno_korisnika; ?></h3>
            </div>
            <div class="stat-card">
                <p>🍎 Proizvoda</p>
                <h3><?php echo $ukupno_proizvoda; ?></h3>
            </div>
            <div class="stat-card">
                <p>📝 Unosa danas</p>
                <h3><?php echo $unosa_danas; ?></h3>
            </div>
            <div class="stat-card">
                <p>💬 Komentara</p>
                <h3><?php echo $ukupno_komentara; ?></h3>
            </div>
            <div class="stat-card">
                <p>⭐ Ocena</p>
                <h3><?php echo $ukupno_ocena; ?></h3>
            </div>
        </div>

        <div class="quick-actions">
            <h2>Brze akcije</h2>
            <div class="action-buttons">
                <a href="korisnici.php" class="action-btn">Upravljanje korisnicima</a>
                <a href="komentari.php" class="action-btn">Pregled komentara</a>
                <a href="statistika.php" class="action-btn">Detaljne statistike</a>
            </div>
        </div>

        <div style="text-align: center;">
            <a href="logout.php" class="logout-btn">Odjavi se</a>
        </div>
    </div>
</div>
</body>
</html>
