<?php
include_once 'db/configdb.php';
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>SKRIPSI - Management User</title>

  <!-- Bootstrap core CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">



  <!-- Custom styles for this template -->
  <link href="assets/css/style.css" rel="stylesheet">

</head>

<body>
  <?php
  include 'header.php';
  
  // Cek apakah user adalah admin
  if ($_SESSION['role'] != 1) {
    header("Location: home.php");
    exit();
  }
  ?>

  <div class="container-fluid">
    <div class="row">
      <?php
      include 'navbar.php';
      ?>

      <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">

        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">Management User</h1>
        </div>

        <?php
        $msg_error = '';
        if (isset($_GET['error'])) {
          switch ($_GET['error']) {
            case 'update':
              $msg_error = 'Gagal Update Data User';
              break;
            case 'add':
              $msg_error = 'Gagal Tambah Data User';
              break;
            case 'delete':
              $msg_error = 'Gagal Hapus Data User';
              break;
            case 'duplicate':
              $msg_error = 'Username sudah ada';
              break;
            case 'access':
              $msg_error = 'Akses ditolak';
              break;
          }
        }

        $msg_success = '';
        if (isset($_GET['success'])) {
          switch ($_GET['success']) {
            case 'update':
              $msg_success = 'Update Data User Berhasil';
              break;
            case 'add':
              $msg_success = 'Tambah Data User Berhasil';
              break;
            case 'delete':
              $msg_success = 'Hapus Data User Berhasil';
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

        // Ambil data user
        $dataUser = '';
        $query = "SELECT u.*, r.namarole FROM user u LEFT JOIN roleuser r ON u.roleuser = r.idrole ORDER BY u.iduser ASC";
        $result = mysqli_query($conn, $query);

        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
          $data[] = $row;
        }

        if ($data) {
          foreach ($data as $row) {
            $statusBadge = $row['is_active'] == '1' ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-danger">Nonaktif</span>';
            $roleBadge = $row['roleuser'] == '1' ? '<span class="badge badge-primary">Admin</span>' : '<span class="badge badge-secondary">User</span>';
            
            $dataUser .= "<tr style='vertical-align: middle;'>
                <td style='text-align: center; vertical-align: middle;'>{$row['iduser']}</td>
                <td style='text-align: center; vertical-align: middle;'>{$roleBadge}</td>
                <td style='text-align: center; vertical-align: middle;'>{$statusBadge}</td>
                <td style='text-align: center; vertical-align: middle;'>{$row['created_at']}</td>
                <td style='text-align: center; vertical-align: middle;'>{$row['updated_at']}</td>
                <td style='text-align: center;'>
                  <div class='btn-group' role='group' aria-label='Basic example'>
                    <button type='button' class='btn btn-warning btn-sm' onclick='editUser(\"{$row['iduser']}\", \"{$row['roleuser']}\", \"{$row['is_active']}\")'>
                      <i class='fas fa-edit'></i> Edit
                    </button>
                    <button type='button' class='btn btn-danger btn-sm' onclick='deleteUser(\"{$row['iduser']}\")'>
                      <i class='fas fa-trash'></i> Hapus
                    </button>
                  </div>
                </td>
              </tr>";
          }
        }
        ?>

        <div class="col-12 mb-3">
          <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addUserModal">
            <i class="fas fa-plus"></i> Tambah User
          </button>
        </div>

        <div class="table-responsive">
          <table id="userTable" class="table table-striped table-bordered table-sm">
            <thead>
              <tr>
                <th style="text-align: center;">USERNAME</th>
                <th style="text-align: center;">ROLE</th>
                <th style="text-align: center;">STATUS</th>
                <th style="text-align: center;">DIBUAT</th>
                <th style="text-align: center;">DIUPDATE</th>
                <th style="text-align: center;">ACTION</th>
              </tr>
            </thead>
            <tbody>
              <?= $dataUser; ?>
            </tbody>
          </table>
        </div>

        <!-- Modal Tambah User -->
        <div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalLabel" aria-hidden="true">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Tambah User Baru</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <form id="addUserForm" action="func/user.php" method="POST">
                <div class="modal-body">
                  <input type="hidden" name="action" value="add">
                  <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                  </div>
                  <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                  </div>
                  <div class="form-group">
                    <label for="role">Role</label>
                    <select class="form-control" id="role" name="role" required>
                      <option value="">Pilih Role</option>
                      <option value="1">Admin</option>
                      <option value="2">User</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label for="status">Status</label>
                    <select class="form-control" id="status" name="status" required>
                      <option value="">Pilih Status</option>
                      <option value="1">Aktif</option>
                      <option value="0">Nonaktif</option>
                    </select>
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

        <!-- Modal Edit User -->
        <div class="modal fade" id="editUserModal" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel" aria-hidden="true">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <form id="editUserForm" action="func/user.php" method="POST">
                <div class="modal-body">
                  <input type="hidden" name="action" value="update">
                  <input type="hidden" id="edit_username" name="username">
                  <div class="form-group">
                    <label for="edit_username_display">Username</label>
                    <input type="text" class="form-control" id="edit_username_display" readonly>
                  </div>
                  <div class="form-group">
                    <label for="edit_password">Password Baru (kosongkan jika tidak ingin mengubah)</label>
                    <input type="password" class="form-control" id="edit_password" name="password">
                  </div>
                  <div class="form-group">
                    <label for="edit_role">Role</label>
                    <select class="form-control" id="edit_role" name="role" required>
                      <option value="1">Admin</option>
                      <option value="2">User</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label for="edit_status">Status</label>
                    <select class="form-control" id="edit_status" name="status" required>
                      <option value="1">Aktif</option>
                      <option value="0">Nonaktif</option>
                    </select>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                  <button type="submit" class="btn btn-primary">Update</button>
                </div>
              </form>
            </div>
          </div>
        </div>

      </main>
    </div>
  </div>

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    $(document).ready(function() {
      // Simple table functionality without DataTables
      console.log('Management User page loaded successfully');
    });

    function editUser(username, role, status) {
      $('#edit_username').val(username);
      $('#edit_username_display').val(username);
      $('#edit_role').val(role);
      $('#edit_status').val(status);
      $('#editUserModal').modal('show');
    }

    function deleteUser(username) {
      if (confirm('Apakah Anda yakin ingin menghapus user "' + username + '"?')) {
        window.location.href = 'func/user.php?action=delete&username=' + encodeURIComponent(username);
      }
    }
  </script>

  <?php
  include 'js.php';
  ?>

</body>

</html>