<?php
/**
 * HALAMAN ANALISA DATA MENGGUNAKAN ALGORITMA APRIORI
 * 
 * Halaman ini menampilkan interface untuk analisis data mining
 * menggunakan algoritma Apriori untuk menemukan pola transaksi
 */

session_start();

// Debug session untuk troubleshooting
if (isset($_GET['debug'])) {
    echo "<h3>Debug Session Info:</h3>";
    echo "<p>Session ID: " . session_id() . "</p>";
    echo "<p>Session Data:</p><pre>";
    print_r($_SESSION);
    echo "</pre>";
    if (!isset($_SESSION['userid'])) {
        echo "<p style='color: red;'>❌ User belum login! Silakan login terlebih dahulu.</p>";
        echo "<p><a href='index.php'>Login di sini</a></p>";
        exit();
    } else {
        echo "<p style='color: green;'>✅ User sudah login dengan ID: " . $_SESSION['userid'] . "</p>";
    }
}

if (!isset($_SESSION['userid'])) {
    header("Location: index.php");
    exit();
}

require_once 'db/configdb.php';
require_once 'func/apriori.php';

// Buat koneksi PDO dari konfigurasi database
try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Koneksi PDO gagal: " . $e->getMessage());
}



// ===== PROCESSING FORM INPUT =====

// Inisialisasi variabel
$analysis = null;
$error = null;
$analysisResult = null;
$startPeriod = $_POST['start_period'] ?? null;
$endPeriod = $_POST['end_period'] ?? null;
$minSupport = (float)($_POST['min_support'] ?? 30) / 100; // Convert percentage to decimal
$minConfidence = (float)($_POST['min_confidence'] ?? 60) / 100; // Convert percentage to decimal
$maxItemsetSize = (int)($_POST['max_itemset_size'] ?? 3); // Maximum itemset size

// Jalankan analisis jika form di-submit
$analysisResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validasi input
        if ($minSupport < 0.01 || $minSupport > 1) {
            throw new Exception("Minimum Support harus antara 1% - 100%");
        }
        
        if ($minConfidence < 0.01 || $minConfidence > 1) {
            throw new Exception("Minimum Confidence harus antara 1% - 100%");
        }
        
        // Validasi ukuran itemset maksimum
        if ($maxItemsetSize < 2 || $maxItemsetSize > 5) {
            throw new Exception("Ukuran itemset maksimum harus antara 2 - 5");
        }
        
        // Validasi periode
        if ($startPeriod && $endPeriod && $startPeriod > $endPeriod) {
            throw new Exception("Periode mulai tidak boleh lebih besar dari periode akhir");
        }
        
        // Jalankan analisis Apriori
        $apriori = new AprioriAnalysis($pdo, $minSupport, $minConfidence);
        $analysisResult = $apriori->runApriori($startPeriod, $endPeriod, $maxItemsetSize);
        
        // Tambahkan min_support_count ke hasil
        $analysisResult['min_support_count'] = ceil($minSupport * $analysisResult['total_transactions']);
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        $analysisResult = null;
    }
}

