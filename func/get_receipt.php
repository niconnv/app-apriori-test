<?php
session_start();
include '../db/configdb.php';

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    echo '<div class="alert alert-danger">Anda harus login terlebih dahulu.</div>';
    exit;
}

// Get transaction ID
$idtransaksi = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($idtransaksi)) {
    echo '<div class="alert alert-danger">ID transaksi tidak valid.</div>';
    exit;
}

// Convert to zerofill format if it's a number
if (is_numeric($idtransaksi)) {
    $idtransaksi = str_pad($idtransaksi, 10, '0', STR_PAD_LEFT);
}

try {
    // Get transaction header
    $query_header = "SELECT t.*, u.iduser 
                     FROM transaksi t 
                     LEFT JOIN user u ON t.iduser = u.iduser 
                     WHERE t.idtransaksi = ?";
    $stmt_header = mysqli_prepare($conn, $query_header);
    mysqli_stmt_bind_param($stmt_header, "s", $idtransaksi);
    mysqli_stmt_execute($stmt_header);
    $result_header = mysqli_stmt_get_result($stmt_header);
    $transaction = mysqli_fetch_assoc($result_header);
    
    if (!$transaction) {
        echo '<div class="alert alert-danger">Transaksi tidak ditemukan.</div>';
        exit;
    }
    
    // Get transaction details
    $query_detail = "SELECT td.*, mb.namabarang 
                     FROM transaksi_detail td 
                     LEFT JOIN master_barang mb ON td.idbarang = mb.idbarang 
                     WHERE td.idtransaksi = ?
                     ORDER BY td.id";
    $stmt_detail = mysqli_prepare($conn, $query_detail);
    mysqli_stmt_bind_param($stmt_detail, "s", $idtransaksi);
    mysqli_stmt_execute($stmt_detail);
    $result_detail = mysqli_stmt_get_result($stmt_detail);
    
    $details = [];
    while ($row = mysqli_fetch_assoc($result_detail)) {
        $details[] = $row;
    }
    
    // Format date
    $date = date('d/m/Y H:i', strtotime($transaction['created_at']));
    $transaction_id = str_pad($transaction['idtransaksi'], 10, '0', STR_PAD_LEFT);
    
    // Generate receipt HTML
    echo '
    <div class="receipt-container" style="font-family: monospace; max-width: 300px; margin: 0 auto; padding: 10px; border: 1px dashed #000;">
        <div class="receipt-header" style="text-align: center; margin-bottom: 15px;">
            <h4 style="margin: 0; font-size: 18px; font-weight: bold;">WARUNG WEDANG JAHE</h4>
            <p style="margin: 5px 0; font-size: 12px;">Jl. Contoh No. 123</p>
            <p style="margin: 5px 0; font-size: 12px;">Telp: 0812-3456-7890</p>
            <div style="border-top: 1px dashed #000; margin: 10px 0;"></div>
        </div>
        
        <div class="receipt-info" style="margin-bottom: 15px; font-size: 12px;">
            <div style="display: flex; justify-content: space-between;">
                <span>No. Transaksi:</span>
                <span>' . $transaction_id . '</span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>Tanggal:</span>
                <span>' . $date . '</span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>Kasir:</span>
                <span>' . htmlspecialchars($transaction['iduser']) . '</span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>Pembeli:</span>
                <span>' . htmlspecialchars($transaction['penerima']) . '</span>
            </div>
            <div style="border-top: 1px dashed #000; margin: 10px 0;"></div>
        </div>
        
        <div class="receipt-items" style="margin-bottom: 15px; font-size: 12px;">
    ';
    
    $total_qty = 0;
    $total_amount = 0;
    
    foreach ($details as $item) {
        $item_total = $item['harga'] * $item['jumlah'];
        $total_qty += $item['jumlah'];
        $total_amount += $item_total;
        
        echo '
            <div style="margin-bottom: 8px;">
                <div style="font-weight: bold;">' . htmlspecialchars($item['namabarang']) . '</div>
                <div style="display: flex; justify-content: space-between;">
                    <span>' . $item['jumlah'] . ' x Rp ' . number_format($item['harga'], 0, ',', '.') . '</span>
                    <span>Rp ' . number_format($item_total, 0, ',', '.') . '</span>
                </div>
            </div>
        ';
    }
    
    echo '
            <div style="border-top: 1px dashed #000; margin: 10px 0;"></div>
        </div>
        
        <div class="receipt-total" style="margin-bottom: 15px; font-size: 12px;">
            <div style="display: flex; justify-content: space-between;">
                <span>Total Item:</span>
                <span>' . $total_qty . '</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 14px;">
                <span>TOTAL:</span>
                <span>Rp ' . number_format($total_amount, 0, ',', '.') . '</span>
            </div>
            <div style="border-top: 1px dashed #000; margin: 10px 0;"></div>
        </div>
        
        <div class="receipt-footer" style="text-align: center; font-size: 11px;">
            <p style="margin: 5px 0;">Terima kasih atas kunjungan Anda</p>
            <p style="margin: 5px 0;">Barang yang sudah dibeli tidak dapat dikembalikan</p>
            <p style="margin: 5px 0;">Simpan struk ini sebagai bukti pembelian</p>
        </div>
    </div>
    
    <style>
        @media print {
            .receipt-container {
                border: none !important;
                max-width: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            body {
                margin: 0;
                padding: 10px;
            }
        }
    </style>
    ';
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Terjadi kesalahan: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

mysqli_close($conn);
?>