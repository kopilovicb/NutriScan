<?php
session_start();

// Provera admin pristupa
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: index.html');
    exit;
}

require_once __DIR__ . '/db.php';


$message = '';
$message_type = '';

// Obrada akcija
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = $_POST['user_id'] ?? 0;

    if ($action === 'delete' && $user_id > 0) {
        // Provera da admin ne briše samog sebe
        if ($user_id == $_SESSION['user_id']) {
            $message = 'Ne možete obrisati svoj nalog!';
            $message_type = 'error';
        } else {
            $stmt = $pdo->prepare("DELETE FROM korisnici WHERE id = :id");
            if ($stmt->execute(['id' => $user_id])) {
                // Log aktivnosti
                $stmt = $pdo->prepare("INSERT INTO log_aktivnosti (korisnik_id, akcija) VALUES (:korisnik_id, :akcija)");
                $stmt->execute([
                    'korisnik_id' => $_SESSION['user_id'],
                    'akcija' => "Admin obrisao korisnika ID: $user_id"
                ]);
                $message = 'Korisnik uspešno obrisan!';
                $message_type = 'success';
            }
        }
    }

    if ($action === 'ban' && $user_id > 0) {
        if ($user_id == $_SESSION['user_id']) {
            $message = 'Ne možete banati svoj nalog!';
            $message_type = 'error';
        } else {
            $stmt = $pdo->prepare("UPDATE korisnici SET je_banovan = 1 WHERE id = :id");
            if ($stmt->execute(['id' => $user_id])) {
                $stmt = $pdo->prepare("INSERT INTO log_aktivnosti (korisnik_id, akcija) VALUES (:korisnik_id, :akcija)");
                $stmt->execute([
                    'korisnik_id' => $_SESSION['user_id'],
                    'akcija' => "Admin banao korisnika ID: $user_id"
                ]);
                $message = 'Korisnik uspešno banovan!';
                $message_type = 'success';
            }
        }
    }

    if ($action === 'unban' && $user_id > 0) {
        $stmt = $pdo->prepare("UPDATE korisnici SET je_banovan = 0 WHERE id = :id");
        if ($stmt->execute(['id' => $user_id])) {
            $stmt = $pdo->prepare("INSERT INTO log_aktivnosti (korisnik_id, akcija) VALUES (:korisnik_id, :akcija)");
            $stmt->execute([
                'korisnik_id' => $_SESSION['user_id'],
                'akcija' => "Admin odbanao korisnika ID: $user_id"
            ]);
            $message = 'Korisnik uspešno odbanovan!';
            $message_type = 'success';
        }
    }
}

