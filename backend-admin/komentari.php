<?php
session_start();

// Admin check
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: index.html');
    exit;
}

require_once __DIR__ . '/db.php';




// AJAX Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';

    // DELETE
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM komentari WHERE id = :id");
            if ($stmt->execute(['id' => $id])) {
                $stmt = $pdo->prepare("INSERT INTO log_aktivnosti (korisnik_id, akcija) VALUES (:kid, :akcija)");
                $stmt->execute(['kid' => $_SESSION['user_id'], 'akcija' => "Admin obrisao komentar ID: $id"]);
                echo json_encode(['success' => true, 'message' => 'Komentar uspešno obrisan!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Greška pri brisanju.']);
            }
        }
        exit;
    }

    // ADD
    if ($action === 'add') {
        $korisnik_id = intval($_POST['korisnik_id'] ?? 0);
        $proizvod_id = intval($_POST['proizvod_id'] ?? 0);
        $tekst = trim($_POST['tekst'] ?? '');

        if ($korisnik_id === 0 || $proizvod_id === 0 || empty($tekst)) {
            echo json_encode(['success' => false, 'message' => 'Sva polja su obavezna!']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO komentari (korisnik_id, proizvod_id, tekst) VALUES (:kid, :pid, :tekst)");
            $stmt->execute(['kid' => $korisnik_id, 'pid' => $proizvod_id, 'tekst' => $tekst]);

            $stmt = $pdo->prepare("INSERT INTO log_aktivnosti (korisnik_id, akcija) VALUES (:kid, :akcija)");
            $stmt->execute(['kid' => $_SESSION['user_id'], 'akcija' => "Admin dodao komentar"]);

            echo json_encode(['success' => true, 'message' => 'Komentar uspešno dodat!']);
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Greška: ' . $e->getMessage()]);
        }
        exit;
    }
}

