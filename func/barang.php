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
        case 'update':
            $id    = (int) $_POST['id'];
            $nama  = $_POST['nama'];
            $harga = (int) $_POST['harga'];
            $stok  = (int) $_POST['stok'];
            updatebarang($id, $nama, $harga, $stok);
            break;

        case 'delete':
            $id = (int) $_GET['id'];
            deletebarang($id);
            break;

        case 'add':
            $nama  = $_POST['nama'];
            $harga = (int) $_POST['harga'];
            $stok  = (int) $_POST['stok'];
            addbarang($nama, $harga, $stok);
            break;
    }
}

// UPDATE Barang
function updatebarang($id, $nama, $harga, $stok)
{
    global $conn;

    // Validate inputs
    if (empty($nama)) {
        header('Location: ../masterbarang.php?error=update');
        exit;
    }

    // Check duplicate nama barang (exclude current ID)
    $check_stmt = $conn->prepare("SELECT idbarang FROM master_barang WHERE namabarang = ? AND idbarang != ?");
    if ($check_stmt) {
        $check_stmt->bind_param("si", $nama, $id);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            header('Location: ../masterbarang.php?error=update');
            exit;
        }
        $check_stmt->close();
    }

    $userupdate = $_SESSION['userid'];
    $tgl = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("UPDATE master_barang SET namabarang = ?, harga = ?, stok = ?, updated_by = ?, updated_at = ? WHERE idbarang = ?");

    if (!$stmt) {
        die("Prepare failed: " . $conn->error); // Debug, lihat pesan error MySQL
    }

    $stmt->bind_param("siissi", $nama, $harga, $stok, $userupdate, $tgl, $id);

    if ($stmt->execute()) {
        header('Location: ../masterbarang.php?success=update');
    } else {
        header('Location: ../masterbarang.php?error=update');
    }
    exit;
}

// DELETE Barang
function deletebarang($id)
{
    global $conn;

    try {
        // Validate input
        $id = filter_var($id, FILTER_VALIDATE_INT);
        if ($id === false) {
            throw new Exception("Invalid ID format");
        }

        $stmt = $conn->prepare("DELETE FROM master_barang WHERE idbarang = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("i", $id);

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        if ($stmt->affected_rows === 0) {
            throw new Exception("No record found with ID: " . $id);
        }

        $stmt->close();
        header('Location: ../masterbarang.php?success=delete');
        exit;
    } catch (Exception $e) {
        error_log("Delete barang error: " . $e->getMessage());
        header('Location: ../masterbarang.php?error=delete');
        exit;
    }
}

// ADD Barang
function addbarang($nama, $harga, $stok)
{
    global $conn;

    try {
        // Validate inputs
        if (empty($nama)) {
            throw new Exception("Nama barang cannot be empty");
        }

        // Check duplicate nama barang
        $check_stmt = $conn->prepare("SELECT idbarang FROM master_barang WHERE namabarang = ?");
        if (!$check_stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $check_stmt->bind_param("s", $nama);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            throw new Exception("Nama barang sudah ada");
        }
        $check_stmt->close();

        $harga = filter_var($harga, FILTER_VALIDATE_INT);
        if ($harga === false || $harga < 0) {
            throw new Exception("Invalid harga format");
        }

        $stok = filter_var($stok, FILTER_VALIDATE_INT);
        if ($stok === false || $stok < 0) {
            throw new Exception("Invalid stok format");
        }

        $userupdate = $_SESSION['userid'];
        $tgl = date('Y-m-d H:i:s');

        $stmt = $conn->prepare("INSERT INTO master_barang (namabarang, harga, stok, updated_at, updated_by) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("siiss", $nama, $harga, $stok, $tgl, $userupdate);

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $stmt->close();
        header('Location: ../masterbarang.php?success=add');
        exit;
    } catch (Exception $e) {
        error_log("Add barang error: " . $e->getMessage());
        header('Location: ../masterbarang.php?error=add');
        exit;
    }
}