// Ambil opsi periode untuk dropdown
try {
    $tempApriori = new AprioriAnalysis($pdo, 0.1, 0.5); // Temporary instance untuk getPeriodOptions
    $periodOptions = $tempApriori->getPeriodOptions();
} catch (Exception $e) {
    $periodOptions = [];
    error_log("Error getting period options: " . $e->getMessage());
}
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Analisa Data Apriori - Sistem Penjualan</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    
    <style>
        .analysis-card {
            border-left: 4px solid #007bff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .metric-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
        }
        .rule-item {
            border-left: 3px solid #28a745;
            background-color: #f8f9fa;
            margin-bottom: 10px;
            padding: 15px;
            border-radius: 5px;
        }
        .confidence-high { border-left-color: #28a745; }
        .confidence-medium { border-left-color: #ffc107; }
        .confidence-low { border-left-color: #dc3545; }
        
        .itemset-badge {
            background-color: #e9ecef;
            color: #495057;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
            margin: 2px;
            display: inline-block;
        }
        
        /* Chart Styling */
        .chart-container {
            position: relative;
            height: 500px;
            margin-bottom: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .chart-container.large {
            height: 600px;
        }
        .chart-title {
            text-align: center;
            margin-bottom: 20px;
            font-size: 1.2em;
            font-weight: 600;
            color: #495057;
        }
        .charts-section {
            background: #f8f9fa;
            padding: 30px 0;
            margin: 30px 0;
            border-radius: 10px;
        }
        .chart-row {
            margin-bottom: 40px;
        }
        .chart-description {
            text-align: center;
            color: #6c757d;
            font-size: 0.9em;
            margin-top: 10px;
            font-style: italic;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'navbar.php'; ?>
            
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-chart-line"></i> Analisa Data Apriori</h1>
                </div>
                
                <!-- Error Alert -->
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Error:</strong> <?= htmlspecialchars($error) ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php endif; ?>
                
                <!-- Info Panel -->
                <div class="alert alert-info" role="alert">
                    <h5><i class="fas fa-info-circle"></i> Tentang Algoritma Apriori</h5>
                    <p class="mb-2"><strong>Algoritma Apriori</strong> adalah teknik data mining untuk menemukan pola hubungan antar produk dalam transaksi penjualan.</p>
                    <ul class="mb-0">
                        <li><strong>Support:</strong> Seberapa sering kombinasi produk muncul dalam transaksi (min: 30%)</li>
                        <li><strong>Confidence:</strong> Seberapa kuat hubungan "jika beli A maka beli B" (min: 60%)</li>
                        <li><strong>Lift:</strong> Mengukur seberapa berguna aturan tersebut (>1 = berguna)</li>
                        <li><strong>Ukuran Itemset:</strong> Jumlah maksimum item dalam satu aturan asosiasi (2-5 item)</li>
                    </ul>
                </div>
                
                <!-- Form Parameter -->
                <div class="card analysis-card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-cogs"></i> Parameter Analisis</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="analysisForm">
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="min_support">Minimum Support (%)</label>
                                    <input type="number" class="form-control" id="min_support" name="min_support" 
                                           value="<?= ($minSupport * 100) ?>" min="1" max="100" step="1">
                                    <small class="text-muted">Minimum kemunculan kombinasi produk</small>
                                </div>
                                <div class="col-md-4">
                                    <label for="min_confidence">Minimum Confidence (%)</label>
                                    <input type="number" class="form-control" id="min_confidence" name="min_confidence" 
                                           value="<?= ($minConfidence * 100) ?>" min="1" max="100" step="1">
                                    <small class="text-muted">Minimum kekuatan aturan asosiasi</small>
                                </div>
                                <div class="col-md-4">
                                    <label for="max_itemset_size">Ukuran Itemset Maksimum</label>
                                    <select class="form-control" id="max_itemset_size" name="max_itemset_size">
                                        <option value="2" <?= $maxItemsetSize === 2 ? 'selected' : '' ?>>2 Item</option>
                                        <option value="3" <?= $maxItemsetSize === 3 ? 'selected' : '' ?>>3 Item</option>
                                        <option value="4" <?= $maxItemsetSize === 4 ? 'selected' : '' ?>>4 Item</option>
                                        <option value="5" <?= $maxItemsetSize === 5 ? 'selected' : '' ?>>5 Item</option>
                                    </select>
                                    <small class="text-muted">Maksimum jumlah item dalam satu aturan</small>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label for="start_period">Periode Mulai</label>
                                    <select class="form-control" id="start_period" name="start_period">
                                        <option value="">-- Semua Data --</option>
                                        <?php foreach ($periodOptions as $option): ?>
                                            <option value="<?= $option['value'] ?>" <?= $startPeriod === $option['value'] ? 'selected' : '' ?>>
                                                <?= $option['label'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="end_period">Periode Akhir</label>
                                    <select class="form-control" id="end_period" name="end_period">
                                        <option value="">-- Semua Data --</option>
                                        <?php foreach ($periodOptions as $option): ?>
                                            <option value="<?= $option['value'] ?>" <?= $endPeriod === $option['value'] ? 'selected' : '' ?>>
                                                <?= $option['label'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <button type="submit" name="analyze" class="btn btn-primary">
                                        <i class="fas fa-play"></i> Jalankan Analisis
                                    </button>
                                    <button type="button" class="btn btn-secondary ml-2" onclick="resetForm()">
                                        <i class="fas fa-undo"></i> Reset
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php if ($analysisResult): ?>
                <!-- Tombol Export PDF -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="alert alert-success d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-check-circle"></i>
                                <strong>Analisis berhasil!</strong> Hasil analisis data Apriori telah selesai diproses.
                            </div>
                            <form id="exportPdfForm" method="POST" action="func/export_pdf.php" target="_blank" style="margin: 0;">
                                <input type="hidden" name="analysis_data" value="<?= htmlspecialchars(json_encode($analysisResult)) ?>">
                                <input type="hidden" name="min_support" value="<?= $minSupport * 100 ?>">
                                <input type="hidden" name="min_confidence" value="<?= $minConfidence * 100 ?>">
                                <input type="hidden" name="max_itemset_size" value="<?= $maxItemsetSize ?>">
                                <input type="hidden" name="start_period" value="<?= htmlspecialchars($startPeriod) ?>">
                                <input type="hidden" name="end_period" value="<?= htmlspecialchars($endPeriod) ?>">
                                <button type="button" class="btn btn-danger" onclick="showPdfModal()">
                                    <i class="fas fa-file-pdf"></i> Export PDF
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Hasil Analisis -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card metric-card">
                            <div class="card-body text-center">
                                <h3><?= $analysisResult['total_transactions'] ?></h3>
                                <p class="mb-0">Total Transaksi</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card metric-card">
                            <div class="card-body text-center">
                                <h3><?= count($analysisResult['frequent_itemsets']) ?></h3>
                                <p class="mb-0">Frequent Itemsets</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card metric-card">
                            <div class="card-body text-center">
                                <h3><?= count($analysisResult['association_rules']) ?></h3>
                                <p class="mb-0">Association Rules</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card metric-card">
                            <div class="card-body text-center">
                                <h3><?= $analysisResult['min_support_count'] ?></h3>
                                <p class="mb-0">Min Support Count</p>
                            </div>
                        </div>
                    </div>
                </div>
                


                <!-- Frequent Itemsets -->
                <div class="card analysis-card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-cubes"></i> Frequent Itemsets</h5>
                        <small class="text-muted">Kombinasi produk yang sering muncul bersamaan</small>
                    </div>
                    <div class="card-body">
                        <?php if (empty($analysisResult['frequent_itemsets'])): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> 
                                Tidak ada frequent itemsets ditemukan. Coba turunkan nilai minimum support.
                            </div>
                        <?php else: ?>
                            <?php foreach ($analysisResult['frequent_itemsets'] as $k => $itemsets): ?>
                                <h6 class="mt-3">Level <?= $k ?>-Itemsets (<?= count($itemsets) ?> items)</h6>
                                <div class="row">
                                    <?php foreach ($itemsets as $info): ?>
                                        <div class="col-md-6 mb-2">
                                            <div class="border rounded p-2">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <?php foreach ($info['items'] as $item): ?>
                                                            <span class="itemset-badge"><?= htmlspecialchars($item) ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <div class="text-right">
                                                        <small class="text-muted">
                                                            Support: <?= number_format($info['support'] * 100, 1) ?>%<br>
                                                            Count: <?= $info['count'] ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Association Rules -->
                <div class="card analysis-card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-arrow-right"></i> Association Rules</h5>
                        <small class="text-muted">Aturan "Jika membeli A, maka akan membeli B"</small>
                    </div>
                    <div class="card-body">
                        <?php if (empty($analysisResult['association_rules'])): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> 
                                Tidak ada association rules ditemukan. Coba turunkan nilai minimum confidence.
                            </div>
                        <?php else: ?>
                            <?php foreach (array_slice($analysisResult['association_rules'], 0, 20) as $index => $rule): ?>
                                <?php 
                                $confidenceClass = 'confidence-low';
                                if ($rule['confidence'] >= 0.8) $confidenceClass = 'confidence-high';
                                elseif ($rule['confidence'] >= 0.6) $confidenceClass = 'confidence-medium';
                                ?>
                                <div class="rule-item <?= $confidenceClass ?>">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <h6 class="mb-1">
                                                <span class="badge badge-primary">Jika membeli</span>
                                                <?php foreach ($rule['antecedent'] as $item): ?>
                                                    <span class="itemset-badge"><?= htmlspecialchars($item) ?></span>
                                                <?php endforeach; ?>
                                            </h6>
                                            <h6 class="mb-0">
                                                <span class="badge badge-success">Maka akan membeli</span>
                                                <?php foreach ($rule['consequent'] as $item): ?>
                                                    <span class="itemset-badge"><?= htmlspecialchars($item) ?></span>
                                                <?php endforeach; ?>
                                            </h6>
                                        </div>
                                        <div class="col-md-6 text-right">
                                            <div class="row">
                                                <div class="col-4">
                                                    <small class="text-muted">Support</small><br>
                                                    <strong><?= number_format($rule['support'] * 100, 1) ?>%</strong>
                                                </div>
                                                <div class="col-4">
                                                    <small class="text-muted">Confidence</small><br>
                                                    <strong><?= number_format($rule['confidence'] * 100, 1) ?>%</strong>
                                                </div>
                                                <div class="col-4">
                                                    <small class="text-muted">Lift</small><br>
                                                    <strong><?= number_format($rule['lift'], 2) ?></strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php if (count($analysisResult['association_rules']) > 20): ?>
                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-info-circle"></i> 
                                    Menampilkan 20 aturan teratas dari <?= count($analysisResult['association_rules']) ?> aturan yang ditemukan.
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Visualisasi Chart -->
                <div class="charts-section">
                    <div class="container">
                        <div class="row text-center mb-4">
                            <div class="col-12">
                                <h3 class="mb-3"><i class="fas fa-chart-pie"></i> Visualisasi Hasil Analisis</h3>
                                <p class="text-muted">Grafik interaktif untuk memahami pola dan tren dalam data analisis Apriori</p>
                            </div>
                        </div>
                        
                        <div class="row chart-row">
                            <div class="col-md-6">
                                <div class="chart-container large">
                                    <div class="chart-title">Distribusi Frequent Itemsets</div>
                                    <canvas id="itemsetDistributionChart"></canvas>
                                    <div class="chart-description">Menampilkan distribusi itemsets berdasarkan level</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="chart-container large">
                                    <div class="chart-title">Top Association Rules</div>
                                    <canvas id="confidenceChart"></canvas>
                                    <div class="chart-description">10 aturan asosiasi dengan confidence tertinggi</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row chart-row">
                            <div class="col-md-6">
                                <div class="chart-container large">
                                    <div class="chart-title">Support vs Confidence</div>
                                    <canvas id="scatterChart"></canvas>
                                    <div class="chart-description">Analisis korelasi antara support dan confidence</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="chart-container large">
                                    <div class="chart-title">Metrik Analisis</div>
                                    <canvas id="metricsChart"></canvas>
                                    <div class="chart-description">Ringkasan metrik utama hasil analisis</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Interpretasi dan Rekomendasi -->
                <div class="card analysis-card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-lightbulb"></i> Interpretasi & Rekomendasi Bisnis</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($analysisResult['association_rules'])): ?>
                            <div class="alert alert-success">
                                <h6><i class="fas fa-chart-line"></i> Insight Bisnis:</h6>
                                <ul class="mb-0">
                                    <?php 
                                    $topRules = array_slice($analysisResult['association_rules'], 0, 3);
                                    foreach ($topRules as $rule): 
                                    ?>
                                        <li>
                                            <strong>Cross-selling opportunity:</strong> 
                                            Pelanggan yang membeli <?= implode(', ', $rule['antecedent']) ?> 
                                            memiliki <?= number_format($rule['confidence'] * 100, 1) ?>% kemungkinan 
                                            juga membeli <?= implode(', ', $rule['consequent']) ?>.
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            
                            <div class="alert alert-info">
                                <h6><i class="fas fa-store"></i> Rekomendasi Strategi:</h6>
                                <ul class="mb-0">
                                    <li><strong>Bundle Products:</strong> Buat paket produk berdasarkan aturan dengan confidence tinggi</li>
                                    <li><strong>Store Layout:</strong> Tempatkan produk yang sering dibeli bersamaan di area yang berdekatan</li>
                                    <li><strong>Promotional Strategy:</strong> Berikan diskon untuk produk consequent ketika membeli antecedent</li>
                                    <li><strong>Inventory Management:</strong> Pastikan stok produk yang sering dibeli bersamaan selalu tersedia</li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <h6><i class="fas fa-exclamation-triangle"></i> Tidak Ada Pattern Ditemukan</h6>
                                <p>Kemungkinan penyebab:</p>
                                <ul class="mb-0">
                                    <li>Data transaksi masih terbatas</li>
                                    <li>Parameter minimum support/confidence terlalu tinggi</li>
                                    <li>Produk dijual secara individual tanpa pola kombinasi yang jelas</li>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
            </main>
        </div>
    </div>
    
    <!-- PDF Modal -->
    <div class="modal fade" id="pdfModal" tabindex="-1" aria-labelledby="pdfModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pdfModalLabel">
                        <i class="fas fa-file-pdf text-danger"></i> Preview Laporan Analisis Apriori
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0">
                    <div id="pdfLoadingSpinner" class="text-center p-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-3">Generating PDF...</p>
                    </div>
                    <iframe id="pdfFrame" src="" width="100%" height="600" style="border: none; display: none;"></iframe>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Tutup
                    </button>
                    <button type="button" class="btn btn-primary" onclick="downloadPdf()">
                        <i class="fas fa-download"></i> Download PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript untuk Analisa Data -->
    <!-- AJAX functionality removed - using normal form submission -->
    
    <script>
    // Chart.js Configuration and Data Visualization
    <?php if ($analysisResult): ?>
    document.addEventListener('DOMContentLoaded', function() {
        // Prepare data for charts
        const analysisData = <?= json_encode($analysisResult) ?>;
        
        // 1. Itemset Distribution Pie Chart
        const itemsetLevels = {};
        Object.keys(analysisData.frequent_itemsets).forEach(level => {
            itemsetLevels[`Level ${level}`] = Object.keys(analysisData.frequent_itemsets[level]).length;
        });
        
        const ctx1 = document.getElementById('itemsetDistributionChart').getContext('2d');
        new Chart(ctx1, {
            type: 'pie',
            data: {
                labels: Object.keys(itemsetLevels),
                datasets: [{
                    data: Object.values(itemsetLevels),
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    title: {
                        display: true,
                        text: 'Distribusi Frequent Itemsets per Level'
                    }
                }
            }
        });
        
        // 2. Top Association Rules Bar Chart
        const topRules = analysisData.association_rules.slice(0, 10);
        const ruleLabels = topRules.map(rule => rule.rule_text.length > 30 ? 
            rule.rule_text.substring(0, 30) + '...' : rule.rule_text);
        const confidenceValues = topRules.map(rule => (rule.confidence * 100).toFixed(1));
        
        const ctx2 = document.getElementById('confidenceChart').getContext('2d');
        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: ruleLabels,
                datasets: [{
                    label: 'Confidence (%)',
                    data: confidenceValues,
                    backgroundColor: 'rgba(54, 162, 235, 0.8)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Top 10 Association Rules by Confidence'
                    }
                }
            }
        });
        
        // 3. Support vs Confidence Scatter Plot
        const scatterData = analysisData.association_rules.map(rule => ({
            x: (rule.support * 100).toFixed(2),
            y: (rule.confidence * 100).toFixed(2),
            label: rule.rule_text
        }));
        
        const ctx3 = document.getElementById('scatterChart').getContext('2d');
        new Chart(ctx3, {
            type: 'scatter',
            data: {
                datasets: [{
                    label: 'Association Rules',
                    data: scatterData,
                    backgroundColor: 'rgba(255, 99, 132, 0.6)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        type: 'linear',
                        position: 'bottom',
                        title: {
                            display: true,
                            text: 'Support (%)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Confidence (%)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Support vs Confidence Analysis'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const point = context.parsed;
                                const rule = scatterData[context.dataIndex];
                                return [
                                    `Rule: ${rule.label}`,
                                    `Support: ${point.x}%`,
                                    `Confidence: ${point.y}%`
                                ];
                            }
                        }
                    }
                }
            }
        });
        
        // 4. Metrics Summary Chart
        const totalItemsets = Object.values(analysisData.frequent_itemsets).reduce((sum, level) => sum + Object.keys(level).length, 0);
        const totalRules = analysisData.association_rules.length;
        const avgSupport = analysisData.association_rules.reduce((sum, rule) => sum + rule.support, 0) / totalRules;
        const avgConfidence = analysisData.association_rules.reduce((sum, rule) => sum + rule.confidence, 0) / totalRules;
        
        const ctx4 = document.getElementById('metricsChart').getContext('2d');
        new Chart(ctx4, {
            type: 'doughnut',
            data: {
                labels: ['Frequent Itemsets', 'Association Rules', 'Avg Support (%)', 'Avg Confidence (%)'],
                datasets: [{
                    data: [totalItemsets, totalRules, (avgSupport * 100).toFixed(1), (avgConfidence * 100).toFixed(1)],
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB', 
                        '#FFCE56',
                        '#4BC0C0'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    title: {
                        display: true,
                        text: 'Ringkasan Metrik Analisis'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label;
                                const value = context.parsed;
                                if (label.includes('Support') || label.includes('Confidence')) {
                                    return `${label}: ${value}%`;
                                }
                                return `${label}: ${value}`;
                            }
                        }
                    }
                }
            }
        });
    });
    <?php endif; ?>
    
    function showPdfModal() {
        // Show modal
        $('#pdfModal').modal('show');
        
        // Show loading spinner
        document.getElementById('pdfLoadingSpinner').style.display = 'block';
        document.getElementById('pdfFrame').style.display = 'none';
        
        // Get form data
        var form = document.getElementById('exportPdfForm');
        var formData = new FormData(form);
        
        // Send AJAX request to generate PDF
        fetch('func/export_pdf.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.ok) {
                return response.blob();
            }
            throw new Error('Network response was not ok');
        })
        .then(blob => {
            // Create blob URL and display in iframe
            var pdfUrl = URL.createObjectURL(blob);
            document.getElementById('pdfFrame').src = pdfUrl;
            
            // Hide loading spinner and show iframe
            document.getElementById('pdfLoadingSpinner').style.display = 'none';
            document.getElementById('pdfFrame').style.display = 'block';
            
            // Store blob for download
            window.pdfBlob = blob;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('pdfLoadingSpinner').innerHTML = 
                '<div class="alert alert-danger">Error generating PDF. Please try again.</div>';
        });
    }
    
    function downloadPdf() {
        if (window.pdfBlob) {
            var url = URL.createObjectURL(window.pdfBlob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'Laporan_Analisis_Apriori.pdf';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
    }
    </script>
    
</body>
</html>