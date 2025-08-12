<?php
/**
 * CLASS APRIORI - IMPLEMENTASI ALGORITMA APRIORI
 * 
 * Class ini mengimplementasikan algoritma Apriori untuk analisis keranjang belanja
 * dengan fitur tambahan filter periode waktu untuk analisis temporal
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

class AprioriAnalysis
{
    private PDO $conn;
    private float $minSupport;     // Minimum support threshold (0-1)
    private float $minConfidence;  // Minimum confidence threshold (0-1)
    
    /**
     * Constructor untuk inisialisasi parameter Apriori
     * 
     * @param PDO $conn Koneksi database PDO
     * @param float $minSupport Minimum support (default: 0.3 = 30%)
     * @param float $minConfidence Minimum confidence (default: 0.6 = 60%)
     */
    public function __construct(
        PDO $conn, 
        float $minSupport = 0.3, 
        float $minConfidence = 0.6
    ) {
        $this->conn = $conn;
        $this->minSupport = $minSupport;
        $this->minConfidence = $minConfidence;
    }

    /**
     * STEP 1: LOAD DATA TRANSAKSI (KERANJANG BELANJA)
     * 
     * Mengambil data transaksi dari database dan mengkonversinya menjadi
     * format keranjang belanja (basket) untuk analisis Apriori
     * 
     * @param string|null $startPeriod Periode mulai (format: Y-m)
     * @param string|null $endPeriod Periode akhir (format: Y-m)
     * @return array Array of baskets, setiap basket berisi array item
     */
    public function loadBaskets(?string $startPeriod = null, ?string $endPeriod = null): array
    {
        try {
            // Query untuk mengambil data transaksi dengan join ke detail dan master barang
            $sql = "
                SELECT 
                    t.idtransaksi,
                    mb.namabarang
                FROM transaksi t
                JOIN transaksi_detail td ON t.idtransaksi = td.idtransaksi
                JOIN master_barang mb ON td.idbarang = mb.idbarang
            ";
            
            $params = [];
            
            // Tambahkan filter periode jika ada
            if ($startPeriod && $endPeriod) {
                $sql .= " WHERE DATE_FORMAT(t.created_at, '%Y-%m') BETWEEN ? AND ?";
                $params[] = $startPeriod;
                $params[] = $endPeriod;
            } elseif ($startPeriod) {
                $sql .= " WHERE DATE_FORMAT(t.created_at, '%Y-%m') >= ?";
                $params[] = $startPeriod;
            } elseif ($endPeriod) {
                $sql .= " WHERE DATE_FORMAT(t.created_at, '%Y-%m') <= ?";
                $params[] = $endPeriod;
            }
            
            $sql .= " ORDER BY t.idtransaksi, mb.namabarang";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Konversi hasil query menjadi format basket
            $baskets = [];
            $currentTransactionId = null;
            $currentBasket = [];
            
            foreach ($results as $row) {
                if ($currentTransactionId !== $row['idtransaksi']) {
                    // Simpan basket sebelumnya jika ada
                    if (!empty($currentBasket)) {
                        $baskets[] = $currentBasket;
                    }
                    
                    // Mulai basket baru
                    $currentTransactionId = $row['idtransaksi'];
                    $currentBasket = [$row['namabarang']];
                } else {
                    // Tambahkan item ke basket yang sama
                    $currentBasket[] = $row['namabarang'];
                }
            }
            
            // Jangan lupa simpan basket terakhir
            if (!empty($currentBasket)) {
                $baskets[] = $currentBasket;
            }
            
            return $baskets;
            
        } catch (Exception $e) {
            error_log("Error in loadBaskets: " . $e->getMessage());
            return [];
        }
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
     * @param string|null $startPeriod Periode mulai (format: Y-m)
     * @param string|null $endPeriod Periode akhir (format: Y-m)
     * @param int $maxK Maximum ukuran itemset yang akan dicari
     * @return array Hasil analisis berisi frequent itemsets dan association rules
     */
    public function runApriori(?string $startPeriod = null, ?string $endPeriod = null, int $maxK = 3): array
    {
        // Load data transaksi
        $baskets = $this->loadBaskets($startPeriod, $endPeriod);
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
                        'start' => $startPeriod,
                        'end' => $endPeriod
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
     * UTILITY: GET PERIOD OPTIONS
     * 
     * Mengambil daftar periode yang tersedia dari data transaksi
     * untuk ditampilkan di dropdown form
     * 
     * @return array Array periode dalam format ['value' => 'YYYY-MM', 'label' => 'Month YYYY']
     */
    public function getPeriodOptions(): array
    {
        try {
            $sql = "SELECT DISTINCT 
                           DATE_FORMAT(created_at, '%Y-%m') as period_value,
                           DATE_FORMAT(created_at, '%M %Y') as period_label
                    FROM transaksi 
                    WHERE created_at IS NOT NULL
                    ORDER BY period_value DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $options = [];
            foreach ($results as $row) {
                $options[] = [
                    'value' => $row['period_value'],
                    'label' => $row['period_label']
                ];
            }
            
            return $options;
            
        } catch (Exception $e) {
            error_log("Error in getPeriodOptions: " . $e->getMessage());
            return [];
        }
    }
}
?>