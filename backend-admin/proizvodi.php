<?php
session_start();

// Admin provera
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: index.html');
    exit;
}

// Database config
require_once __DIR__ . '/db.php';




// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';

    // DELETE
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM proizvodi WHERE id = :id");
            if ($stmt->execute(['id' => $id])) {
                // Log
                $stmt = $pdo->prepare("INSERT INTO log_aktivnosti (korisnik_id, akcija) VALUES (:kid, :akcija)");
                $stmt->execute(['kid' => $_SESSION['user_id'], 'akcija' => "Admin obrisao proizvod ID: $id"]);
                echo json_encode(['success' => true, 'message' => 'Proizvod uspešno obrisan!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Greška pri brisanju.']);
            }
        }
        exit;
    }

    // ADD/UPDATE
    if ($action === 'save') {
        $id = intval($_POST['id'] ?? 0);
        $naziv = trim($_POST['naziv'] ?? '');
        $kategorija = trim($_POST['kategorija'] ?? '');
        $opis = trim($_POST['opis'] ?? '');
        $kalorije = intval($_POST['kalorije'] ?? 0);
        $proteini = floatval($_POST['proteini'] ?? 0);
        $masti = floatval($_POST['masti'] ?? 0);
        $ugljeni_hidrati = floatval($_POST['ugljeni_hidrati'] ?? 0);

        // Validation
        if (empty($naziv)) {
            echo json_encode(['success' => false, 'message' => 'Naziv je obavezan!']);
            exit;
        }

        // Handle image upload
        $slika = '';
        if (isset($_FILES['slika']) && $_FILES['slika']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['slika']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed)) {
                $new_filename = uniqid() . '_' . time() . '.' . $ext;
                $upload_path = 'uploads/' . $new_filename;

                // Create uploads dir if doesn't exist
                if (!is_dir('uploads')) {
                    mkdir('uploads', 0777, true);
                }

                if (move_uploaded_file($_FILES['slika']['tmp_name'], $upload_path)) {
                    $slika = $new_filename;
                }
            }
        }

        try {
            if ($id > 0) {
                // UPDATE
                if ($slika) {
                    $stmt = $pdo->prepare("UPDATE proizvodi SET naziv=:naziv, slika=:slika, opis=:opis, kategorija=:kategorija, kalorije=:kalorije, proteini=:proteini, masti=:masti, ugljeni_hidrati=:ugljeni_hidrati WHERE id=:id");
                    $stmt->execute([
                        'id' => $id, 'naziv' => $naziv, 'slika' => $slika, 'opis' => $opis,
                        'kategorija' => $kategorija, 'kalorije' => $kalorije, 'proteini' => $proteini,
                        'masti' => $masti, 'ugljeni_hidrati' => $ugljeni_hidrati
                    ]);
                } else {
                    $stmt = $pdo->prepare("UPDATE proizvodi SET naziv=:naziv, opis=:opis, kategorija=:kategorija, kalorije=:kalorije, proteini=:proteini, masti=:masti, ugljeni_hidrati=:ugljeni_hidrati WHERE id=:id");
                    $stmt->execute([
                        'id' => $id, 'naziv' => $naziv, 'opis' => $opis, 'kategorija' => $kategorija,
                        'kalorije' => $kalorije, 'proteini' => $proteini, 'masti' => $masti,
                        'ugljeni_hidrati' => $ugljeni_hidrati
                    ]);
                }
                $msg = 'Proizvod uspešno ažuriran!';
                $log_action = "Admin ažurirao proizvod: $naziv (ID: $id)";
            } else {
                // INSERT
                $stmt = $pdo->prepare("INSERT INTO proizvodi (naziv, slika, opis, kategorija, kalorije, proteini, masti, ugljeni_hidrati) VALUES (:naziv, :slika, :opis, :kategorija, :kalorije, :proteini, :masti, :ugljeni_hidrati)");
                $stmt->execute([
                    'naziv' => $naziv, 'slika' => $slika, 'opis' => $opis, 'kategorija' => $kategorija,
                    'kalorije' => $kalorije, 'proteini' => $proteini, 'masti' => $masti,
                    'ugljeni_hidrati' => $ugljeni_hidrati
                ]);
                $msg = 'Proizvod uspešno dodat!';
                $log_action = "Admin dodao proizvod: $naziv";
            }

            // Log
            $stmt = $pdo->prepare("INSERT INTO log_aktivnosti (korisnik_id, akcija) VALUES (:kid, :akcija)");
            $stmt->execute(['kid' => $_SESSION['user_id'], 'akcija' => $log_action]);

            echo json_encode(['success' => true, 'message' => $msg]);
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Greška: ' . $e->getMessage()]);
        }
        exit;
    }
}

