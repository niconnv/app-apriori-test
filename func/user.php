<?php
session_start();
include_once '../db/configdb.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 1) {
    header("Location: ../management_user.php?error=access");
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

switch ($action) {
    case 'add':
        addUser();
        break;
    case 'update':
        updateUser();
        break;
    case 'delete':
        deleteUser();
        break;
    default:
        header("Location: ../management_user.php");
        exit();
}

function addUser() {
    global $conn;
    
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $created_by = $_SESSION['userid'];
    $created_at = date('Y-m-d H:i:s');
    
    // Cek apakah username sudah ada
    $checkQuery = "SELECT iduser FROM user WHERE iduser = '$username'";
    $checkResult = mysqli_query($conn, $checkQuery);
    
    if (mysqli_num_rows($checkResult) > 0) {
        header("Location: ../management_user.php?error=duplicate");
        exit();
    }
    
    // Hash password menggunakan MD5 (sesuai dengan sistem yang ada)
    $hashedPassword = md5($password);
    
    $query = "INSERT INTO user (iduser, passuser, roleuser, is_active, created_at, created_by, updated_at, updated_by) 
              VALUES ('$username', '$hashedPassword', '$role', '$status', '$created_at', '$created_by', '$created_at', '$created_by')";
    
    if (mysqli_query($conn, $query)) {
        header("Location: ../management_user.php?success=add");
    } else {
        header("Location: ../management_user.php?error=add");
    }
    exit();
}

function updateUser() {
    global $conn;
    
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $updated_by = $_SESSION['userid'];
    $updated_at = date('Y-m-d H:i:s');
    
    // Jika password tidak kosong, update password juga
    if (!empty($password)) {
        $hashedPassword = md5($password);
        $query = "UPDATE user SET 
                    passuser = '$hashedPassword', 
                    roleuser = '$role', 
                    is_active = '$status', 
                    updated_at = '$updated_at', 
                    updated_by = '$updated_by' 
                  WHERE iduser = '$username'";
    } else {
        $query = "UPDATE user SET 
                    roleuser = '$role', 
                    is_active = '$status', 
                    updated_at = '$updated_at', 
                    updated_by = '$updated_by' 
                  WHERE iduser = '$username'";
    }
    
    if (mysqli_query($conn, $query)) {
        header("Location: ../management_user.php?success=update");
    } else {
        header("Location: ../management_user.php?error=update");
    }
    exit();
}

function deleteUser() {
    global $conn;
    
    $username = mysqli_real_escape_string($conn, $_GET['username']);
    
    // Tidak boleh menghapus user admin utama
    if ($username == 'admin') {
        header("Location: ../management_user.php?error=delete");
        exit();
    }
    
    // Cek apakah user memiliki transaksi
    $checkTransaksi = "SELECT COUNT(*) as count FROM transaksi WHERE iduser = '$username'";
    $result = mysqli_query($conn, $checkTransaksi);
    $row = mysqli_fetch_assoc($result);
    
    if ($row['count'] > 0) {
        // Jika user memiliki transaksi, ubah status menjadi nonaktif saja
        $query = "UPDATE user SET is_active = '0', updated_at = '" . date('Y-m-d H:i:s') . "', updated_by = '" . $_SESSION['userid'] . "' WHERE iduser = '$username'";
    } else {
        // Jika tidak ada transaksi, hapus user
        $query = "DELETE FROM user WHERE iduser = '$username'";
    }
    
    if (mysqli_query($conn, $query)) {
        header("Location: ../management_user.php?success=delete");
    } else {
        header("Location: ../management_user.php?error=delete");
    }
    exit();
}
?>