// Uzimanje svih korisnika
$stmt = $pdo->query("
    SELECT 
        k.id, 
        k.ime, 
        k.email, 
        k.datum_registracije, 
        k.je_admin,
        k.je_banovan,
        k.poslednja_prijava,
        COUNT(DISTINCT d.id) as broj_unosa,
        COUNT(DISTINCT o.id) as broj_ocena,
        COUNT(DISTINCT kom.id) as broj_komentara
    FROM korisnici k
    LEFT JOIN dnevnik_ishrane d ON k.id = d.korisnik_id
    LEFT JOIN ocene o ON k.id = o.korisnik_id
    LEFT JOIN komentari kom ON k.id = kom.korisnik_id
    GROUP BY k.id
    ORDER BY k.datum_registracije DESC
");
$korisnici = $stmt->fetchAll(PDO::FETCH_ASSOC);

$ime_admina = $_SESSION['ime'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upravljanje Korisnicima - NutriScan</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: #f5f5f5;
        }

        .nav {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav h1 {
            color: #06b6d4;
            font-size: 24px;
            margin: 0;
        }

        .nav-links a {
            margin-left: 20px;
            color: #666;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .nav-links a:hover {
            color: #06b6d4;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
        }

        .stat-box h3 {
            font-size: 32px;
            color: #06b6d4;
            margin: 10px 0;
        }

        .stat-box p {
            color: #666;
            font-size: 14px;
        }

        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .table-header {
            background: linear-gradient(135deg, #06b6d4 0%, #10b981 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-header h2 {
            margin: 0;
            font-size: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8f9fa;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e0e0e0;
            font-size: 14px;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge.admin {
            background: #ffd93d;
            color: #856404;
        }

        .badge.user {
            background: #e3f2fd;
            color: #1565c0;
        }

        .badge.banned {
            background: #ffebee;
            color: #c62828;
        }

        .badge.active {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            margin-right: 5px;
            transition: transform 0.2s, opacity 0.2s;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }

        .action-btn.delete {
            background: #ff4757;
            color: white;
        }

        .action-btn.ban {
            background: #ffa502;
            color: white;
        }

        .action-btn.unban {
            background: #10b981;
            color: white;
        }

        .status-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
        }

        .status-dot.online {
            background: #10b981;
        }

        .status-dot.offline {
            background: #e0e0e0;
        }

        .stats-mini {
            color: #999;
            font-size: 12px;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .search-box {
            padding: 20px;
            border-bottom: 1px solid #f0f0f0;
        }

        .search-box input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.3s;
        }

        .search-box input:focus {
            border-color: #06b6d4;
        }
    </style>
</head>
<body>
<div class="nav">
    <h1>🥗 NutriScan Admin</h1>
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="korisnici.php" style="color: #06b6d4;">Korisnici</a>
        <a href="statistika.php">Statistika</a>
        <a href="logout.php">Odjava</a>
    </div>
</div>

<div class="container">
    <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="stats-bar">
        <div class="stat-box">
            <p>👥 Ukupno korisnika</p>
            <h3><?php echo count($korisnici); ?></h3>
        </div>
        <div class="stat-box">
            <p>⚡ Aktivnih</p>
            <h3><?php echo count(array_filter($korisnici, fn($k) => !($k['je_banovan'] ?? 0))); ?></h3>
        </div>
        <div class="stat-box">
            <p>🚫 Banovanih</p>
            <h3><?php echo count(array_filter($korisnici, fn($k) => ($k['je_banovan'] ?? 0) == 1)); ?></h3>
        </div>
        <div class="stat-box">
            <p>👑 Admin naloga</p>
            <h3><?php echo count(array_filter($korisnici, fn($k) => $k['je_admin'] == 1)); ?></h3>
        </div>
    </div>

    <div class="table-container">
        <div class="table-header">
            <h2>Lista korisnika</h2>
        </div>

        <div class="search-box">
            <input type="text" id="searchInput" placeholder="🔍 Pretraži korisnike po imenu ili email-u..." onkeyup="searchTable()">
        </div>

        <?php if (empty($korisnici)): ?>
            <div class="no-data">Nema korisnika u bazi.</div>
        <?php else: ?>
            <table id="usersTable">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Korisnik</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Aktivnost</th>
                    <th>Registrovan</th>
                    <th>Poslednja prijava</th>
                    <th>Akcije</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($korisnici as $korisnik): ?>
                    <tr>
                        <td><strong>#<?php echo $korisnik['id']; ?></strong></td>
                        <td>
                            <div>
                                <?php if ($korisnik['je_banovan'] ?? 0): ?>
                                    <span class="status-dot offline"></span>
                                <?php else: ?>
                                    <span class="status-dot online"></span>
                                <?php endif; ?>
                                <strong><?php echo htmlspecialchars($korisnik['ime']); ?></strong>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($korisnik['email']); ?></td>
                        <td>
                            <?php if ($korisnik['je_admin'] == 1): ?>
                                <span class="badge admin">👑 Admin</span>
                            <?php else: ?>
                                <span class="badge user">👤 Korisnik</span>
                            <?php endif; ?>

                            <?php if ($korisnik['je_banovan'] ?? 0): ?>
                                <span class="badge banned">🚫 Banovan</span>
                            <?php else: ?>
                                <span class="badge active">✅ Aktivan</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="stats-mini">
                                📝 <?php echo $korisnik['broj_unosa']; ?> unosa |
                                ⭐ <?php echo $korisnik['broj_ocena']; ?> ocena |
                                💬 <?php echo $korisnik['broj_komentara']; ?> komentara
                            </div>
                        </td>
                        <td><?php echo date('d.m.Y', strtotime($korisnik['datum_registracije'])); ?></td>
                        <td>
                            <?php
                            if ($korisnik['poslednja_prijava']) {
                                $diff = time() - strtotime($korisnik['poslednja_prijava']);
                                if ($diff < 300) echo '🟢 Upravo sad';
                                elseif ($diff < 3600) echo '🟡 Pre ' . floor($diff/60) . ' min';
                                elseif ($diff < 86400) echo '🟠 Pre ' . floor($diff/3600) . ' h';
                                else echo '⚫ ' . date('d.m.Y', strtotime($korisnik['poslednja_prijava']));
                            } else {
                                echo '⚫ Nikad';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if ($korisnik['id'] != $_SESSION['user_id']): ?>
                                <?php if ($korisnik['je_banovan'] ?? 0): ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Da li ste sigurni da želite da odbanuјete ovog korisnika?');">
                                        <input type="hidden" name="action" value="unban">
                                        <input type="hidden" name="user_id" value="<?php echo $korisnik['id']; ?>">
                                        <button type="submit" class="action-btn unban">Odbanuј</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Da li ste sigurni da želite da banuјete ovog korisnika?');">
                                        <input type="hidden" name="action" value="ban">
                                        <input type="hidden" name="user_id" value="<?php echo $korisnik['id']; ?>">
                                        <button type="submit" class="action-btn ban">Banuј</button>
                                    </form>
                                <?php endif; ?>

                                <form method="POST" style="display: inline;" onsubmit="return confirm('Da li ste sigurni da želite da obrišete ovog korisnika? Svi njegovi podaci će biti obrisani!');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?php echo $korisnik['id']; ?>">
                                    <button type="submit" class="action-btn delete">Obriši</button>
                                </form>
                            <?php else: ?>
                                <span style="color: #999; font-size: 12px;">To si ti</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
    function searchTable() {
        const input = document.getElementById('searchInput');
        const filter = input.value.toUpperCase();
        const table = document.getElementById('usersTable');
        const tr = table.getElementsByTagName('tr');

        for (let i = 1; i < tr.length; i++) {
            const tdName = tr[i].getElementsByTagName('td')[1];
            const tdEmail = tr[i].getElementsByTagName('td')[2];

            if (tdName || tdEmail) {
                const nameValue = tdName.textContent || tdName.innerText;
                const emailValue = tdEmail.textContent || tdEmail.innerText;

                if (nameValue.toUpperCase().indexOf(filter) > -1 || emailValue.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = '';
                } else {
                    tr[i].style.display = 'none';
                }
            }
        }
    }
</script>
</body>
</html>