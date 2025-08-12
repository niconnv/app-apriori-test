<?php
include_once 'db/configdb.php';
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>SKRIPSI - Tambah Transaksi</title>

  <!-- Bootstrap core CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

  <!-- Add jQuery before DataTables -->
  <script src="https://code.jquery.com/jquery-3.7.1.js"></script>

  <!-- Bootstrap JS -->
  <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
  <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/js/bootstrap.min.js"></script>

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

    .item-row {
      border: 1px solid #dee2e6;
      border-radius: 5px;
      margin-bottom: 10px;
      padding: 15px;
      background-color: #f8f9fa;
    }

    .remove-item {
      position: absolute;
      top: 10px;
      right: 10px;
    }
  </style>

  <!-- Custom styles for this template -->
  <link href="assets/css/style.css" rel="stylesheet">

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

        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">Tambah Transaksi</h1>
        </div>

        <?php
        $msg_error = '';
        if (isset($_GET['error'])) {
          switch ($_GET['error']) {
            case 'add':
              $msg_error = 'Gagal Menambah Transaksi';
              break;
            case 'empty':
              $msg_error = 'Data transaksi tidak boleh kosong';
              break;
            case 'stock':
              $msg_error = 'Stok barang tidak mencukupi';
              break;
          }
        }

        $msg_success = '';
        if (isset($_GET['success'])) {
          switch ($_GET['success']) {
            case 'add':
              $msg_success = 'Transaksi berhasil ditambahkan';
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
        ?>

        <div class="card">
          <div class="card-header">
            <h5>Form Tambah Transaksi</h5>
          </div>
          <div class="card-body">
            <form id="transaksiForm" method="POST" action="func/transaksi.php?action=add">
              
              <!-- Info Pembeli -->
              <div class="row mb-4">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="penerima">Nama Pembeli</label>
                    <input type="text" class="form-control" id="penerima" name="penerima" required>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="tanggal">Tanggal Transaksi</label>
                    <input type="datetime-local" class="form-control" id="tanggal" name="tanggal" value="<?= date('Y-m-d\TH:i') ?>" required>
                  </div>
                </div>
              </div>

              <hr>

              <!-- Items Section -->
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h6>Detail Barang</h6>
                <button type="button" class="btn btn-success btn-sm" id="addItem">
                  <i class="fas fa-plus"></i> Tambah Item
                </button>
              </div>

              <div id="itemsContainer">
                <!-- Item rows will be added here -->
              </div>

              <hr>

              <!-- Summary -->
              <div class="row">
                <div class="col-md-6 offset-md-6">
                  <table class="table table-sm">
                    <tr>
                      <td><strong>Total Item:</strong></td>
                      <td><span id="totalItems">0</span></td>
                    </tr>
                    <tr>
                      <td><strong>Total Harga:</strong></td>
                      <td><strong>Rp <span id="totalHarga">0</span></strong></td>
                    </tr>
                  </table>
                </div>
              </div>

              <!-- Submit Buttons -->
              <div class="text-right">
                <a href="list_transaksi.php" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                  <i class="fas fa-save"></i> Simpan Transaksi
                </button>
              </div>

            </form>
          </div>
        </div>

      </main>
    </div>
  </div>

  <?php
  include 'js.php';
  ?>

  <script>
    $(document).ready(function() {
      let itemCounter = 0;
      
      // Get barang data for dropdown
      const barangData = [
        <?php
        $query = "SELECT * FROM master_barang WHERE stok > 0";
        $result = mysqli_query($conn, $query);
        $barang_options = [];
        while ($row = mysqli_fetch_assoc($result)) {
          $barang_options[] = "{
            id: {$row['idbarang']},
            nama: '" . addslashes($row['namabarang']) . "',
            harga: {$row['harga']},
            stok: {$row['stok']}
          }";
        }
        echo implode(',', $barang_options);
        ?>
      ];

      // Add first item on page load
      addItemRow();

      // Add item button click
      $('#addItem').click(function() {
        addItemRow();
      });

      // Function to add item row
      function addItemRow() {
        itemCounter++;
        
        let barangOptions = '<option value="">Pilih Barang</option>';
        barangData.forEach(function(barang) {
          barangOptions += `<option value="${barang.id}" data-harga="${barang.harga}" data-stok="${barang.stok}">${barang.nama} (Stok: ${barang.stok})</option>`;
        });

        const itemRow = `
          <div class="item-row position-relative" data-item="${itemCounter}">
            <button type="button" class="btn btn-danger btn-sm remove-item" onclick="removeItem(${itemCounter})">
              <i class="fas fa-times"></i>
            </button>
            
            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label>Barang</label>
                  <select class="form-control barang-select" name="items[${itemCounter}][idbarang]" data-item="${itemCounter}" required>
                    ${barangOptions}
                  </select>
                </div>
              </div>
              
              <div class="col-md-2">
                <div class="form-group">
                  <label>Harga</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text">Rp</span>
                    </div>
                    <input type="text" class="form-control harga-display" data-item="${itemCounter}" readonly>
                    <input type="hidden" class="harga-input" name="items[${itemCounter}][harga]" data-item="${itemCounter}">
                  </div>
                </div>
              </div>
              
              <div class="col-md-2">
                <div class="form-group">
                  <label>Jumlah</label>
                  <input type="number" class="form-control jumlah-input" name="items[${itemCounter}][jumlah]" data-item="${itemCounter}" min="1" required>
                </div>
              </div>
              
              <div class="col-md-2">
                <div class="form-group">
                  <label>Stok Tersedia</label>
                  <input type="number" class="form-control stok-display" data-item="${itemCounter}" readonly>
                </div>
              </div>
              
              <div class="col-md-2">
                <div class="form-group">
                  <label>Subtotal</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text">Rp</span>
                    </div>
                    <input type="text" class="form-control subtotal-display" data-item="${itemCounter}" readonly>
                    <input type="hidden" class="subtotal-input" name="items[${itemCounter}][totalharga]" data-item="${itemCounter}">
                  </div>
                </div>
              </div>
            </div>
          </div>
        `;
        
        $('#itemsContainer').append(itemRow);
        updateCalculations();
      }

      // Remove item function
      window.removeItem = function(itemId) {
        if ($('.item-row').length > 1) {
          $(`.item-row[data-item="${itemId}"]`).remove();
          updateCalculations();
        } else {
          alert('Minimal harus ada 1 item dalam transaksi');
        }
      }

      // Handle barang selection change
      $(document).on('change', '.barang-select', function() {
        const itemId = $(this).data('item');
        const selectedOption = $(this).find('option:selected');
        const selectedValue = $(this).val();
        const harga = selectedOption.data('harga') || 0;
        const stok = selectedOption.data('stok') || 0;
        
        // Check for duplicate selection
        if (selectedValue) {
          let isDuplicate = false;
          $('.barang-select').each(function() {
            if ($(this).data('item') !== itemId && $(this).val() === selectedValue) {
              isDuplicate = true;
              return false; // break loop
            }
          });
          
          if (isDuplicate) {
             alert('Barang ini sudah dipilih di item lain. Silakan pilih barang yang berbeda atau gabungkan jumlahnya.');
             $(this).val(''); // Reset selection
             $(`.harga-input[data-item="${itemId}"]`).val('');
             $(`.harga-display[data-item="${itemId}"]`).val('');
             $(`.stok-display[data-item="${itemId}"]`).val('');
             $(`.jumlah-input[data-item="${itemId}"]`).val('').attr('max', '');
             $(`.subtotal-input[data-item="${itemId}"]`).val('');
             $(`.subtotal-display[data-item="${itemId}"]`).val('');
             updateCalculations();
             return;
           }
        }
        
        $(`.harga-input[data-item="${itemId}"]`).val(harga);
         $(`.harga-display[data-item="${itemId}"]`).val(formatRupiah(harga));
         $(`.stok-display[data-item="${itemId}"]`).val(stok);
         $(`.jumlah-input[data-item="${itemId}"]`).attr('max', stok);
         $(`.jumlah-input[data-item="${itemId}"]`).val('');
         $(`.subtotal-input[data-item="${itemId}"]`).val('');
         $(`.subtotal-display[data-item="${itemId}"]`).val('');
         
         updateCalculations();
      });

      // Handle jumlah change
      $(document).on('input', '.jumlah-input', function() {
        const itemId = $(this).data('item');
        const jumlah = parseInt($(this).val());
        const stok = parseInt($(`.stok-display[data-item="${itemId}"]`).val()) || 0;
        
        // Only validate if user has entered a value and stok is available
        if (!isNaN(jumlah) && jumlah > 0 && stok > 0 && jumlah > stok) {
          alert('Jumlah tidak boleh melebihi stok yang tersedia (' + stok + ')');
          $(this).val(stok);
        }
        
        updateItemCalculation(itemId);
      });

      // Update item calculation
      function updateItemCalculation(itemId) {
        const harga = parseInt($(`.harga-input[data-item="${itemId}"]`).val()) || 0;
        const jumlah = parseInt($(`.jumlah-input[data-item="${itemId}"]`).val()) || 0;
        const subtotal = harga * jumlah;
        
        $(`.subtotal-input[data-item="${itemId}"]`).val(subtotal);
        $(`.subtotal-display[data-item="${itemId}"]`).val(formatRupiah(subtotal));
        updateCalculations();
      }

      // Update total calculations
      function updateCalculations() {
        let totalItems = 0;
        let totalHarga = 0;
        let hasValidItems = false;
        
        $('.item-row').each(function() {
          const jumlah = parseInt($(this).find('.jumlah-input').val()) || 0;
          const subtotal = parseInt($(this).find('.subtotal-input').val()) || 0;
          const barangSelected = $(this).find('.barang-select').val();
          
          if (barangSelected && jumlah > 0) {
            hasValidItems = true;
            totalItems += jumlah;
            totalHarga += subtotal;
          }
        });
        
        $('#totalItems').text(totalItems);
        $('#totalHarga').text(formatRupiah(totalHarga));
        
        // Enable/disable submit button
        $('#submitBtn').prop('disabled', !hasValidItems || totalItems === 0);
      }

      // Form validation before submit
      $('#transaksiForm').submit(function(e) {
        let isValid = true;
        let errorMessage = '';
        
        // Check if penerima is filled
        if (!$('#penerima').val().trim()) {
          isValid = false;
          errorMessage = 'Nama pembeli harus diisi';
        }
        
        // Check if at least one item is selected
        let hasItems = false;
        let selectedBarang = [];
        
        $('.item-row').each(function() {
          const barang = $(this).find('.barang-select').val();
          const jumlah = parseInt($(this).find('.jumlah-input').val()) || 0;
          
          if (barang && jumlah > 0) {
            hasItems = true;
            selectedBarang.push(barang);
          }
        });
        
        if (!hasItems) {
          isValid = false;
          errorMessage = 'Minimal harus ada 1 item dalam transaksi';
        }
        
        // Check for duplicate items
        if (isValid) {
          const duplicates = selectedBarang.filter((item, index) => selectedBarang.indexOf(item) !== index);
          if (duplicates.length > 0) {
            isValid = false;
            errorMessage = 'Tidak boleh ada barang yang sama dalam satu transaksi. Silakan gabungkan jumlahnya atau hapus duplikat.';
          }
        }
        
        if (!isValid) {
          e.preventDefault();
          alert(errorMessage);
        }
      });
    });
  </script>

</body>

</html>