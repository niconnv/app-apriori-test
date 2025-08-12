<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>SKRIPSI</title>

  <!-- Bootstrap core CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

  <!-- Custom styles for this template -->
  <link href="assets/css/style.css" rel="stylesheet">
  
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>

<body>
  <?php
  include 'header.php';
  ?>



  <div class="container-fluid">
    <div class="row">
      <?php
      include 'navbar.php';
      ?>


      <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
        <?php
        include_once 'db/configdb.php';

        // Get user role from database
        $currentUserId = $_SESSION['userid'];
        $queryUserRole = "SELECT roleuser FROM user WHERE iduser = '$currentUserId'";
        $resultUserRole = mysqli_query($conn, $queryUserRole);
        $userRole = '2'; // Default to user role
        if ($resultUserRole && mysqli_num_rows($resultUserRole) > 0) {
          $userRoleData = mysqli_fetch_assoc($resultUserRole);
          $userRole = $userRoleData['roleuser'];
        }
        $isAdmin = ($userRole == '1');

        // Get dashboard statistics
        $today = date('Y-m-d');
        $thisMonth = date('Y-m');

        // Total transaksi hari ini
        if ($isAdmin) {
          // Admin melihat semua penjualan
          $queryTodayTransactions = "SELECT COUNT(*) as total, COALESCE(SUM(totalharga), 0) as revenue FROM transaksi WHERE DATE(created_at) = '$today'";
        } else {
          // User biasa hanya melihat penjualan mereka sendiri
          $queryTodayTransactions = "SELECT COUNT(*) as total, COALESCE(SUM(totalharga), 0) as revenue FROM transaksi WHERE DATE(created_at) = '$today' AND iduser = '$currentUserId'";
        }
        $resultToday = mysqli_query($conn, $queryTodayTransactions);
        $todayStats = mysqli_fetch_assoc($resultToday);

        // Total transaksi bulan ini
        if ($isAdmin) {
          // Admin melihat semua penjualan
          $queryMonthTransactions = "SELECT COUNT(*) as total, COALESCE(SUM(totalharga), 0) as revenue FROM transaksi WHERE DATE_FORMAT(created_at, '%Y-%m') = '$thisMonth'";
        } else {
          // User biasa hanya melihat penjualan mereka sendiri
          $queryMonthTransactions = "SELECT COUNT(*) as total, COALESCE(SUM(totalharga), 0) as revenue FROM transaksi WHERE DATE_FORMAT(created_at, '%Y-%m') = '$thisMonth' AND iduser = '$currentUserId'";
        }
        $resultMonth = mysqli_query($conn, $queryMonthTransactions);
        $monthStats = mysqli_fetch_assoc($resultMonth);

        // Total barang dan stok rendah (hanya untuk admin)
        if ($isAdmin) {
          $queryBarang = "SELECT COUNT(*) as total_barang, SUM(stok) as total_stok FROM master_barang";
          $resultBarang = mysqli_query($conn, $queryBarang);
          $barangStats = mysqli_fetch_assoc($resultBarang);

          $queryStokRendah = "SELECT COUNT(*) as stok_rendah FROM master_barang WHERE stok <= 5";
          $resultStokRendah = mysqli_query($conn, $queryStokRendah);
          $stokRendahStats = mysqli_fetch_assoc($resultStokRendah);
        }

        // Produk terlaris bulan ini (hanya untuk admin)
        if ($isAdmin) {
          $queryTerlaris = "SELECT mb.namabarang, SUM(td.jumlah) as total_terjual, SUM(td.totalharga) as total_pendapatan 
                             FROM transaksi_detail td 
                             JOIN master_barang mb ON td.idbarang = mb.idbarang 
                             JOIN transaksi t ON td.idtransaksi = t.idtransaksi 
                             WHERE DATE_FORMAT(t.created_at, '%Y-%m') = '$thisMonth' 
                             GROUP BY td.idbarang, mb.namabarang 
                             ORDER BY total_terjual DESC 
                             LIMIT 5";
          $resultTerlaris = mysqli_query($conn, $queryTerlaris);
        }

        // Transaksi terbaru
        if ($isAdmin) {
          // Admin melihat semua transaksi
          $queryRecentTransactions = "SELECT t.idtransaksi, t.penerima, t.totalbarang, t.totalharga, t.created_at, t.iduser 
                                       FROM transaksi t 
                                       ORDER BY t.created_at DESC 
                                       LIMIT 5";
        } else {
          // User biasa hanya melihat transaksi mereka sendiri
          $queryRecentTransactions = "SELECT t.idtransaksi, t.penerima, t.totalbarang, t.totalharga, t.created_at, t.iduser 
                                       FROM transaksi t 
                                       WHERE t.iduser = '$currentUserId' 
                                       ORDER BY t.created_at DESC 
                                       LIMIT 5";
        }
        $resultRecent = mysqli_query($conn, $queryRecentTransactions);
        
        // Data penjualan bulanan untuk chart (6 bulan terakhir)
        $salesData = [];
        $salesLabels = [];
        for ($i = 5; $i >= 0; $i--) {
          $monthYear = date('Y-m', strtotime("-$i months"));
          $salesLabels[] = date('M Y', strtotime("-$i months"));
          
          if ($isAdmin) {
            $querySales = "SELECT COALESCE(SUM(totalharga), 0) as monthly_sales FROM transaksi WHERE DATE_FORMAT(created_at, '%Y-%m') = '$monthYear'";
          } else {
            $querySales = "SELECT COALESCE(SUM(totalharga), 0) as monthly_sales FROM transaksi WHERE DATE_FORMAT(created_at, '%Y-%m') = '$monthYear' AND iduser = '$currentUserId'";
          }
          
          $resultSales = mysqli_query($conn, $querySales);
          $salesRow = mysqli_fetch_assoc($resultSales);
          $salesData[] = (float)$salesRow['monthly_sales'];
        }
        
        // Data seluruh transaksi untuk admin (dari awal hingga sekarang)
        if ($isAdmin) {
          $totalSalesData = [];
          $totalSalesLabels = [];
          
          // Ambil semua bulan yang memiliki transaksi
          $queryMonths = "SELECT DISTINCT DATE_FORMAT(created_at, '%Y-%m') as month_year FROM transaksi ORDER BY month_year ASC";
          $resultMonths = mysqli_query($conn, $queryMonths);
          
          while ($monthRow = mysqli_fetch_assoc($resultMonths)) {
            $monthYear = $monthRow['month_year'];
            $totalSalesLabels[] = date('M Y', strtotime($monthYear . '-01'));
            
            $queryTotalSales = "SELECT COALESCE(SUM(totalharga), 0) as total_monthly_sales FROM transaksi WHERE DATE_FORMAT(created_at, '%Y-%m') = '$monthYear'";
            $resultTotalSales = mysqli_query($conn, $queryTotalSales);
            $totalSalesRow = mysqli_fetch_assoc($resultTotalSales);
            $totalSalesData[] = (float)$totalSalesRow['total_monthly_sales'];
          }
        }
        ?>

        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2"><i class="fas fa-home"></i> Dashboard</h1>
          <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group mr-2">
              <span class="badge badge-info"><?= date('d F Y') ?></span>
            </div>
          </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
          <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
              <div class="card-body">
                <div class="row no-gutters align-items-center">
                  <div class="col mr-2">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1"><?= $isAdmin ? 'Penjualan Hari Ini' : 'Penjualan Saya Hari Ini' ?></div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">Rp <?= number_format($todayStats['revenue'], 0, ',', '.') ?></div>
                    <div class="text-xs text-muted"><?= $todayStats['total'] ?> transaksi</div>
                  </div>
                  <div class="col-auto">
                    <i class="fas fa-calendar fa-2x text-gray-300"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
              <div class="card-body">
                <div class="row no-gutters align-items-center">
                  <div class="col mr-2">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1"><?= $isAdmin ? 'Penjualan Bulan Ini' : 'Penjualan Saya Bulan Ini' ?></div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">Rp <?= number_format($monthStats['revenue'], 0, ',', '.') ?></div>
                    <div class="text-xs text-muted"><?= $monthStats['total'] ?> transaksi</div>
                  </div>
                  <div class="col-auto">
                    <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <?php if ($isAdmin): ?>
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Produk</div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $barangStats['total_barang'] ?></div>
                      <div class="text-xs text-muted"><?= number_format($barangStats['total_stok']) ?> total stok</div>
                    </div>
                    <div class="col-auto">
                      <i class="fas fa-boxes fa-2x text-gray-300"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Stok Rendah</div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stokRendahStats['stok_rendah'] ?></div>
                      <div class="text-xs text-muted">produk perlu restock</div>
                    </div>
                    <div class="col-auto">
                      <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php else: ?>
            <!-- Informasi untuk User Biasa -->
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Status Akses</div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800">User</div>
                      <div class="text-xs text-muted">Akses terbatas</div>
                    </div>
                    <div class="col-auto">
                      <i class="fas fa-user fa-2x text-gray-300"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Jam Operasional</div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800">08:00 - 17:00</div>
                      <div class="text-xs text-muted">Senin - Sabtu</div>
                    </div>
                    <div class="col-auto">
                      <i class="fas fa-clock fa-2x text-gray-300"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <!-- Content Row -->
        <div class="row">
          <?php if ($isAdmin): ?>
            <!-- Produk Terlaris (Hanya Admin) -->
            <div class="col-lg-6 mb-4">
              <div class="card shadow mb-4">
                <div class="card-header py-3">
                  <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-chart-bar"></i> Produk Terlaris Bulan Ini</h6>
                </div>
                <div class="card-body">
                  <?php if (mysqli_num_rows($resultTerlaris) > 0): ?>
                    <div class="table-responsive">
                      <table class="table table-sm">
                        <thead>
                          <tr>
                            <th>Produk</th>
                            <th class="text-center">Terjual</th>
                            <th class="text-right">Pendapatan</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php while ($row = mysqli_fetch_assoc($resultTerlaris)): ?>
                            <tr>
                              <td><?= htmlspecialchars($row['namabarang']) ?></td>
                              <td class="text-center"><span class="badge badge-success"><?= $row['total_terjual'] ?></span></td>
                              <td class="text-right">Rp <?= number_format($row['total_pendapatan'], 0, ',', '.') ?></td>
                            </tr>
                          <?php endwhile; ?>
                        </tbody>
                      </table>
                    </div>
                  <?php else: ?>
                    <div class="text-center text-muted py-4">
                      <i class="fas fa-chart-bar fa-3x mb-3"></i>
                      <p>Belum ada data penjualan bulan ini</p>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endif; ?>

          <!-- Transaksi Terbaru -->
          <div class="<?= $isAdmin ? 'col-lg-6' : 'col-lg-12' ?> mb-4">
            <div class="card shadow mb-4">
              <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list"></i> <?= $isAdmin ? 'Transaksi Terbaru' : 'Transaksi Saya' ?></h6>
              </div>
              <div class="card-body">
                <?php if (mysqli_num_rows($resultRecent) > 0): ?>
                  <div class="table-responsive">
                    <table class="table table-sm">
                      <thead>
                        <tr>
                          <th>ID</th>
                          <th>Pembeli</th>
                          <th class="text-center">Item</th>
                          <th class="text-right">Total</th>
                          <?php if ($isAdmin): ?>
                            <th class="text-center">Tanggal</th>
                          <?php endif; ?>
                        </tr>
                      </thead>
                      <tbody>
                        <?php while ($row = mysqli_fetch_assoc($resultRecent)): ?>
                          <tr>
                            <td><small><?= sprintf('%010d', $row['idtransaksi']) ?></small></td>
                            <td><?= htmlspecialchars($row['penerima']) ?></td>
                            <td class="text-center"><?= $row['totalbarang'] ?></td>
                            <td class="text-right">Rp <?= number_format($row['totalharga'], 0, ',', '.') ?></td>
                            <?php if ($isAdmin): ?>
                              <td class="text-center"><small><?= date('d/m/Y', strtotime($row['created_at'])) ?></small></td>
                            <?php endif; ?>
                          </tr>
                        <?php endwhile; ?>
                      </tbody>
                    </table>
                  </div>
                  <div class="text-center mt-3">
                    <a href="list_transaksi.php" class="btn btn-primary btn-sm">
                      <i class="fas fa-eye"></i> Lihat Semua Transaksi
                    </a>
                  </div>
                <?php else: ?>
                  <div class="text-center text-muted py-4">
                    <i class="fas fa-receipt fa-3x mb-3"></i>
                    <p><?= $isAdmin ? 'Belum ada transaksi' : 'Anda belum melakukan transaksi' ?></p>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Sales Chart -->
        <div class="row mb-4">
          <div class="col-12">
            <div class="card shadow">
              <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">
                  <i class="fas fa-chart-line"></i> Grafik Penjualan (6 Bulan Terakhir)
                </h6>
              </div>
              <div class="card-body">
                <div class="chart-container" style="position: relative; height: 400px;">
                  <canvas id="salesChart"></canvas>
                </div>
              </div>
            </div>
          </div>
        </div>

        <?php if ($isAdmin): ?>
        <!-- Total Sales Chart (Admin Only) -->
        <div class="row mb-4">
          <div class="col-12">
            <div class="card shadow">
              <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-success">
                  <i class="fas fa-chart-area"></i> Grafik Total Penjualan Keseluruhan (Seluruh Transaksi)
                </h6>
                <div class="badge badge-success">Admin Only</div>
              </div>
              <div class="card-body">
                <div class="chart-container" style="position: relative; height: 400px;">
                  <canvas id="totalSalesChart"></canvas>
                </div>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="row">
          <!-- Aksi untuk Semua User -->
          <div class="col-lg-3 col-md-6 mb-4">
            <div class="quick-action-card bg-gradient-success">
              <a href="tambah_transaksi.php" class="quick-action-link">
                <div class="quick-action-content">
                  <div class="quick-action-icon">
                    <i class="fas fa-plus-circle"></i>
                  </div>
                  <div class="quick-action-text">
                    <h6>Transaksi Baru</h6>
                    <p class="mb-0">Buat transaksi penjualan</p>
                  </div>
                </div>
              </a>
            </div>
          </div>

          <div class="col-lg-3 col-md-6 mb-4">
            <div class="quick-action-card bg-gradient-primary">
              <a href="list_transaksi.php" class="quick-action-link">
                <div class="quick-action-content">
                  <div class="quick-action-icon">
                    <i class="fas fa-list"></i>
                  </div>
                  <div class="quick-action-text">
                    <h6>Riwayat Transaksi</h6>
                    <p class="mb-0"><?= $isAdmin ? 'Lihat semua transaksi' : 'Lihat transaksi saya' ?></p>
                  </div>
                </div>
              </a>
            </div>
          </div>

          <?php if ($isAdmin): ?>
            <!-- Aksi Khusus Admin -->
            <div class="col-lg-3 col-md-6 mb-4">
              <div class="quick-action-card bg-gradient-info">
                <a href="masterbarang.php" class="quick-action-link">
                  <div class="quick-action-content">
                    <div class="quick-action-icon">
                      <i class="fas fa-boxes"></i>
                    </div>
                    <div class="quick-action-text">
                      <h6>Kelola Produk</h6>
                      <p class="mb-0">Manajemen master barang</p>
                    </div>
                  </div>
                </a>
              </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
              <div class="quick-action-card bg-gradient-warning">
                <a href="masterbarang.php?filter=stok-rendah" class="quick-action-link">
                  <div class="quick-action-content">
                    <div class="quick-action-icon">
                      <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="quick-action-text">
                      <h6>Cek Stok Rendah</h6>
                      <p class="mb-0">Monitor stok produk</p>
                    </div>
                  </div>
                </a>
              </div>
            </div>
          <?php else: ?>
            <!-- Informasi untuk User Biasa -->
            <div class="col-lg-3 col-md-6 mb-4">
              <div class="quick-action-card bg-gradient-secondary">
                <div class="quick-action-content">
                  <div class="quick-action-icon">
                    <i class="fas fa-info-circle"></i>
                  </div>
                  <div class="quick-action-text">
                    <h6>Bantuan</h6>
                    <p class="mb-0">Hubungi admin untuk bantuan</p>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
              <div class="quick-action-card bg-gradient-dark">
                <div class="quick-action-content">
                  <div class="quick-action-icon">
                    <i class="fas fa-clock"></i>
                  </div>
                  <div class="quick-action-text">
                    <h6>Jam Kerja</h6>
                    <p class="mb-0">08:00 - 17:00 WIB</p>
                  </div>
                </div>
              </div>
            </div>
          <?php endif; ?>
        </div>

      </main>
    </div>
  </div>

  <script>
  // Sales Chart
  document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('salesChart').getContext('2d');
    
    const salesChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: <?= json_encode($salesLabels) ?>,
        datasets: [{
          label: 'Penjualan (Rp)',
          data: <?= json_encode($salesData) ?>,
          borderColor: '#4e73df',
          backgroundColor: 'rgba(78, 115, 223, 0.1)',
          borderWidth: 3,
          fill: true,
          tension: 0.4,
          pointBackgroundColor: '#4e73df',
          pointBorderColor: '#ffffff',
          pointBorderWidth: 2,
          pointRadius: 6,
          pointHoverRadius: 8,
          pointHoverBackgroundColor: '#2e59d9',
          pointHoverBorderColor: '#ffffff',
          pointHoverBorderWidth: 3
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: true,
            position: 'top'
          },
          tooltip: {
            backgroundColor: 'rgba(0,0,0,0.8)',
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            borderColor: '#4e73df',
            borderWidth: 1,
            callbacks: {
              label: function(context) {
                return 'Penjualan: Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed.y);
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(0,0,0,0.1)'
            },
            ticks: {
              callback: function(value) {
                return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
              }
            }
          },
          x: {
            grid: {
              color: 'rgba(0,0,0,0.1)'
            }
          }
        },
        interaction: {
          intersect: false,
          mode: 'index'
        }
      }
    });
     
     <?php if ($isAdmin): ?>
     // Total Sales Chart (Admin Only)
     const totalCtx = document.getElementById('totalSalesChart').getContext('2d');
     
     const totalSalesChart = new Chart(totalCtx, {
       type: 'line',
       data: {
         labels: <?= json_encode($totalSalesLabels) ?>,
         datasets: [{
           label: 'Total Penjualan Keseluruhan (Seluruh Transaksi)',
           data: <?= json_encode($totalSalesData) ?>,
           borderColor: '#28a745',
           backgroundColor: 'rgba(40, 167, 69, 0.1)',
           borderWidth: 3,
           fill: true,
           tension: 0.4,
           pointBackgroundColor: '#28a745',
           pointBorderColor: '#ffffff',
           pointBorderWidth: 2,
           pointRadius: 6,
           pointHoverRadius: 8,
           pointHoverBackgroundColor: '#1e7e34',
           pointHoverBorderColor: '#ffffff',
           pointHoverBorderWidth: 3
         }]
       },
       options: {
         responsive: true,
         maintainAspectRatio: false,
         plugins: {
           legend: {
             display: true,
             position: 'top'
           },
           tooltip: {
             backgroundColor: 'rgba(0,0,0,0.8)',
             titleColor: '#ffffff',
             bodyColor: '#ffffff',
             borderColor: '#28a745',
             borderWidth: 1,
             callbacks: {
               label: function(context) {
                 return 'Total Penjualan: Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed.y);
               }
             }
           }
         },
         scales: {
           y: {
             beginAtZero: true,
             grid: {
               color: 'rgba(0,0,0,0.1)'
             },
             ticks: {
               callback: function(value) {
                 return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
               }
             }
           },
           x: {
             grid: {
               color: 'rgba(0,0,0,0.1)'
             }
           }
         },
         interaction: {
           intersect: false,
           mode: 'index'
         }
       }
     });
     <?php endif; ?>
   });
   </script>

  <?php
  include 'js.php';
  ?>

</body>

</html>