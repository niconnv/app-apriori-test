<?php
// include 'func/barang.php';
include_once 'db/configdb.php';
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>SKRIPSI</title>

  <!-- Bootstrap core CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

  <!-- Add jQuery before DataTables -->
  <script src="https://code.jquery.com/jquery-3.7.1.js"></script>

  <!-- DataTables CSS -->
  <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.css">
  <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/2.3.2/css/dataTables.bootstrap4.css">

  <!-- DataTables JS -->
  <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
  <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/js/bootstrap.min.js"></script>
  <script type="text/javascript" src="https://cdn.datatables.net/2.3.2/js/dataTables.js"></script>
  <script type="text/javascript" src="https://cdn.datatables.net/2.3.2/js/dataTables.bootstrap4.js"></script>


  <style>
    .bd-placeholder-img {
      font-size: 1.125rem;
      text-anchor: middle;
      -webkit-user-select: none;
      -moz-user-select: none;
      -ms-user-select: none;
      user-select: none;
    }

    @media (min-width: 768px) {
      .bd-placeholder-img-lg {
        font-size: 3.5rem;
      }
    }

    /* Custom styles for transaction detail modal */
    #detailModal .modal-dialog {
      max-width: 90%;
    }
    
    #detailModal .table th {
      background-color: #f8f9fa;
      font-weight: 600;
    }
    
    #detailModal .table-borderless td {
      padding: 0.25rem 0.5rem;
    }
    
    .transaction-header {
       background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
       color: white;
       padding: 1rem;
       border-radius: 0.5rem;
       margin-bottom: 1rem;
     }
     
     /* Receipt modal styles */
     #receiptModal .modal-dialog {
       max-width: 400px;
     }
     
     .receipt-container {
       background: white;
       border: 2px dashed #333;
       border-radius: 5px;
       padding: 15px;
       font-family: 'Courier New', monospace;
       line-height: 1.4;
     }
     
     @media print {
       .modal-header, .modal-footer {
         display: none !important;
       }
       
       .modal-body {
         padding: 0 !important;
       }
       
       .receipt-container {
         border: none !important;
         box-shadow: none !important;
         margin: 0 !important;
         padding: 10px !important;
       }
     }
  </style>


  <!-- Custom styles for this template -->
  <link href="assets/css/style.css" rel="stylesheet">

</head>