// Get all products
$stmt = $pdo->query("SELECT * FROM proizvodi ORDER BY datum_kreiranja DESC");
$proizvodi = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter
$stmt = $pdo->query("SELECT DISTINCT kategorija FROM proizvodi WHERE kategorija IS NOT NULL ORDER BY kategorija");
$kategorije = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upravljanje Proizvodima - NutriScan</title>
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

        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #06b6d4 0%, #10b981 100%);
            color: white;
        }

        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(6,182,212,0.3); }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: linear-gradient(135deg, #06b6d4 0%, #10b981 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
        }

        .product-body {
            padding: 20px;
        }

        .product-title {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .product-category {
            display: inline-block;
            padding: 4px 10px;
            background: #e3f2fd;
            color: #1565c0;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .product-desc {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .nutrition-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin: 15px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .nutrition-item {
            display: flex;
            align-items: center;
            font-size: 13px;
        }

        .nutrition-item strong {
            color: #06b6d4;
            margin-right: 5px;
        }

        .product-actions {
            display: flex;
            gap: 10px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
        }

        .btn-small {
            padding: 8px 16px;
            font-size: 13px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            flex: 1;
            transition: all 0.2s;
        }

        .btn-edit {
            background: #ffa502;
            color: white;
        }

        .btn-delete {
            background: #ff4757;
            color: white;
        }

        .btn-small:hover {
            transform: translateY(-2px);
            opacity: 0.9;
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
            animation: fadeIn 0.2s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: white;
            margin: 50px auto;
            padding: 0;
            border-radius: 15px;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
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

        .modal-header h2 {
            margin: 0;
            font-size: 22px;
        }

        .close {
            color: white;
            font-size: 32px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
            transition: transform 0.2s;
        }

        .close:hover {
            transform: scale(1.1);
        }

        .modal-body {
            padding: 25px;
        }

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

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #06b6d4;
            box-shadow: 0 0 0 3px rgba(6,182,212,0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .image-preview {
            width: 100%;
            height: 200px;
            border: 2px dashed #e0e0e0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-top: 10px;
            background: #f8f9fa;
        }

        .image-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }

        .image-preview-placeholder {
            color: #999;
            text-align: center;
        }

        /* Notification */
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
            animation: slideInRight 0.3s;
            display: none;
        }

        @keyframes slideInRight {
            from { transform: translateX(400px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .notification.success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .notification.error {
            background: linear-gradient(135deg, #ff4757 0%, #e84118 100%);
        }

        .filter-bar {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .filter-bar select {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="nav">
    <h1>🥗 NutriScan Admin</h1>
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="korisnici.php">Korisnici</a>
        <a href="proizvodi.php" style="color: #06b6d4;">Proizvodi</a>
        <a href="komentari.php">Komentari</a>
        <a href="statistika.php">Statistike</a>
        <a href="logout.php">Odjava</a>
    </div>
</div>

<div class="container">
    <div class="header-bar">
        <div>
            <h2 style="margin:0; color:#333;">Proizvodi</h2>
            <p style="color:#666; margin-top:5px;">Upravljajte proizvodima u bazi</p>
        </div>
        <button class="btn btn-primary" onclick="openModal()">+ Dodaj Proizvod</button>
    </div>

    <div class="filter-bar">
        <label style="font-weight:600; margin-right:10px;">Filter po kategoriji:</label>
        <select id="categoryFilter" onchange="filterProducts()">
            <option value="">Sve kategorije</option>
            <?php foreach($kategorije as $kat): ?>
                <option value="<?php echo htmlspecialchars($kat); ?>"><?php echo htmlspecialchars($kat); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="products-grid" id="productsGrid">
        <?php if(empty($proizvodi)): ?>
            <div class="empty-state" style="grid-column: 1/-1;">
                <div class="empty-state-icon">📦</div>
                <h3>Nema proizvoda</h3>
                <p>Dodajte prvi proizvod klikom na dugme iznad</p>
            </div>
        <?php else: ?>
            <?php foreach($proizvodi as $p): ?>
                <div class="product-card" data-category="<?php echo htmlspecialchars($p['kategorija'] ?? ''); ?>">
                    <div class="product-image">
                        <?php if($p['slika'] && file_exists('uploads/' . $p['slika'])): ?>
                            <img src="uploads/<?php echo htmlspecialchars($p['slika']); ?>" alt="<?php echo htmlspecialchars($p['naziv']); ?>" style="width:100%; height:100%; object-fit:cover;">
                        <?php else: ?>
                            🍽️
                        <?php endif; ?>
                    </div>
                    <div class="product-body">
                        <h3 class="product-title"><?php echo htmlspecialchars($p['naziv']); ?></h3>
                        <?php if($p['kategorija']): ?>
                            <span class="product-category"><?php echo htmlspecialchars($p['kategorija']); ?></span>
                        <?php endif; ?>
                        <?php if($p['opis']): ?>
                            <p class="product-desc"><?php echo htmlspecialchars($p['opis']); ?></p>
                        <?php endif; ?>
                        <div class="nutrition-info">
                            <div class="nutrition-item">
                                <strong>🔥</strong> <?php echo $p['kalorije']; ?> kcal
                            </div>
                            <div class="nutrition-item">
                                <strong>💪</strong> <?php echo $p['proteini']; ?>g prot.
                            </div>
                            <div class="nutrition-item">
                                <strong>🧈</strong> <?php echo $p['masti']; ?>g masti
                            </div>
                            <div class="nutrition-item">
                                <strong>🍞</strong> <?php echo $p['ugljeni_hidrati']; ?>g UH
                            </div>
                        </div>
                        <div class="product-actions">
                            <button class="btn-small btn-edit" onclick='editProduct(<?php echo json_encode($p); ?>)'>✏️ Izmeni</button>
                            <button class="btn-small btn-delete" onclick="deleteProduct(<?php echo $p['id']; ?>)">🗑️ Obriši</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal -->
<div id="productModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Dodaj Proizvod</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="productForm" enctype="multipart/form-data">
                <input type="hidden" name="ajax" value="1">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" id="productId" value="0">

                <div class="form-group">
                    <label>Naziv proizvoda *</label>
                    <input type="text" name="naziv" id="naziv" required placeholder="npr. Jabuka">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Kategorija</label>
                        <select name="kategorija" id="kategorija">
                            <option value="">Bez kategorije</option>
                            <option value="Voće">Voće</option>
                            <option value="Povrće">Povrće</option>
                            <option value="Meso">Meso</option>
                            <option value="Mlečni proizvodi">Mlečni proizvodi</option>
                            <option value="Pecivo">Pecivo</option>
                            <option value="Žitarice">Žitarice</option>
                            <option value="Slatkiši">Slatkiši</option>
                            <option value="Piće">Piće</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Kalorije (kcal) *</label>
                        <input type="number" name="kalorije" id="kalorije" required placeholder="52">
                    </div>
                </div>

                <div class="form-group">
                    <label>Opis</label>
                    <textarea name="opis" id="opis" placeholder="Kratak opis proizvoda..."></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Proteini (g)</label>
                        <input type="number" step="0.01" name="proteini" id="proteini" placeholder="0.30">
                    </div>
                    <div class="form-group">
                        <label>Masti (g)</label>
                        <input type="number" step="0.01" name="masti" id="masti" placeholder="0.20">
                    </div>
                </div>

                <div class="form-group">
                    <label>Ugljeni hidrati (g)</label>
                    <input type="number" step="0.01" name="ugljeni_hidrati" id="ugljeni_hidrati" placeholder="14.00">
                </div>

                <div class="form-group">
                    <label>Slika proizvoda</label>
                    <input type="file" name="slika" id="slika" accept="image/*" onchange="previewImage(event)">
                    <div class="image-preview" id="imagePreview">
                        <div class="image-preview-placeholder">📷 Izaberite sliku</div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%; margin-top:10px;">💾 Sačuvaj</button>
            </form>
        </div>
    </div>
</div>

<!-- Notification -->
<div id="notification" class="notification"></div>

<script>
    const modal = document.getElementById('productModal');
    const form = document.getElementById('productForm');

    function openModal() {
        document.getElementById('modalTitle').textContent = 'Dodaj Proizvod';
        form.reset();
        document.getElementById('productId').value = '0';
        document.getElementById('imagePreview').innerHTML = '<div class="image-preview-placeholder">📷 Izaberite sliku</div>';
        modal.style.display = 'block';
    }

    function closeModal() {
        modal.style.display = 'none';
    }

    function editProduct(product) {
        document.getElementById('modalTitle').textContent = 'Izmeni Proizvod';
        document.getElementById('productId').value = product.id;
        document.getElementById('naziv').value = product.naziv;
        document.getElementById('kategorija').value = product.kategorija || '';
        document.getElementById('opis').value = product.opis || '';
        document.getElementById('kalorije').value = product.kalorije;
        document.getElementById('proteini').value = product.proteini;
        document.getElementById('masti').value = product.masti;
        document.getElementById('ugljeni_hidrati').value = product.ugljeni_hidrati;

        if (product.slika) {
            document.getElementById('imagePreview').innerHTML = '<img src="uploads/' + product.slika + '" alt="Preview">';
        }

        modal.style.display = 'block';
    }

    function previewImage(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('imagePreview').innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
            }
            reader.readAsDataURL(file);
        }
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

        fetch('proizvodi.php', {
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
            })
            .catch(error => {
                showNotification('Greška: ' + error, 'error');
            });
    });

    function deleteProduct(id) {
        if (!confirm('Da li ste sigurni da želite da obrišete ovaj proizvod?')) return;

        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'delete');
        formData.append('id', id);

        fetch('proizvodi.php', {
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

    function filterProducts() {
        const filter = document.getElementById('categoryFilter').value.toLowerCase();
        const cards = document.querySelectorAll('.product-card');

        cards.forEach(card => {
            const category = card.getAttribute('data-category').toLowerCase();
            if (filter === '' || category === filter) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    // Close modal on outside click
    window.onclick = function(event) {
        if (event.target == modal) {
            closeModal();
        }
    }
</script>
</body>
</html>