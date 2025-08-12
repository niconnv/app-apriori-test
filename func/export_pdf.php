<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    header('Location: ../index.php');
    exit();
}

// Check if analysis data is provided
if (!isset($_POST['analysis_data']) || !isset($_POST['min_support']) || !isset($_POST['min_confidence'])) {
    die('Data analisis tidak tersedia');
}

// Get analysis parameters
$analysisData = json_decode($_POST['analysis_data'], true);
$minSupport = $_POST['min_support'];
$minConfidence = $_POST['min_confidence'];
$maxItemsetSize = $_POST['max_itemset_size'] ?? 3;
$startPeriod = $_POST['start_period'] ?? '';
$endPeriod = $_POST['end_period'] ?? '';

// Validate analysis data
if (!$analysisData || !isset($analysisData['frequent_itemsets']) || !isset($analysisData['association_rules'])) {
    die('Data analisis tidak valid');
}

// Include DomPDF library
require_once('../vendor/autoload.php');
use Dompdf\Dompdf;
use Dompdf\Options;

// Configure DomPDF options
$options = new Options();
$options->set('defaultFont', 'Arial');
$options->set('isRemoteEnabled', true);

// Create DomPDF instance
$dompdf = new Dompdf($options);

// Create HTML content for PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Analisis Apriori</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
        .parameter {
            margin-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }
        .text-center {
            text-align: center;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">LAPORAN ANALISIS APRIORI</div>
    </div>
    
    <div class="section">
        <div class="section-title">Parameter Analisis</div>
        <div class="parameter">Minimum Support: ' . $minSupport . '%</div>
        <div class="parameter">Minimum Confidence: ' . $minConfidence . '%</div>
        <div class="parameter">Ukuran Itemset Maksimum: ' . $maxItemsetSize . ' Item</div>';
        
if ($startPeriod && $endPeriod) {
    $html .= '<div class="parameter">Periode: ' . htmlspecialchars($startPeriod) . ' s/d ' . htmlspecialchars($endPeriod) . '</div>';
}

$html .= '
    </div>
    
    <div class="section">
        <div class="section-title">Ringkasan Hasil</div>
        <div class="summary-grid">
            <div class="parameter">Total Transaksi: ' . ($analysisData['total_transactions'] ?? 0) . '</div>
            <div class="parameter">Total Item Unik: ' . ($analysisData['total_items'] ?? 0) . '</div>
            <div class="parameter">Frequent Itemsets: ' . array_sum(array_map('count', $analysisData['frequent_itemsets'])) . '</div>
            <div class="parameter">Association Rules: ' . count($analysisData['association_rules']) . '</div>
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">Frequent Itemsets</div>
        <table>
            <thead>
                <tr>
                    <th>Itemset</th>
                    <th>Support</th>
                    <th>Count</th>
                </tr>
            </thead>
            <tbody>';
            
// Handle nested frequent itemsets structure
foreach ($analysisData['frequent_itemsets'] as $k => $itemsets) {
    foreach ($itemsets as $itemset) {
        $items = is_array($itemset['items']) ? implode(', ', $itemset['items']) : $itemset['items'];
        $html .= '
                    <tr>
                        <td>' . htmlspecialchars($items) . '</td>
                        <td class="text-center">' . number_format($itemset['support'] * 100, 2) . '%</td>
                        <td class="text-center">' . ($itemset['count'] ?? 0) . '</td>
                    </tr>';
    }
}

$html .= '
            </tbody>
        </table>
    </div>
    
    <div class="section">
        <div class="section-title">Association Rules</div>
        <table>
            <thead>
                <tr>
                    <th>Antecedent</th>
                    <th>Consequent</th>
                    <th>Support</th>
                    <th>Confidence</th>
                    <th>Lift</th>
                </tr>
            </thead>
            <tbody>';
            
foreach ($analysisData['association_rules'] as $rule) {
    $antecedent = is_array($rule['antecedent']) ? implode(', ', $rule['antecedent']) : $rule['antecedent'];
    $consequent = is_array($rule['consequent']) ? implode(', ', $rule['consequent']) : $rule['consequent'];
    
    $html .= '
                <tr>
                    <td>' . htmlspecialchars($antecedent) . '</td>
                    <td>' . htmlspecialchars($consequent) . '</td>
                    <td class="text-center">' . number_format($rule['support'] * 100, 2) . '%</td>
                    <td class="text-center">' . number_format($rule['confidence'] * 100, 2) . '%</td>
                    <td class="text-center">' . number_format($rule['lift'], 2) . '</td>
                </tr>';
}

$html .= '
            </tbody>
        </table>
    </div>
</body>
</html>';

// Load HTML content
$dompdf->loadHtml($html);

// Set paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Render PDF
$dompdf->render();

// Output PDF
$filename = 'Laporan_Analisis_Apriori_' . date('Y-m-d_H-i-s') . '.pdf';

// Set headers for PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Output the PDF
echo $dompdf->output();


?>