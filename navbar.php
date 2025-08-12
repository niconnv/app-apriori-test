<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
  <div class="sidebar-sticky">
    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
      <span>Menu</span>
    </h6>
    <ul class="nav flex-column">
      <li class="nav-item">
        <a class="nav-link" href="home.php">
          <span data-feather="home"></span>
          Home <span class="sr-only">(current)</span>
        </a>
      </li>

      <?php
      if ($_SESSION['role'] == '1') {
      ?>

        <li class="nav-item">
          <a class="nav-link" href="masterbarang.php">
            <span data-feather="file-text"></span>
            Master Barang <span class="sr-only">(current)</span>
          </a>
        </li>

      <?php
      }
      ?>

      <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
        <span>Transaksi</span>
      </h6>

      <li class="nav-item">
        <a class="nav-link" href="list_transaksi.php">
          <span data-feather="layers"></span>
          List Transaksi <span class="sr-only">(current)</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="tambah_transaksi.php">
          <span data-feather="layers"></span>
          Tambah Transaksi <span class="sr-only">(current)</span>
        </a>
      </li>


    </ul>

    <!-- khusus admin -->

    <?php
    if ($_SESSION['role'] == '1') {
    ?>

      <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
        <span>Management</span>
      </h6>
      <ul class="nav flex-column mb-2">
        <li class="nav-item">
          <a class="nav-link" href="management_user.php">
            <span data-feather="users"></span>
            Management User
          </a>
        </li>
      </ul>

      <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
        <span>Report</span>
      </h6>
      <ul class="nav flex-column mb-2">
        <li class="nav-item">
          <a class="nav-link" href="analisa_data.php">
            <span data-feather="bar-chart-2"></span>
            Analisa Data Apriori
          </a>
        </li>
      </ul>
    <?php
    }
    ?>
  </div>
</nav>