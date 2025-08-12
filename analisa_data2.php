<?php
/**
 * HALAMAN ANALISA DATA MENGGUNAKAN ALGORITMA APRIORI
 * 
 * Algoritma Apriori adalah salah satu algoritma data mining yang digunakan untuk
 * menemukan pola hubungan antar item dalam dataset transaksi (Market Basket Analysis).
 * 
 * Tujuan utama:
 * 1. Menemukan itemset yang sering muncul bersamaan (Frequent Itemsets)
 * 2. Membuat aturan asosiasi (Association Rules) seperti "Jika membeli A, maka akan membeli B"
 * 3. Membantu dalam strategi cross-selling dan rekomendasi produk
 * 
 * Parameter penting:
 * - Support: Seberapa sering itemset muncul dalam dataset
 * - Confidence: Seberapa kuat hubungan antara item A dan B
 * - Lift: Mengukur seberapa berguna aturan tersebut
 */

session_start();
if (!isset($_SESSION['userid'])) {
    header("Location: index.php");
    exit();
}

require_once 'db/configdb.php';

/**
 * CLASS APRIORI - IMPLEMENTASI ALGORITMA APRIORI
 * 
 * Class ini mengimplementasikan algoritma Apriori untuk analisis keranjang belanja
 * dengan fitur tambahan filter periode waktu untuk analisis temporal
 */
class AprioriAnalysis
{
    private mysqli $conn;
    private float $minSupport;     // Minimum support threshold (0-1)
    private float $minConfidence;  // Minimum confidence threshold (0-1)
    private bool $useNames;        // true: gunakan nama barang, false: gunakan ID
    private ?string $startDate;    // Tanggal mulai filter
    private ?string $endDate;      // Tanggal akhir filter