// Get all comments with user and product info
$stmt = $pdo->query("
    SELECT 
        k.id,
        k.tekst,
        k.datum,
        k.korisnik_id,
        k.proizvod_id,
        kor.ime AS korisnik_ime,
        kor.email AS korisnik_email,
        p.naziv AS proizvod_naziv
    FROM komentari k
    JOIN korisnici kor ON k.korisnik_id = kor.id
    JOIN proizvodi p ON k.proizvod_id = p.id
    ORDER BY k.datum DESC
");
$komentari = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Get users for dropdown
$users = $pdo->query("SELECT id, ime, email FROM korisnici ORDER BY ime")->fetchAll(PDO::FETCH_ASSOC);

// Get products for dropdown
$products = $pdo->query("SELECT id, naziv FROM proizvodi ORDER BY naziv")->fetchAll(PDO::FETCH_ASSOC);

// Stats
$total_komentari = count($komentari);
$stmt = $pdo->query("SELECT COUNT(DISTINCT korisnik_id) FROM komentari");
$aktivni_korisnici = $stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(DISTINCT proizvod_id) FROM komentari");
$komentarisani_proizvodi = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Komentari - NutriScan</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { background: #f5f5f5; }

        .nav {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav h1 { color: #06b6d4; font-size: 24px; margin: 0; }
        .nav-links a { margin-left: 20px; color: #666; text-decoration: none; font-weight: 500; }
        .nav-links a:hover { color: #06b6d4; }

        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }

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

        .stat-box h3 { font-size: 32px; color: #06b6d4; margin: 10px 0; }
        .stat-box p { color: #666; font-size: 14px; }

        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #06b6d4 0%, #10b981 100%);
            color: white;
        }

        .btn-primary:hover { transform: translateY(-2px); }

        .comments-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .comment-item {
            padding: 20px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            gap: 20px;
            transition: background 0.2s;
        }

        .comment-item:hover {
            background: #f8f9fa;
        }

        .comment-product-thumb {
            width: 80px;
            height: 80px;
            border-radius: 10px;
            object-fit: cover;
            background: linear-gradient(135deg, #06b6d4 0%, #10b981 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            flex-shrink: 0;
        }

        .comment-content {
            flex: 1;
        }

        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }

        .comment-user {
            font-weight: 600;
            color: #333;
            font-size: 15px;
        }

        .comment-product {
            color: #06b6d4;
            font-size: 13px;
            font-weight: 500;
        }

        .comment-date {
            color: #999;
            font-size: 12px;
        }

        .comment-text {
            color: #555;
            line-height: 1.6;
            margin: 10px 0;
        }

        .comment-actions {
            margin-top: 10px;
        }

        .btn-small {
            padding: 6px 14px;
            font-size: 12px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-delete {
            background: #ff4757;
            color: white;
        }

        .btn-delete:hover {
            background: #e84118;
            transform: translateY(-2px);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
        }

        .modal-content {
            background: white;
            margin: 80px auto;
            padding: 0;
            border-radius: 15px;
            max-width: 600px;
            animation: slideDown 0.3s;
        }

        @keyframes slideDown {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            background: linear-gradient(135deg, #06b6d4 0%, #10b981 100%);
            color: white;
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 15px 15px 0 0;
        }

        .modal-header h2 { margin: 0; font-size: 22px; }

        .close {
            color: white;
            font-size: 32px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }

        .close:hover { transform: scale(1.1); }

        .modal-body { padding: 25px; }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
        }

        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #06b6d4;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            z-index: 2000;
            display: none;
        }

        .notification.success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .notification.error { background: linear-gradient(135deg, #ff4757 0%, #e84118 100%); }

        .filter-bar {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .filter-bar select {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
    </style>
</head>
<body>
<div class="nav">
    <h1>🥗 NutriScan Admin</h1>
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="korisnici.php">Korisnici</a>
        <a href="komentari.php" style="color: #06b6d4;">Komentari</a>
        <a href="statistika.php">Statistike</a>
        <a href="logout.php">Odjava</a>
    </div>
</div>

<div class="container">
    <div class="stats-bar">
        <div class="stat-box">
            <p>💬 Ukupno komentara</p>
            <h3><?php echo $total_komentari; ?></h3>
        </div>
        <div class="stat-box">
            <p>👥 Aktivnih korisnika</p>
            <h3><?php echo $aktivni_korisnici; ?></h3>
        </div>
        <div class="stat-box">
            <p>🍎 Komentarisanih proizvoda</p>
            <h3><?php echo $komentarisani_proizvodi; ?></h3>
        </div>
    </div>

    <div class="header-bar">
        <div>
            <h2 style="margin:0; color:#333;">Komentari</h2>
            <p style="color:#666; margin-top:5px;">Moderacija komentara</p>
        </div>
        <button class="btn btn-primary" onclick="openModal()">+ Dodaj Komentar</button>
    </div>

    <div class="filter-bar">
        <label style="font-weight:600;">Filter:</label>
        <select id="productFilter" onchange="filterComments()">
            <option value="">Svi proizvodi</option>
            <?php foreach($products as $prod): ?>
                <option value="<?php echo $prod['id']; ?>"><?php echo htmlspecialchars($prod['naziv']); ?></option>
            <?php endforeach; ?>
        </select>
        <select id="userFilter" onchange="filterComments()">
            <option value="">Svi korisnici</option>
            <?php foreach($users as $user): ?>
                <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['ime']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="comments-container">
        <?php if(empty($komentari)): ?>
            <div class="empty-state">
                <div style="font-size:64px; margin-bottom:20px;">💬</div>
                <h3>Nema komentara</h3>
                <p>Dodajte prvi komentar</p>
            </div>
        <?php else: ?>
            <?php foreach($komentari as $kom): ?>
                <div class="comment-item" data-product="<?php echo $kom['proizvod_id']; ?>" data-user="<?php echo $kom['korisnik_id']; ?>">

                    <div class="comment-content">
                        <div class="comment-header">
                            <div>
                                <div class="comment-user">👤 <?php echo htmlspecialchars($kom['korisnik_ime']); ?></div>
                                <div class="comment-product">📦 <?php echo htmlspecialchars($kom['proizvod_naziv']); ?></div>
                            </div>
                            <div class="comment-date">
                                <?php echo date('d.m.Y H:i', strtotime($kom['datum'])); ?>
                            </div>
                        </div>
                        <div class="comment-text">
                            "<?php echo htmlspecialchars($kom['tekst']); ?>"
                        </div>
                        <div class="comment-actions">
                            <button class="btn-small btn-delete" onclick="deleteComment(<?php echo $kom['id']; ?>)">🗑️ Obriši</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal -->
<div id="commentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Dodaj Komentar</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="commentForm">
                <input type="hidden" name="ajax" value="1">
                <input type="hidden" name="action" value="add">

                <div class="form-group">
                    <label>Korisnik *</label>
                    <select name="korisnik_id" required>
                        <option value="">Izaberite korisnika</option>
                        <?php foreach($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['ime']); ?> (<?php echo htmlspecialchars($user['email']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Proizvod *</label>
                    <select name="proizvod_id" required>
                        <option value="">Izaberite proizvod</option>
                        <?php foreach($products as $prod): ?>
                            <option value="<?php echo $prod['id']; ?>"><?php echo htmlspecialchars($prod['naziv']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Tekst komentara *</label>
                    <textarea name="tekst" required placeholder="Unesite komentar..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;">💾 Dodaj Komentar</button>
            </form>
        </div>
    </div>
</div>

<!-- Notification -->
<div id="notification" class="notification"></div>

<script>
    const modal = document.getElementById('commentModal');
    const form = document.getElementById('commentForm');

    function openModal() {
        form.reset();
        modal.style.display = 'block';
    }

    function closeModal() {
        modal.style.display = 'none';
    }

    function showNotification(message, type) {
        const notif = document.getElementById('notification');
        notif.textContent = message;
        notif.className = 'notification ' + type;
        notif.style.display = 'block';

        setTimeout(() => {
            notif.style.display = 'none';
        }, 3000);
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(form);

        fetch('komentari.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    closeModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(data.message, 'error');
                }
            });
    });

    function deleteComment(id) {
        if (!confirm('Da li ste sigurni da želite da obrišete ovaj komentar?')) return;

        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'delete');
        formData.append('id', id);

        fetch('komentari.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(data.message, 'error');
                }
            });
    }

    function filterComments() {
        const productFilter = document.getElementById('productFilter').value;
        const userFilter = document.getElementById('userFilter').value;
        const items = document.querySelectorAll('.comment-item');

        items.forEach(item => {
            const productId = item.getAttribute('data-product');
            const userId = item.getAttribute('data-user');

            const productMatch = !productFilter || productId === productFilter;
            const userMatch = !userFilter || userId === userFilter;

            if (productMatch && userMatch) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            closeModal();
        }
    }
</script>
</body>
</html>