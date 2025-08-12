<?php

// Get login credentials from POST
$userid = $_POST['username'] ?? '';
$userpass = $_POST['password'] ?? '';
// Connect to database
require_once '../db/configdb.php';

// Test database connection and handle errors
try {
  if (!$conn) {
    throw new Exception(mysqli_connect_error());
  }
} catch (Exception $e) {
  die("Connection failed: " . $e->getMessage());
}
  
$hashedPassword = md5($userpass);
$sql = "SELECT * FROM user 
        WHERE iduser = '$userid' 
        AND passuser = '$hashedPassword' 
        AND is_active = '1'";

// Execute query directly using mysqli_query
$result = mysqli_query($conn, $sql);

// kalo dapet hasilnya check apakah admin ? 
if (mysqli_num_rows($result) > 0) {
  $row = mysqli_fetch_array($result);
  // Start session and set user data
  session_start();
  // Store essential user information in session
  $_SESSION['userid'] = $row['iduser'];
  $_SESSION['role'] = $row['roleuser'];
  header('Location: ../home.php');
} else {
  echo "Login failed. Please check your credentials.";
  header('Location: ../index.php');
}