    /**
     * Constructor untuk inisialisasi parameter Apriori
     * 
     * @param mysqli $conn Koneksi database
     * @param float $minSupport Minimum support (default: 0.3 = 30%)
     * @param float $minConfidence Minimum confidence (default: 0.6 = 60%)
     * @param bool $useNames Gunakan nama barang atau ID
     * @param string|null $startDate Tanggal mulai (format: Y-m-d)
     * @param string|null $endDate Tanggal akhir (format: Y-m-d)
     */
    public function __construct(
        mysqli $conn, 
        float $minSupport = 0.3, 
        float $minConfidence = 0.6, 
        bool $useNames = true,
        ?string $startDate = null,
        ?string $endDate = null
    ) {
        $this->conn = $conn;
        $this->minSupport = $minSupport;
        $this->minConfidence = $minConfidence;
        $this->useNames = $useNames;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * STEP 1: LOAD DATA TRANSAKSI (KERANJANG BELANJA)
     * 
     * Mengambil data transaksi dari database dan mengkonversinya menjadi
     * format keranjang belanja (basket) untuk analisis Apriori
     * 
     * @return array Array of baskets, setiap basket berisi array item
     */
    public function loadBaskets(): array
    {
        // Query untuk mengambil data transaksi dengan filter periode
        if ($this->useNames) {
            // Menggunakan nama barang untuk analisis yang lebih mudah dipahami
            $sql = "SELECT td.idtransaksi, 
                           GROUP_CONCAT(DISTINCT mb.namabarang ORDER BY mb.namabarang) AS items_csv
                    FROM transaksi_detail td
                    JOIN master_barang mb ON mb.idbarang = td.idbarang
                    JOIN transaksi t ON t.idtransaksi = td.idtransaksi";
        } else {
            // Menggunakan ID barang untuk performa yang lebih cepat
            $sql = "SELECT td.idtransaksi, 
                           GROUP_CONCAT(DISTINCT td.idbarang ORDER BY td.idbarang) AS items_csv
                    FROM transaksi_detail td
                    JOIN transaksi t ON t.idtransaksi = td.idtransaksi";
        }
        
        // Tambahkan filter periode jika ada
        $whereConditions = [];
        if ($this->startDate) {
            $whereConditions[] = "DATE(t.created_at) >= '" . $this->conn->real_escape_string($this->startDate) . "'";
        }
        if ($this->endDate) {
            $whereConditions[] = "DATE(t.created_at) <= '" . $this->conn->real_escape_string($this->endDate) . "'";
        }
        
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(" AND ", $whereConditions);
        }
        
        $sql .= " GROUP BY td.idtransaksi";
        
        $result = $this->conn->query($sql);
        $baskets = [];
        
        // Konversi hasil query menjadi array keranjang belanja
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['items_csv'])) {
                // Pisahkan item dan bersihkan data
                $items = array_values(array_filter(array_map('trim', explode(',', $row['items_csv']))));
                sort($items); // Urutkan untuk konsistensi
                $baskets[] = $items;
            }
        }
        
        return $baskets;
    }

    /**
     * STEP 2: GENERATE KOMBINASI ITEM
     * 
     * Membuat semua kombinasi k-item dari array items
     * Contoh: dari [A,B,C] dengan k=2 akan menghasilkan [A,B], [A,C], [B,C]
     * 
     * @param array $items Array item
     * @param int $k Ukuran kombinasi
     * @return array Array kombinasi
     */
    private function generateCombinations(array $items, int $k): array
    {
        $result = [];
        $n = count($items);
        
        // Base case: kombinasi 1 item
        if ($k === 1) {
            foreach ($items as $item) {
                $result[] = [$item];
            }
            return $result;
        }
        
        // Recursive function untuk generate kombinasi
        $generateRecursive = function($start, $combination) use (&$generateRecursive, $items, $n, $k, &$result) {
            if (count($combination) === $k) {
                $result[] = $combination;
                return;
            }
            
            for ($i = $start; $i < $n; $i++) {
                $newCombination = $combination;
                $newCombination[] = $items[$i];
                $generateRecursive($i + 1, $newCombination);
            }
        };
        
        $generateRecursive(0, []);
        return $result;
    }

    /**
     * STEP 3: CREATE UNIQUE KEY UNTUK ITEMSET
     * 
     * Membuat key unik untuk setiap itemset agar bisa digunakan sebagai index array
     * 
     * @param array $itemset Array item
     * @return string Unique key
     */
    private function createItemsetKey(array $itemset): string
    {
        sort($itemset); // Pastikan urutan konsisten
        return implode('||', $itemset);
    }

    /**
     * STEP 4: HITUNG SUPPORT ITEMSET
     * 
     * Support = (Jumlah transaksi yang mengandung itemset) / (Total transaksi)
     * Support mengukur seberapa sering itemset muncul dalam dataset
     * 
     * @param array $itemset Item yang akan dihitung supportnya
     * @param array $baskets Array semua keranjang belanja
     * @return float Nilai support (0-1)
     */
    private function calculateSupport(array $itemset, array $baskets): float
    {
        $count = 0;
        $totalBaskets = count($baskets);
        
        foreach ($baskets as $basket) {
            // Cek apakah semua item dalam itemset ada di basket ini
            $basketSet = array_flip($basket); // Konversi ke associative array untuk pencarian cepat
            $found = true;
            
            foreach ($itemset as $item) {
                if (!isset($basketSet[$item])) {
                    $found = false;
                    break;
                }
            }
            
            if ($found) {
                $count++;
            }
        }
        
        return $totalBaskets > 0 ? $count / $totalBaskets : 0;
    }

    /**
     * STEP 5: MAIN APRIORI ALGORITHM
     * 
     * Implementasi utama algoritma Apriori:
     * 1. Cari frequent 1-itemsets
     * 2. Generate candidate k-itemsets dari frequent (k-1)-itemsets
     * 3. Prune candidates yang tidak memenuhi minimum support
     * 4. Ulangi sampai tidak ada frequent itemsets baru
     * 5. Generate association rules
     * 
     * @param int $maxK Maximum ukuran itemset yang akan dicari
     * @return array Hasil analisis berisi frequent itemsets dan association rules
     */
    public function runApriori(int $maxK = 3): array
    {
        // Load data transaksi
        $baskets = $this->loadBaskets();
        $totalBaskets = count($baskets);
        
        if ($totalBaskets === 0) {
            return [
                'frequent_itemsets' => [],
                'association_rules' => [],
                'total_transactions' => 0,
                'min_support_count' => 0
            ];
        }
        
        // Hitung minimum count berdasarkan minimum support
        $minSupportCount = ceil($this->minSupport * $totalBaskets);
        
        // STEP 5.1: Cari frequent 1-itemsets
        $itemCounts = [];
        foreach ($baskets as $basket) {
            foreach (array_unique($basket) as $item) {
                $itemCounts[$item] = ($itemCounts[$item] ?? 0) + 1;
            }
        }
        
        // Simpan frequent itemsets per level k
        $frequentItemsets = [];
        $frequentItemsets[1] = [];
        
        // Filter item yang memenuhi minimum support
        foreach ($itemCounts as $item => $count) {
            if ($count >= $minSupportCount) {
                $key = $this->createItemsetKey([$item]);
                $frequentItemsets[1][$key] = [
                    'items' => [$item],
                    'count' => $count,
                    'support' => $count / $totalBaskets
                ];
            }
        }
        
        // STEP 5.2: Generate frequent k-itemsets untuk k >= 2
        for ($k = 2; $k <= $maxK; $k++) {
            if (empty($frequentItemsets[$k - 1])) {
                break; // Tidak ada frequent (k-1)-itemsets, hentikan
            }
            
            // Generate candidates dari frequent (k-1)-itemsets
            $candidates = [];
            $previousItemsets = array_values(array_map(fn($x) => $x['items'], $frequentItemsets[$k - 1]));
            $numPrevious = count($previousItemsets);
            
            // Gabungkan setiap pasang frequent (k-1)-itemsets
            for ($i = 0; $i < $numPrevious; $i++) {
                for ($j = $i + 1; $j < $numPrevious; $j++) {
                    $union = array_values(array_unique(array_merge($previousItemsets[$i], $previousItemsets[$j])));
                    sort($union);
                    
                    // Pastikan ukuran union = k
                    if (count($union) !== $k) {
                        continue;
                    }
                    
                    // APRIORI PRUNING: Semua subset (k-1) harus frequent
                    $allSubsetsFrequent = true;
                    foreach ($this->generateCombinations($union, $k - 1) as $subset) {
                        $subsetKey = $this->createItemsetKey($subset);
                        if (!isset($frequentItemsets[$k - 1][$subsetKey])) {
                            $allSubsetsFrequent = false;
                            break;
                        }
                    }
                    
                    if ($allSubsetsFrequent) {
                        $candidateKey = $this->createItemsetKey($union);
                        $candidates[$candidateKey] = $union;
                    }
                }
            }
            
            if (empty($candidates)) {
                break; // Tidak ada candidates, hentikan
            }
            
            // Hitung support untuk setiap candidate
            $frequentItemsets[$k] = [];
            foreach ($candidates as $candidateKey => $candidateItems) {
                $support = $this->calculateSupport($candidateItems, $baskets);
                $count = round($support * $totalBaskets);
                
                if ($count >= $minSupportCount) {
                    $frequentItemsets[$k][$candidateKey] = [
                        'items' => $candidateItems,
                        'count' => $count,
                        'support' => $support
                    ];
                }
            }
        }
        
        // STEP 5.3: Generate Association Rules
        $associationRules = $this->generateAssociationRules($frequentItemsets, $baskets);
        
        return [
            'frequent_itemsets' => $frequentItemsets,
            'association_rules' => $associationRules,
            'total_transactions' => $totalBaskets,
            'min_support_count' => $minSupportCount,
            'parameters' => [
                'min_support' => $this->minSupport,
                'min_confidence' => $this->minConfidence,
                'period' => [
                    'start' => $this->startDate,
                    'end' => $this->endDate
                ]
            ]
        ];
    }

    /**
     * STEP 6: GENERATE ASSOCIATION RULES
     * 
     * Dari frequent itemsets, buat aturan asosiasi dalam bentuk X => Y
     * dimana X adalah antecedent dan Y adalah consequent
     * 
     * Confidence(X => Y) = Support(X ∪ Y) / Support(X)
     * Lift(X => Y) = Confidence(X => Y) / Support(Y)
     * 
     * @param array $frequentItemsets Frequent itemsets dari algoritma Apriori
     * @param array $baskets Data keranjang belanja
     * @return array Association rules yang memenuhi minimum confidence
     */
    private function generateAssociationRules(array $frequentItemsets, array $baskets): array
    {
        $rules = [];
        $supportLookup = [];
        
        // Buat lookup table untuk support setiap itemset
        foreach ($frequentItemsets as $k => $itemsets) {
            foreach ($itemsets as $key => $info) {
                $supportLookup[$key] = $info['support'];
            }
        }
        
        // Generate rules dari frequent itemsets dengan k >= 2
        foreach ($frequentItemsets as $k => $itemsets) {
            if ($k < 2) continue; // Skip 1-itemsets
            
            foreach ($itemsets as $info) {
                $items = $info['items'];
                $supportXY = $info['support'];
                
                // Generate semua kemungkinan pembagian X => Y
                $numItems = count($items);
                for ($antecedentSize = 1; $antecedentSize < $numItems; $antecedentSize++) {
                    foreach ($this->generateCombinations($items, $antecedentSize) as $antecedent) {
                        $consequent = array_values(array_diff($items, $antecedent));
                        
                        $antecedentKey = $this->createItemsetKey($antecedent);
                        $consequentKey = $this->createItemsetKey($consequent);
                        
                        // Pastikan antecedent ada di frequent itemsets
                        if (!isset($supportLookup[$antecedentKey])) {
                            continue;
                        }
                        
                        $supportX = $supportLookup[$antecedentKey];
                        $supportY = $supportLookup[$consequentKey] ?? 0;
                        
                        // Hitung confidence
                        $confidence = $supportX > 0 ? $supportXY / $supportX : 0;
                        
                        // Filter berdasarkan minimum confidence
                        if ($confidence >= $this->minConfidence) {
                            // Hitung lift
                            $lift = $supportY > 0 ? $confidence / $supportY : 0;
                            
                            $rules[] = [
                                'antecedent' => $antecedent,
                                'consequent' => $consequent,
                                'support' => $supportXY,
                                'confidence' => $confidence,
                                'lift' => $lift,
                                'rule_text' => implode(', ', $antecedent) . ' => ' . implode(', ', $consequent)
                            ];
                        }
                    }
                }
            }
        }
        
        // Urutkan rules berdasarkan confidence (descending)
        usort($rules, fn($a, $b) => $b['confidence'] <=> $a['confidence']);
        
        return $rules;
    }

    /**
     * UTILITY: Get periode options untuk dropdown
     * 
     * @return array Array periode dalam format [value => label]
     */
    public static function getPeriodOptions(): array
    {
        $options = [];
        $currentYear = date('Y');
        $currentMonth = date('n');
        
        // Generate options untuk 12 bulan terakhir
        for ($i = 11; $i >= 0; $i--) {
            $targetMonth = $currentMonth - $i;
            $targetYear = $currentYear;
            
            if ($targetMonth <= 0) {
                $targetMonth += 12;
                $targetYear--;
            }
            
            $monthName = date('F', mktime(0, 0, 0, $targetMonth, 1));
            $value = sprintf('%04d-%02d', $targetYear, $targetMonth);
            $label = "$monthName $targetYear";
            
            $options[$value] = $label;
        }
        
        return $options;
    }
}

