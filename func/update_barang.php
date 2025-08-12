<?php
header('Content-Type: application/json');

// Include database configuration
if (!file_exists('../db/configdb.php')) {
    echo json_encode(['success' => false, 'message' => 'Database configuration file not found']);
    exit;
}

include_once '../db/configdb.php';
include_once 'barang.php';

// Check if connection variable exists and is valid
if (!isset($conn) || $conn === null) {
    $error_message = isset($db_error) ? $db_error : 'Database connection not established';
    echo json_encode(['success' => false, 'message' => $error_message]);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Check if action is update
if (!isset($_POST['action']) || $_POST['action'] !== 'update') {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

// Validate required fields
if (!isset($_POST['id']) || !isset($_POST['nama']) || !isset($_POST['harga']) || !isset($_POST['stok'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Get and sanitize input data
$id = intval($_POST['id']);
$nama = trim($_POST['nama']);
$harga = floatval($_POST['harga']);
$stok = intval($_POST['stok']);

// Validate input data
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

if (empty($nama)) {
    echo json_encode(['success' => false, 'message' => 'Nama barang tidak boleh kosong']);
    exit;
}

if ($harga < 0) {
    echo json_encode(['success' => false, 'message' => 'Harga tidak boleh negatif']);
    exit;
}

if ($stok < 0) {
    echo json_encode(['success' => false, 'message' => 'Stok tidak boleh negatif']);
    exit;
}

try {
    // Call the updatebarang function
    $result = updatebarang($id, $nama, $harga, $stok);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Data berhasil diupdate']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengupdate data atau tidak ada perubahan']);
    }
} catch (Exception $e) {
    error_log('Error in update_barang.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
}
?>