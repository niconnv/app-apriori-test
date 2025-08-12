<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../db/configdb.php';
session_start();

// Pastikan koneksi berhasil
if (!isset($conn) || $conn === null) {
    if (isset($db_error)) {
        die('Database Error: ' . $db_error);
    } else {
        die('Error: Database connection not established!');
    }
}

// Tangani request action
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'add':
            addTransaksi();
            break;
        case 'delete':
            $id = (int) $_GET['id'];
            deleteTransaksi($id);
            break;
    }
}

// ADD Transaksi
function addTransaksi()
{
    global $conn;

    try {
        // Validate inputs
        if (empty($_POST['penerima'])) {
            throw new Exception("Nama penerima tidak boleh kosong");
        }

        if (empty($_POST['items']) || !is_array($_POST['items'])) {
            throw new Exception("Data item transaksi tidak valid");
        }

        $penerima = trim($_POST['penerima']);
        $tanggal = $_POST['tanggal'] ?? date('Y-m-d H:i:s');
        $created_by = $_SESSION['userid'];
        $items = $_POST['items'];

        // Validate and calculate totals
        $totalBarang = 0;
        $totalHarga = 0;
        $validItems = [];

        foreach ($items as $item) {
            if (empty($item['idbarang']) || empty($item['jumlah']) || $item['jumlah'] <= 0) {
                continue; // Skip empty items
            }

            $idbarang = (int) $item['idbarang'];
            $jumlah = (int) $item['jumlah'];
            $harga = (int) $item['harga'];
            $totalharga = (int) $item['totalharga'];

            // Verify barang exists and has sufficient stock
            $stmt = $conn->prepare("SELECT namabarang, harga, stok FROM master_barang WHERE idbarang = ?");
            $stmt->bind_param("i", $idbarang);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("Barang dengan ID {$idbarang} tidak ditemukan");
            }
            
            $barang = $result->fetch_assoc();
            
            if ($barang['stok'] < $jumlah) {
                throw new Exception("Stok {$barang['namabarang']} tidak mencukupi. Stok tersedia: {$barang['stok']}");
            }
            
            // Verify price calculation
            if ($harga != $barang['harga'] || $totalharga != ($harga * $jumlah)) {
                throw new Exception("Terjadi kesalahan dalam perhitungan harga");
            }

            $validItems[] = [
                'idbarang' => $idbarang,
                'jumlah' => $jumlah,
                'harga' => $harga,
                'totalharga' => $totalharga,
                'namabarang' => $barang['namabarang']
            ];

            $totalBarang += $jumlah;
            $totalHarga += $totalharga;
        }

        if (empty($validItems)) {
            throw new Exception("Tidak ada item valid dalam transaksi");
        }

        // Start transaction
        $conn->autocommit(FALSE);

        try {
            // Insert into transaksi table
            $stmt = $conn->prepare("INSERT INTO transaksi (iduser, totalbarang, totalharga, penerima, created_at, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("siisss", $created_by, $totalBarang, $totalHarga, $penerima, $tanggal, $created_by);
            
            if (!$stmt->execute()) {
                throw new Exception("Gagal menyimpan data transaksi: " . $stmt->error);
            }

            $idtransaksi = $conn->insert_id;

            // Insert into transaksi_detail table and update stock
            foreach ($validItems as $item) {
                // Insert detail
                $stmt = $conn->prepare("INSERT INTO transaksi_detail (idtransaksi, idbarang, jumlah, harga, totalharga, created_at, created_by, updated_at, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iiiisssss", $idtransaksi, $item['idbarang'], $item['jumlah'], $item['harga'], $item['totalharga'], $tanggal, $created_by, $tanggal, $created_by);
                
                if (!$stmt->execute()) {
                    throw new Exception("Gagal menyimpan detail transaksi untuk {$item['namabarang']}: " . $stmt->error);
                }

                // Update stock
                $stmt = $conn->prepare("UPDATE master_barang SET stok = stok - ?, updated_at = ?, updated_by = ? WHERE idbarang = ?");
                $stmt->bind_param("issi", $item['jumlah'], $tanggal, $created_by, $item['idbarang']);
                
                if (!$stmt->execute()) {
                    throw new Exception("Gagal mengupdate stok untuk {$item['namabarang']}: " . $stmt->error);
                }
            }

            // Commit transaction
            $conn->commit();
            $conn->autocommit(TRUE);

            header('Location: ../tambah_transaksi.php?success=add');
            exit;

        } catch (Exception $e) {
            // Rollback transaction
            $conn->rollback();
            $conn->autocommit(TRUE);
            throw $e;
        }

    } catch (Exception $e) {
        error_log("Add transaksi error: " . $e->getMessage());
        
        // Determine error type for better user feedback
        if (strpos($e->getMessage(), 'stok') !== false) {
            header('Location: ../tambah_transaksi.php?error=stock&msg=' . urlencode($e->getMessage()));
        } elseif (strpos($e->getMessage(), 'kosong') !== false || strpos($e->getMessage(), 'valid') !== false) {
            header('Location: ../tambah_transaksi.php?error=empty&msg=' . urlencode($e->getMessage()));
        } else {
            header('Location: ../tambah_transaksi.php?error=add&msg=' . urlencode($e->getMessage()));
        }
        exit;
    }
}

