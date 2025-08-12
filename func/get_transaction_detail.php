<?php
require_once '../db/configdb.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    http_response_code(401);
    echo '<div class="alert alert-danger">Unauthorized access.</div>';
    exit;
}

// Get transaction ID
$idtransaksi = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($idtransaksi <= 0) {
    echo '<div class="alert alert-danger">ID transaksi tidak valid.</div>';
    exit;
}

try {
    // Get transaction header
    $stmt = $conn->prepare("SELECT t.*, u.iduser as username FROM transaksi t LEFT JOIN user u ON t.iduser = u.iduser WHERE t.idtransaksi = ?");
    $stmt->bind_param("i", $idtransaksi);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo '<div class="alert alert-warning">Transaksi tidak ditemukan.</div>';
        exit;
    }
    
    $transaksi = $result->fetch_assoc();
    
    // Get transaction details
    $stmt = $conn->prepare("
        SELECT td.*, mb.namabarang 
        FROM transaksi_detail td 
        JOIN master_barang mb ON td.idbarang = mb.idbarang 
        WHERE td.idtransaksi = ? 
        ORDER BY td.id
    ");
    $stmt->bind_param("i", $idtransaksi);
    $stmt->execute();
    $detailResult = $stmt->get_result();
    
    $details = [];
    while ($row = $detailResult->fetch_assoc()) {
        $details[] = $row;
    }
    
    // Format currency
    function formatRupiah($angka) {
        return 'Rp ' . number_format($angka, 0, ',', '.');
    }
    
    // Format date
    function formatTanggal($tanggal) {
        return date('d/m/Y H:i', strtotime($tanggal));
    }
    
?>

<div class="container-fluid">
    <!-- Transaction Header -->
    <div class="row mb-4">
        <div class="col-md-6">
            <table class="table table-borderless table-sm">
                <tr>
                    <td width="40%"><strong>ID Transaksi:</strong></td>
                    <td><?= sprintf('%010d', $transaksi['idtransaksi']) ?></td>
                </tr>
                <tr>
                    <td><strong>Tanggal:</strong></td>
                    <td><?= formatTanggal($transaksi['created_at']) ?></td>
                </tr>
                <tr>
                    <td><strong>User:</strong></td>
                    <td><span class="badge badge-primary"><?= htmlspecialchars($transaksi['iduser']) ?></span></td>
                </tr>
            </table>
        </div>
        <div class="col-md-6">
            <table class="table table-borderless table-sm">
                <tr>
                    <td width="40%"><strong>Nama Pembeli:</strong></td>
                    <td><?= htmlspecialchars($transaksi['penerima']) ?></td>
                </tr>
                <tr>
                    <td><strong>Total Item:</strong></td>
                    <td><?= $transaksi['totalbarang'] ?> item</td>
                </tr>
                <tr>
                    <td><strong>Total Harga:</strong></td>
                    <td><strong class="text-success"><?= formatRupiah($transaksi['totalharga']) ?></strong></td>
                </tr>
            </table>
        </div>
    </div>
    
    <hr>
    
    <!-- Transaction Details -->
    <div class="row">
        <div class="col-12">
            <h6 class="mb-3"><i class="fas fa-list"></i> Detail Barang</h6>
            
            <?php if (empty($details)): ?>
                <div class="alert alert-info">Tidak ada detail barang untuk transaksi ini.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-sm">
                        <thead class="thead-light">
                            <tr>
                                <th width="5%" class="text-center">No</th>
                                <th width="40%">Nama Barang</th>
                                <th width="15%" class="text-center">Harga Satuan</th>
                                <th width="10%" class="text-center">Jumlah</th>
                                <th width="15%" class="text-center">Subtotal</th>
                                <th width="15%" class="text-center">Tanggal Input</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            $grandTotal = 0;
                            foreach ($details as $detail): 
                                $grandTotal += $detail['totalharga'];
                            ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($detail['namabarang']) ?></td>
                                    <td class="text-right"><?= formatRupiah($detail['harga']) ?></td>
                                    <td class="text-center"><?= $detail['jumlah'] ?></td>
                                    <td class="text-right"><?= formatRupiah($detail['totalharga']) ?></td>
                                    <td class="text-center"><?= formatTanggal($detail['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="thead-light">
                            <tr>
                                <th colspan="4" class="text-right">Grand Total:</th>
                                <th class="text-right"><?= formatRupiah($grandTotal) ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Additional Info -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body">
                    <h6 class="card-title"><i class="fas fa-info-circle"></i> Informasi Tambahan</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">
                                <strong>Dibuat oleh:</strong> <?= htmlspecialchars($transaksi['created_by']) ?><br>
                                <strong>Tanggal dibuat:</strong> <?= formatTanggal($transaksi['created_at']) ?>
                            </small>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">
                                <?php if (!empty($details)): ?>
                                    <strong>Terakhir diupdate:</strong> <?= formatTanggal($details[0]['updated_at']) ?><br>
                                    <strong>Diupdate oleh:</strong> <?= htmlspecialchars($details[0]['updated_by']) ?>
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
} catch (Exception $e) {
    error_log("Get transaction detail error: " . $e->getMessage());
    echo '<div class="alert alert-danger">Terjadi kesalahan saat memuat detail transaksi.</div>';
}
?>