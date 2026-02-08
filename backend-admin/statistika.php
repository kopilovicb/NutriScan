<?php
session_start();

// Admin check
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: index.html');
    exit;
}
require_once __DIR__ . '/db.php';


// Top 10 kalorični proizvodi
$stmt = $pdo->query("SELECT naziv, kalorije FROM proizvodi ORDER BY kalorije DESC LIMIT 10");
$top_kalorije = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Makronutrijentna distribucija (suma svih proizvoda)
$stmt = $pdo->query("SELECT SUM(proteini) as proteini, SUM(masti) as masti, SUM(ugljeni_hidrati) as uh FROM proizvodi");
$makro = $stmt->fetch(PDO::FETCH_ASSOC);

// Nedeljni trend unosa
$stmt = $pdo->query("
    SELECT 
        DATE(datum_unosa) as dan,
        COUNT(*) as broj_unosa,
        SUM(kolicina_grama) as ukupna_kolicina
    FROM dnevnik_ishrane 
    WHERE datum_unosa >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(datum_unosa)
    ORDER BY dan
");
$nedeljni_trend = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top 5 korisnika po broju unosa
$stmt = $pdo->query("
    SELECT 
        k.ime,
        COUNT(d.id) as broj_unosa,
        COUNT(DISTINCT d.proizvod_id) as razlicitih_proizvoda
    FROM korisnici k
    JOIN dnevnik_ishrane d ON k.id = d.korisnik_id
    GROUP BY k.id
    ORDER BY broj_unosa DESC
    LIMIT 5
");
$top_korisnici = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top ocenjeni proizvodi (prosečna ocena)
$stmt = $pdo->query("
    SELECT 
        p.naziv,
        AVG(o.ocena) as prosecna_ocena,
        COUNT(o.id) as broj_ocena
    FROM proizvodi p
    JOIN ocene o ON p.id = o.proizvod_id
    GROUP BY p.id
    HAVING broj_ocena >= 1
    ORDER BY prosecna_ocena DESC, broj_ocena DESC
    LIMIT 5
");
$top_ocenjeni = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistika po kategorijama
$stmt = $pdo->query("
    SELECT 
        kategorija,
        COUNT(*) as broj_proizvoda,
        AVG(kalorije) as prosecne_kalorije,
        AVG(proteini) as prosecan_protein
    FROM proizvodi
    WHERE kategorija IS NOT NULL
    GROUP BY kategorija
    ORDER BY broj_proizvoda DESC
");
$kategorije_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prosečan dnevni unos
$stmt = $pdo->query("
    SELECT 
        AVG(dnevni_unos) as prosek
    FROM (
        SELECT 
            datum_unosa,
            COUNT(*) as dnevni_unos
        FROM dnevnik_ishrane
        GROUP BY datum_unosa
    ) as dnevni
");
$prosecan_dnevni_unos = round($stmt->fetchColumn(), 1);

// Ukupne metrike
$stmt = $pdo->query("SELECT COUNT(*) FROM proizvodi");
$ukupno_proizvoda = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM korisnici");
$ukupno_korisnika = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM dnevnik_ishrane");
$ukupno_unosa = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM komentari");
$ukupno_komentara = $stmt->fetchColumn();

// Engagement rate (% korisnika koji su aktivni)
$stmt = $pdo->query("SELECT COUNT(DISTINCT korisnik_id) FROM dnevnik_ishrane");
$aktivni_korisnici = $stmt->fetchColumn();
$engagement_rate = $ukupno_korisnika > 0 ? round(($aktivni_korisnici / $ukupno_korisnika) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistike - NutriScan</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
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

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h2 {
            color: #333;
            margin: 0 0 5px 0;
            font-size: 28px;
        }

        .page-header p {
            color: #666;
            margin: 0;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .metric-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #06b6d4 0%, #10b981 100%);
        }

        .metric-icon {
            font-size: 36px;
            margin-bottom: 10px;
        }

        .metric-value {
            font-size: 32px;
            font-weight: 700;
            color: #06b6d4;
            margin: 10px 0;
        }

        .metric-label {
            color: #666;
            font-size: 14px;
            font-weight: 500;
        }

        .metric-sublabel {
            color: #999;
            font-size: 12px;
            margin-top: 5px;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .chart-card h3 {
            color: #333;
            margin: 0 0 20px 0;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        .table-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 25px;
        }

        .table-card h3 {
            background: linear-gradient(135deg, #06b6d4 0%, #10b981 100%);
            color: white;
            padding: 20px 25px;
            margin: 0;
            font-size: 18px;
        }

        .table-content {
            padding: 20px 25px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8f9fa;
        }

        th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #555;
            font-size: 13px;
            border-bottom: 2px solid #e0e0e0;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
            color: #666;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .rank-badge {
            display: inline-block;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            text-align: center;
            line-height: 28px;
            font-weight: 700;
            font-size: 13px;
        }

        .rank-1 { background: #ffd700; color: #856404; }
        .rank-2 { background: #c0c0c0; color: #495057; }
        .rank-3 { background: #cd7f32; color: #fff; }
        .rank-other { background: #e3f2fd; color: #1565c0; }

        .progress-bar {
            height: 8px;
            background: #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 5px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #06b6d4 0%, #10b981 100%);
            border-radius: 10px;
            transition: width 0.3s;
        }

        .star-rating {
            color: #ffa502;
            font-size: 16px;
        }

        .category-badge {
            display: inline-block;
            padding: 4px 10px;
            background: #e3f2fd;
            color: #1565c0;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
    </style>
</head>
<body>
<div class="nav">
    <h1>🥗 NutriScan Admin</h1>
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="korisnici.php">Korisnici</a>
        <a href="komentari.php">Komentari</a>
        <a href="statistika.php" style="color: #06b6d4;">Statistike</a>
        <a href="logout.php">Odjava</a>
    </div>
</div>

<div class="container">
    <div class="page-header">
        <h2>📊 Napredne Statistike</h2>
        <p>Detaljni pregled performansi i engagement-a</p>
    </div>

    <!-- Key Metrics -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-icon">📦</div>
            <div class="metric-value"><?php echo $ukupno_proizvoda; ?></div>
            <div class="metric-label">Ukupno proizvoda</div>
        </div>

        <div class="metric-card">
            <div class="metric-icon">👥</div>
            <div class="metric-value"><?php echo $ukupno_korisnika; ?></div>
            <div class="metric-label">Registrovanih korisnika</div>
            <div class="metric-sublabel"><?php echo $aktivni_korisnici; ?> aktivnih</div>
        </div>

        <div class="metric-card">
            <div class="metric-icon">📝</div>
            <div class="metric-value"><?php echo $ukupno_unosa; ?></div>
            <div class="metric-label">Unosa u dnevnik</div>
            <div class="metric-sublabel">Prosek: <?php echo $prosecan_dnevni_unos; ?> po danu</div>
        </div>

        <div class="metric-card">
            <div class="metric-icon">📈</div>
            <div class="metric-value"><?php echo $engagement_rate; ?>%</div>
            <div class="metric-label">Engagement Rate</div>
            <div class="metric-sublabel">Aktivnih korisnika</div>
        </div>
    </div>

    <!-- Charts -->
    <div class="charts-grid">
        <div class="chart-card">
            <h3>🔥 Top 10 Kalorični Proizvodi</h3>
            <div class="chart-container">
                <canvas id="kalorieChart"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <h3>🥗 Makronutrijentna Distribucija</h3>
            <div class="chart-container">
                <canvas id="macroChart"></canvas>
            </div>
        </div>

        <div class="chart-card" style="grid-column: 1 / -1;">
            <h3>📅 Nedeljni Trend Unosa</h3>
            <div class="chart-container">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Top Users Table -->
    <div class="table-card">
        <h3>🏆 Top 5 Najaktivnijih Korisnika</h3>
        <div class="table-content">
            <?php if(empty($top_korisnici)): ?>
                <p style="text-align:center; color:#999;">Nema podataka</p>
            <?php else: ?>
                <table>
                    <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Korisnik</th>
                        <th>Broj Unosa</th>
                        <th>Različitih Proizvoda</th>
                        <th>Aktivnost</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach($top_korisnici as $index => $kor): ?>
                        <tr>
                            <td>
                                <?php
                                $rank_class = $index < 3 ? "rank-" . ($index + 1) : "rank-other";
                                echo "<span class='rank-badge $rank_class'>" . ($index + 1) . "</span>";
                                ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($kor['ime']); ?></strong></td>
                            <td><?php echo $kor['broj_unosa']; ?> unosa</td>
                            <td><?php echo $kor['razlicitih_proizvoda']; ?> proizvoda</td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo min(($kor['broj_unosa'] / max(array_column($top_korisnici, 'broj_unosa'))) * 100, 100); ?>%;"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Top Rated Products -->
    <div class="table-card">
        <h3>⭐ Top Ocenjeni Proizvodi</h3>
        <div class="table-content">
            <?php if(empty($top_ocenjeni)): ?>
                <p style="text-align:center; color:#999;">Nema podataka</p>
            <?php else: ?>
                <table>
                    <thead>
                    <tr>
                        <th>Proizvod</th>
                        <th>Prosečna Ocena</th>
                        <th>Broj Ocena</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach($top_ocenjeni as $ocena): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($ocena['naziv']); ?></strong></td>
                            <td>
                                <span class="star-rating">
                                    <?php
                                    $stars = round($ocena['prosecna_ocena']);
                                    echo str_repeat('⭐', $stars);
                                    echo " " . number_format($ocena['prosecna_ocena'], 1);
                                    ?>
                                </span>
                            </td>
                            <td><?php echo $ocena['broj_ocena']; ?> glasova</td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Categories Stats -->
    <div class="table-card">
        <h3>📂 Statistika po Kategorijama</h3>
        <div class="table-content">
            <?php if(empty($kategorije_stats)): ?>
                <p style="text-align:center; color:#999;">Nema podataka</p>
            <?php else: ?>
                <table>
                    <thead>
                    <tr>
                        <th>Kategorija</th>
                        <th>Broj Proizvoda</th>
                        <th>Prosečne Kalorije</th>
                        <th>Prosečan Protein</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach($kategorije_stats as $kat): ?>
                        <tr>
                            <td><span class="category-badge"><?php echo htmlspecialchars($kat['kategorija']); ?></span></td>
                            <td><?php echo $kat['broj_proizvoda']; ?> proizvoda</td>
                            <td><?php echo round($kat['prosecne_kalorije']); ?> kcal</td>
                            <td><?php echo round($kat['prosecan_protein'], 1); ?>g</td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Chart.js configuration
    Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";

    // Top Kalorični Proizvodi Chart
    const kalorieData = <?php echo json_encode(array_column($top_kalorije, 'kalorije')); ?>;
    const kalorieLabels = <?php echo json_encode(array_column($top_kalorije, 'naziv')); ?>;

    new Chart(document.getElementById('kalorieChart'), {
        type: 'bar',
        data: {
            labels: kalorieLabels,
            datasets: [{
                label: 'Kalorije (kcal)',
                data: kalorieData,
                backgroundColor: 'rgba(6, 182, 212, 0.8)',
                borderColor: 'rgba(6, 182, 212, 1)',
                borderWidth: 2,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // Makronutrijenti Pie Chart
    new Chart(document.getElementById('macroChart'), {
        type: 'doughnut',
        data: {
            labels: ['Proteini', 'Masti', 'Ugljeni Hidrati'],
            datasets: [{
                data: [
                    <?php echo $makro['proteini'] * 4; ?>,
                    <?php echo $makro['masti'] * 9; ?>,
                    <?php echo $makro['uh'] * 4; ?>
                ],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 15, font: { size: 13 } }
                }
            }
        }
    });

    // Nedeljni Trend Line Chart
    const trendLabels = <?php echo json_encode(array_map(function($d) { return date('d.m', strtotime($d['dan'])); }, $nedeljni_trend)); ?>;
    const trendData = <?php echo json_encode(array_column($nedeljni_trend, 'broj_unosa')); ?>;

    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: trendLabels,
            datasets: [{
                label: 'Broj unosa',
                data: trendData,
                borderColor: 'rgba(6, 182, 212, 1)',
                backgroundColor: 'rgba(6, 182, 212, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: 'rgba(6, 182, 212, 1)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
</script>
</body>
</html>