<?php
$host     = 'localhost';
$port     = 3306;
$dbname   = 'skripsi_iqrom';
$username = 'root';
$password = 'dbdev';

$conn = @new mysqli($host, $username, $password, $dbname, $port);

// Tampilkan status koneksi
if ($conn->connect_error) {
  die("Koneksi database gagal: (" . $conn->connect_errno . ") " . $conn->connect_error);
}


// echo "✅ Koneksi database berhasil<br>";
$conn->set_charset('utf8mb4');