// DELETE Transaksi
function deleteTransaksi($id)
{
    global $conn;

    try {
        // Validate input
        $id = filter_var($id, FILTER_VALIDATE_INT);
        if ($id === false) {
            throw new Exception("Invalid ID format");
        }

        // Start transaction
        $conn->autocommit(FALSE);

        try {
            // Get transaction details before deletion to restore stock
            $stmt = $conn->prepare("SELECT td.idbarang, td.jumlah FROM transaksi_detail td WHERE td.idtransaksi = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $itemsToRestore = [];
            while ($row = $result->fetch_assoc()) {
                $itemsToRestore[] = $row;
            }

            // Delete transaction details first (foreign key constraint)
            $stmt = $conn->prepare("DELETE FROM transaksi_detail WHERE idtransaksi = ?");
            $stmt->bind_param("i", $id);
            
            if (!$stmt->execute()) {
                throw new Exception("Gagal menghapus detail transaksi: " . $stmt->error);
            }

            // Delete main transaction
            $stmt = $conn->prepare("DELETE FROM transaksi WHERE idtransaksi = ?");
            $stmt->bind_param("i", $id);
            
            if (!$stmt->execute()) {
                throw new Exception("Gagal menghapus transaksi: " . $stmt->error);
            }

            if ($stmt->affected_rows === 0) {
                throw new Exception("Transaksi dengan ID {$id} tidak ditemukan");
            }

            // Restore stock for each item
            $updated_by = $_SESSION['userid'];
            $updated_at = date('Y-m-d H:i:s');
            
            foreach ($itemsToRestore as $item) {
                $stmt = $conn->prepare("UPDATE master_barang SET stok = stok + ?, updated_at = ?, updated_by = ? WHERE idbarang = ?");
                $stmt->bind_param("issi", $item['jumlah'], $updated_at, $updated_by, $item['idbarang']);
                
                if (!$stmt->execute()) {
                    throw new Exception("Gagal mengembalikan stok untuk barang ID {$item['idbarang']}: " . $stmt->error);
                }
            }

            // Commit transaction
            $conn->commit();
            $conn->autocommit(TRUE);

            header('Location: ../list_transaksi.php?success=delete');
            exit;

        } catch (Exception $e) {
            // Rollback transaction
            $conn->rollback();
            $conn->autocommit(TRUE);
            throw $e;
        }

    } catch (Exception $e) {
        error_log("Delete transaksi error: " . $e->getMessage());
        header('Location: ../list_transaksi.php?error=delete&msg=' . urlencode($e->getMessage()));
        exit;
    }
}
?>