<body>
  <?php
  include 'header.php';
  session_start();

  ?>

  <div class="container-fluid">
    <div class="row">
      <?php
      include 'navbar.php';
      ?>

      <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">

        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">List Transaksi</h1>
        </div>


        <?php
        $msg_error = '';
        if (isset($_GET['error'])) {
          switch ($_GET['error']) {
            case 'update':
              $msg_error = 'Gagal Update Data';
              break;
            case 'add':
              $msg_error = 'Gagal Add Data';
              break;
            case 'delete':
              $msg_error = 'Gagal Hapus Data';
              break;
          }
        }

        // $_GET['success']
        $msg_success = '';
        if (isset($_GET['success'])) {
          switch ($_GET['success']) {
            case 'update':
              $msg_success = 'Update Data Berhasil';
              break;
            case 'add':
              $msg_success = 'Add Data Berhasil';
              break;
            case 'delete':
              $msg_success = 'Hapus Data Berhasil';
              break;
          }
        }

        if ($msg_error) {
          echo '<div class="alert alert-danger" role="alert">
                ' . $msg_error . '
              </div>';
        }
        if ($msg_success) {
          echo '<div class="alert alert-success" role="alert">
                ' . $msg_success . '
              </div>';
        }


        $dataBarang = '';
        // Cek role user untuk menentukan query
        if ($_SESSION['role'] == 1) {
          // Admin: tampilkan semua transaksi
          $query = "SELECT * FROM transaksi ORDER BY idtransaksi DESC";
        } else {
          // User biasa: hanya tampilkan transaksi user tersebut
          $userid = $_SESSION['userid'];
          $query = "SELECT * FROM transaksi WHERE iduser = '$userid' ORDER BY idtransaksi DESC";
        }
        $result = mysqli_query($conn, $query);

        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
          $data[] = $row;
        }
        $databarang = $data;
        if ($data) {

          
          foreach ($data as $row) {
            // delete hanya untuk admin
            $btnDelete = '';
            if ($_SESSION['role'] == 1) {
              $btnDelete .= "
                <button type='button' class='btn btn-danger btn-sm' onclick='deleteTransaction({$row['idtransaksi']})'>
                  <i class='fas fa-trash'></i> Hapus
                </button>
              ";
            }
            // Format harga dengan Rupiah
            $hargaFormatted = 'Rp ' . number_format($row['totalharga'], 0, ',', '.');
            
            // Kolom USER hanya untuk admin
            $userColumn = '';
            if ($_SESSION['role'] == 1) {
              $userColumn = "<td style='text-align: center; vertical-align: middle;'><h6><span class='badge badge-primary'>{$row['iduser']}</span></h6></td>";
            }
            
            $dataBarang .= "<tr style='vertical-align: middle;'>
                <td style='text-align: center; vertical-align: middle;'>{$row['idtransaksi']}</td>
                <td style='text-align: center; vertical-align: middle;'>{$row['totalbarang']}</td>
                <td style='text-align: center; vertical-align: middle;'>{$hargaFormatted}</td>
                <td style='text-align: center; vertical-align: middle;'>{$row['penerima']}</td>
                <td style='text-align: center; vertical-align: middle;'>{$row['created_at']}</td>
                {$userColumn}
                <td style='text-align: center;'>
                  <div class='btn-group' role='group' aria-label='Basic example'>
                    <button type='button' class='btn btn-info btn-sm' onclick='viewTransactionDetail({$row['idtransaksi']})'>
                      <i class='fas fa-eye'></i> Detail
                    </button>
                    <button type='button' class='btn btn-success btn-sm' onclick='viewReceipt({$row['idtransaksi']})'>
                      <i class='fas fa-receipt'></i> Struk
                    </button>
                    {$btnDelete}
                  </div>
                </td>
              </tr>";
          }
        }

        ?>
        <div class="col-12">
          <a href="tambah_transaksi.php" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Tambah Transaksi
          </a>
        </div>
        <div class="table-responsive">
          <table id="barangTable" class="table table-striped table-bordered table-sm" >

            <thead>
              <tr>
                <th style="text-align: center;">ID TRANSAKSI</th>
                <th style="text-align: center;">JUMLAH ITEM</th>
                <th style="text-align: center;">TOTAL HARGA</th>
                <th style="text-align: center;">NAMA PEMBELI</th>
                <th style="text-align: center;">TANGGAL</th>
                <?php if ($_SESSION['role'] == 1): ?>
                <th style="text-align: center;">USER</th>
                <?php endif; ?>
                <th style="text-align: center;">ACTION</th>
              </tr>
            </thead>
            <tbody>
              <?= $dataBarang; ?>
            </tbody>
          </table>
        </div>

        <!-- Modal Detail Transaksi -->
        <div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-labelledby="detailModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="detailModalLabel">Detail Transaksi</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <div id="detailContent">
                  <!-- Content will be loaded here -->
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Modal Receipt/Struk -->
        <div class="modal fade" id="receiptModal" tabindex="-1" role="dialog" aria-labelledby="receiptModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="receiptModalLabel">Struk Transaksi</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body" id="receiptContent">
                <!-- Receipt content will be loaded here -->
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="printReceipt()">
                  <i class="fas fa-print"></i> Cetak
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <!-- Feather Icons -->
  <script src="https://cdn.jsdelivr.net/npm/feather-icons@4.28.0/dist/feather.min.js"></script>

  <script>
    $(document).ready(function() {

      // Initialize DataTable
      new DataTable('#barangTable', {
        pageLength: 10,
        lengthChange: false
      })
    });

    // Function to view transaction detail
    function viewTransactionDetail(idtransaksi) {
      // Show loading in modal
      $('#detailContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
      $('#detailModal').modal('show');
      
      // Fetch transaction detail via AJAX
      $.ajax({
        url: 'func/get_transaction_detail.php',
        type: 'GET',
        data: { id: idtransaksi },
        success: function(response) {
          $('#detailContent').html(response);
        },
        error: function() {
          $('#detailContent').html('<div class="alert alert-danger">Gagal memuat detail transaksi.</div>');
        }
      });
    }

    // Function to delete transaction
    function deleteTransaction(idtransaksi) {
      if (confirm('Apakah Anda yakin ingin menghapus transaksi ini? Stok barang akan dikembalikan.')) {
        window.location.href = 'func/transaksi.php?action=delete&id=' + idtransaksi;
      }
    }

    // Function to view receipt
    function viewReceipt(idtransaksi) {
      // Show loading in modal
      $('#receiptContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
      $('#receiptModal').modal('show');
      
      // Fetch receipt data via AJAX
      $.ajax({
        url: 'func/get_receipt.php',
        type: 'GET',
        data: { id: idtransaksi },
        success: function(response) {
          $('#receiptContent').html(response);
        },
        error: function() {
          $('#receiptContent').html('<div class="alert alert-danger">Gagal memuat struk transaksi.</div>');
        }
      });
    }

    // Function to print receipt
    function printReceipt() {
      var printContent = document.getElementById('receiptContent').innerHTML;
      var originalContent = document.body.innerHTML;
      
      document.body.innerHTML = printContent;
      window.print();
      document.body.innerHTML = originalContent;
      
      // Reload the page to restore functionality
      location.reload();
    }
  </script>

</body>

</html>