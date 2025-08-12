<?php
session_start();

// Check if session exists
if (!isset($_SESSION['userid'])) {
  header("Location: func/logout.php");
  exit();
}

?>

<nav class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
  <a class="navbar-brand col-md-3 col-lg-2 mr-0 px-3" href="#">SISTEM APRIORI TEST</a>
  <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-toggle="collapse" data-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  <h5 class="w-100 mx-3" style="color:aquamarine">User Login : <?php echo $_SESSION['userid']; ?></h5>
  <ul class="navbar-nav px-3">
    <li class="nav-item text-nowrap">
      <a class="nav-link btn btn-sm btn-danger px-2 text-white" href="func/logout.php">Sign out</a>


    </li>
  </ul>
</nav>