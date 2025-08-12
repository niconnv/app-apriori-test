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
  
  <!-- FontAwesome for icons -->
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

        <?php
        if ($_SESSION['role'] == '1') {

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

          $dataBarang = '';
          $query = "SELECT * FROM master_barang";
          $result = mysqli_query($conn, $query);

          $data = [];
          while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
          }
          $databarang = $data;
          if ($data) {
            foreach ($data as $row) {
              // Format harga dengan Rupiah
              $hargaFormatted = 'Rp ' . number_format($row['harga'], 0, ',', '.');
              
              // Stock alert styling
              $stockClass = '';
              $stockIcon = '';
              if ($row['stok'] <= 5) {
                $stockClass = 'text-danger font-weight-bold';
                $stockIcon = '<i class="fas fa-exclamation-triangle"></i> ';
              }
              
              $dataBarang .= "<tr>
                <td style='text-align: center;'>{$row['idbarang']}</td>
                <td>{$row['namabarang']}</td>
                <td>{$hargaFormatted}</td>
                <td class='{$stockClass}'>{$stockIcon}{$row['stok']}</td>
                <td style='text-align: center;'>
                  <div class='btn-group' role='group' aria-label='Basic example'>
                    <button type='button' class='btn btn-primary btn-sm btn-edit' 
                            data-id='{$row['idbarang']}' 
                            data-nama='" . htmlspecialchars($row['namabarang']) . "' 
                            data-harga='" . $row['harga'] . "' 
                            data-stok='" . $row['stok'] . "' 
                            data-toggle='modal' 
                            data-target='#editModal'>
                      Edit
                    </button>
                    <button type='button' class='btn btn-danger btn-sm' onclick='confirmDelete({$row['idbarang']}, \"" . htmlspecialchars($row['namabarang']) . "\")'>
                      Hapus
                    </button>
                  </div>
                </td>
              </tr>";
            }
          }
        ?>

          <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">Master Barang</h1>
          </div>

          <?
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
          ?>

          <div class="col-12 mb-3">
            <div class="row">
              <div class="col-md-6">
                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addModal">
                  <i class="fas fa-plus"></i> Tambah Barang
                </button>
              </div>
              <div class="col-md-6">
                <div class="form-group mb-0">
                  <label for="filterStok" class="sr-only">Filter Stok</label>
                  <select id="filterStok" class="form-control form-control-sm">
                    <option value="">Semua Barang</option>
                    <option value="stok-rendah">Stok Rendah (≤ 5)</option>
                    <option value="stok-habis">Stok Habis (0)</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
          <div class="table-responsive">
            <table id="barangTable" class="table table-sm table-striped table-bordered">
              <thead>
                <tr>
                  <th style="text-align: center;">ID</th>
                  <th style="text-align: center;">NAMA BARANG</th>
                  <th style="text-align: center;">HARGA BARANG</th>
                  <th style="text-align: center;">STOK</th>
                  <th style="text-align: center;">ACTION</th>
                </tr>
              </thead>
              <tbody>
                <?= $dataBarang; ?>
              </tbody>
            </table>
          </div>

          <!-- Modal Edit Barang -->
          <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="editModalLabel">Edit Barang</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
                <form id="editForm" method="POST" action="func/barang.php?action=update">

                  <div class="modal-body">
                    <input type="hidden" id="edit_id" name="id">

                    <div class="form-group">
                      <label for="edit_nama">Nama Barang</label>
                      <input type="text" class="form-control" id="edit_nama" name="nama" required>
                    </div>

                    <div class="form-group">
                      <label for="edit_harga">Harga Barang</label>
                      <input type="number" class="form-control" id="edit_harga" name="harga" min="0" required>
                    </div>

                    <div class="form-group">
                      <label for="edit_stok">Stok</label>
                      <input type="number" class="form-control" id="edit_stok" name="stok" min="0" required>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
          <!-- Modal Add Barang -->
          <div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="addModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="addModalLabel">Tambah Barang</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
                <form id="addForm" method="POST" action="func/barang.php?action=add">
                  <div class="modal-body">
                    <div class="form-group">
                      <label for="add_nama">Nama Barang</label>
                      <input type="text" class="form-control" id="add_nama" name="nama" required>
                    </div>

                    <div class="form-group">
                      <label for="add_harga">Harga Barang</label>
                      <input type="number" class="form-control" id="add_harga" name="harga" min="0" required>
                    </div>

                    <div class="form-group">
                      <label for="add_stok">Stok</label>
                      <input type="number" class="form-control" id="add_stok" name="stok" min="0" required>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        <?php
        } else {
        ?>
          <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">Tidak Ada Akses</h1>
          </div>
        <?php
        }
        ?>
      </main>
    </div>
  </div>

  <?php
  include 'js.php';
  ?>

  <script>
    $(document).ready(function() {
      // Initialize DataTable
      var table = new DataTable('#barangTable', {
        pageLength: 10,
        lengthChange: false
      });
      
      // Check for URL parameter filter
      const urlParams = new URLSearchParams(window.location.search);
      const filterParam = urlParams.get('filter');
      if (filterParam) {
        $('#filterStok').val(filterParam).trigger('change');
      }

      // Filter berdasarkan stok
      $('#filterStok').on('change', function() {
        var filter = $(this).val();
        
        // Clear any existing search
        table.search('').columns().search('').draw();
        
        if (filter === 'stok-rendah') {
          // Hide rows that don't match stok <= 5
          table.rows().every(function(rowIdx, tableLoop, rowLoop) {
            var data = this.data();
            var stockCell = data[3];
            // Extract number from HTML using regex
            var stockMatch = stockCell.match(/>(\d+)<\/td>$/);
            if (!stockMatch) {
              stockMatch = stockCell.match(/(\d+)$/);
            }
            var stockValue = stockMatch ? parseInt(stockMatch[1]) : 999;
            
            if (stockValue > 5) {
              $(this.node()).hide();
            } else {
              $(this.node()).show();
            }
          });
        } else if (filter === 'stok-habis') {
          // Hide rows that don't match stok = 0
          table.rows().every(function(rowIdx, tableLoop, rowLoop) {
            var data = this.data();
            var stockCell = data[3];
            // Extract number from HTML using regex
            var stockMatch = stockCell.match(/>(\d+)<\/td>$/);
            if (!stockMatch) {
              stockMatch = stockCell.match(/(\d+)$/);
            }
            var stockValue = stockMatch ? parseInt(stockMatch[1]) : 999;
            
            if (stockValue !== 0) {
              $(this.node()).hide();
            } else {
              $(this.node()).show();
            }
          });
        } else {
          // Show all rows
          table.rows().every(function() {
            $(this.node()).show();
          });
        }
      });

      // Handle Edit button click
      $(document).on('click', '.btn-edit', function() {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        var harga = $(this).data('harga');
        var stok = $(this).data('stok');

        // Fill modal form with data
        $('#edit_id').val(id);
        $('#edit_nama').val(nama);
        $('#edit_harga').val(harga);
        $('#edit_stok').val(stok);
      });

      // Loading state for forms
      $('#addForm, #editForm').on('submit', function() {
        var submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true);
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');
      });

      // Reset form when modal is closed
      $('#addModal').on('hidden.bs.modal', function() {
        $('#addForm')[0].reset();
        $('#addForm button[type="submit"]').prop('disabled', false).html('Simpan');
      });

      $('#editModal').on('hidden.bs.modal', function() {
        $('#editForm button[type="submit"]').prop('disabled', false).html('Simpan');
      });
    });

    // Confirm delete function
    function confirmDelete(id, nama) {
      if (confirm('Yakin ingin menghapus barang "' + nama + '"?\n\nData yang sudah dihapus tidak dapat dikembalikan.')) {
        window.location.href = 'func/barang.php?action=delete&id=' + id;
      }
    }
  </script>

</body>

</html>