// ===== PROCESSING FORM INPUT =====
$minSupport = isset($_POST['min_support']) ? (float)$_POST['min_support'] / 100 : 0.3;
$minConfidence = isset($_POST['min_confidence']) ? (float)$_POST['min_confidence'] / 100 : 0.6;
$startPeriod = $_POST['start_period'] ?? null;
$endPeriod = $_POST['end_period'] ?? null;

// Konversi periode ke tanggal
$startDate = null;
$endDate = null;

if ($startPeriod) {
    $startDate = $startPeriod . '-01';
}

if ($endPeriod) {
    $endDate = date('Y-m-t', strtotime($endPeriod . '-01')); // Last day of month
}

// Jalankan analisis jika form di-submit
$analysisResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $apriori = new AprioriAnalysis($conn, $minSupport, $minConfidence, true, $startDate, $endDate);
    $analysisResult = $apriori->runApriori(3);
}

$periodOptions = AprioriAnalysis::getPeriodOptions();
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
                
                <!-- Info Panel -->
                <div class="alert alert-info" role="alert">
                    <h5><i class="fas fa-info-circle"></i> Tentang Algoritma Apriori</h5>
                    <p class="mb-2"><strong>Algoritma Apriori</strong> adalah teknik data mining untuk menemukan pola hubungan antar produk dalam transaksi penjualan.</p>
                    <ul class="mb-0">
                        <li><strong>Support:</strong> Seberapa sering kombinasi produk muncul dalam transaksi (min: 30%)</li>
                        <li><strong>Confidence:</strong> Seberapa kuat hubungan "jika beli A maka beli B" (min: 60%)</li>
                        <li><strong>Lift:</strong> Mengukur seberapa berguna aturan tersebut (>1 = berguna)</li>
                    </ul>
                </div>
                
                <!-- Form Parameter -->
                <div class="card analysis-card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-cogs"></i> Parameter Analisis</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-3">
                                    <label for="min_support">Minimum Support (%)</label>
                                    <input type="number" class="form-control" id="min_support" name="min_support" 
                                           value="<?= ($minSupport * 100) ?>" min="1" max="100" step="1">
                                    <small class="text-muted">Minimum kemunculan kombinasi produk</small>
                                </div>
                                <div class="col-md-3">
                                    <label for="min_confidence">Minimum Confidence (%)</label>
                                    <input type="number" class="form-control" id="min_confidence" name="min_confidence" 
                                           value="<?= ($minConfidence * 100) ?>" min="1" max="100" step="1">
                                    <small class="text-muted">Minimum kekuatan aturan asosiasi</small>
                                </div>
                                <div class="col-md-3">
                                    <label for="start_period">Periode Mulai</label>
                                    <select class="form-control" id="start_period" name="start_period">
                                        <option value="">-- Semua Data --</option>
                                        <?php foreach ($periodOptions as $value => $label): ?>
                                            <option value="<?= $value ?>" <?= $startPeriod === $value ? 'selected' : '' ?>>
                                                <?= $label ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="end_period">Periode Akhir</label>
                                    <select class="form-control" id="end_period" name="end_period">
                                        <option value="">-- Semua Data --</option>
                                        <?php foreach ($periodOptions as $value => $label): ?>
                                            <option value="<?= $value ?>" <?= $endPeriod === $value ? 'selected' : '' ?>>
                                                <?= $label ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-play"></i> Jalankan Analisis
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php if ($analysisResult): ?>
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
                                <h3><?= count($analysisResult['frequent_itemsets'], COUNT_RECURSIVE) - count($analysisResult['frequent_itemsets']) ?></h3>
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
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-submit form when period changes
        $('#start_period, #end_period').change(function() {
            if ($('#start_period').val() && $('#end_period').val()) {
                // Validate period range
                if ($('#start_period').val() > $('#end_period').val()) {
                    alert('Periode mulai tidak boleh lebih besar dari periode akhir!');
                    return;
                }
            }
        });
        
        // Tooltip for metrics
        $('[data-toggle="tooltip"]').tooltip();
    </script>
</body>
</html>