<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Apriori Test</title>

  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: hidden;
    }

    /* Animated background particles */
    .bg-animation {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      overflow: hidden;
      z-index: 1;
    }

    .bg-animation span {
      position: absolute;
      display: block;
      width: 20px;
      height: 20px;
      background: rgba(255, 255, 255, 0.1);
      animation: animate 25s linear infinite;
      bottom: -150px;
    }

    .bg-animation span:nth-child(1) {
      left: 25%;
      width: 80px;
      height: 80px;
      animation-delay: 0s;
    }

    .bg-animation span:nth-child(2) {
      left: 10%;
      width: 20px;
      height: 20px;
      animation-delay: 2s;
      animation-duration: 12s;
    }

    .bg-animation span:nth-child(3) {
      left: 70%;
      width: 20px;
      height: 20px;
      animation-delay: 4s;
    }

    .bg-animation span:nth-child(4) {
      left: 40%;
      width: 60px;
      height: 60px;
      animation-delay: 0s;
      animation-duration: 18s;
    }

    .bg-animation span:nth-child(5) {
      left: 65%;
      width: 20px;
      height: 20px;
      animation-delay: 0s;
    }

    .bg-animation span:nth-child(6) {
      left: 75%;
      width: 110px;
      height: 110px;
      animation-delay: 3s;
    }

    .bg-animation span:nth-child(7) {
      left: 35%;
      width: 150px;
      height: 150px;
      animation-delay: 7s;
    }

    .bg-animation span:nth-child(8) {
      left: 50%;
      width: 25px;
      height: 25px;
      animation-delay: 15s;
      animation-duration: 45s;
    }

    .bg-animation span:nth-child(9) {
      left: 20%;
      width: 15px;
      height: 15px;
      animation-delay: 2s;
      animation-duration: 35s;
    }

    .bg-animation span:nth-child(10) {
      left: 85%;
      width: 150px;
      height: 150px;
      animation-delay: 0s;
      animation-duration: 11s;
    }

    @keyframes animate {
      0% {
        transform: translateY(0) rotate(0deg);
        opacity: 1;
        border-radius: 0;
      }
      100% {
        transform: translateY(-1000px) rotate(720deg);
        opacity: 0;
        border-radius: 50%;
      }
    }

    .login-container {
      position: relative;
      z-index: 2;
      width: 100%;
      max-width: 400px;
      padding: 20px;
    }

    .login-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      overflow: hidden;
      transition: all 0.3s ease;
    }

    .login-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 25px 45px rgba(0, 0, 0, 0.15);
    }

    .card-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      text-align: center;
      padding: 30px 20px;
      border: none;
      position: relative;
    }

    .card-header::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.1) 50%, transparent 70%);
      transform: translateX(-100%);
      transition: transform 0.6s;
    }

    .login-card:hover .card-header::before {
      transform: translateX(100%);
    }

    .login-title {
      font-size: 28px;
      font-weight: 600;
      margin: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }

    .login-subtitle {
      font-size: 14px;
      opacity: 0.9;
      margin-top: 5px;
      font-weight: 300;
    }

    .card-body {
      padding: 40px 30px;
    }

    .form-group {
      margin-bottom: 25px;
      position: relative;
    }

    .form-group label {
      font-weight: 500;
      color: #555;
      margin-bottom: 8px;
      font-size: 14px;
    }

    .input-group {
      position: relative;
    }

    .input-group .form-control {
      border: 2px solid #e1e5e9;
      border-radius: 10px;
      padding: 12px 15px 12px 45px;
      font-size: 14px;
      transition: all 0.3s ease;
      background: #f8f9fa;
    }

    .input-group .form-control:focus {
      border-color: #667eea;
      box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
      background: white;
      transform: translateY(-2px);
    }

    .input-group .input-icon {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #adb5bd;
      z-index: 3;
      transition: color 0.3s ease;
    }

    .input-group .form-control:focus + .input-icon {
      color: #667eea;
    }

    .btn-login {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border: none;
      border-radius: 10px;
      padding: 12px;
      font-weight: 600;
      font-size: 16px;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .btn-login::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
      transition: left 0.5s;
    }

    .btn-login:hover::before {
      left: 100%;
    }

    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
    }

    .btn-login:active {
      transform: translateY(0);
    }

    .password-toggle {
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: #adb5bd;
      z-index: 3;
      transition: color 0.3s ease;
    }

    .password-toggle:hover {
      color: #667eea;
    }

    .alert {
      border-radius: 10px;
      border: none;
      margin-bottom: 20px;
    }

    .alert-danger {
      background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
      color: white;
    }

    .form-control.is-invalid {
      border-color: #dc3545;
      animation: shake 0.5s;
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-5px); }
      75% { transform: translateX(5px); }
    }

    .loading {
      display: none;
    }

    .loading .spinner-border {
      width: 20px;
      height: 20px;
      margin-right: 10px;
    }

    @media (max-width: 576px) {
      .login-container {
        padding: 15px;
      }
      
      .card-body {
        padding: 30px 20px;
      }
      
      .login-title {
        font-size: 24px;
      }
    }
  </style>
</head>

<body>
  <!-- Animated Background -->
  <div class="bg-animation">
    <span></span>
    <span></span>
    <span></span>
    <span></span>
    <span></span>
    <span></span>
    <span></span>
    <span></span>
    <span></span>
    <span></span>
  </div>

  <div class="login-container">
    <div class="card login-card">
      <div class="card-header">
        <h3 class="login-title">
          <i class="fas fa-lock"></i>
          LOGIN
        </h3>
        <p class="login-subtitle">Sistem Apriori Test</p>

      </div>
      <div class="card-body">
        <!-- Alert untuk error -->
        <div id="errorAlert" class="alert alert-danger" style="display: none;">
          <i class="fas fa-exclamation-triangle"></i>
          <span id="errorMessage"></span>
        </div>

        <form id="loginForm" action="func/login.php" method="post">
          <div class="form-group">
            <label for="username">Username</label>
            <div class="input-group">
              <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username" required>
              <i class="fas fa-user input-icon"></i>
            </div>
            <div class="invalid-feedback"></div>
          </div>
          
          <div class="form-group">
            <label for="password">Password</label>
            <div class="input-group">
              <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required>
              <i class="fas fa-lock input-icon"></i>
              <i class="fas fa-eye password-toggle" id="togglePassword"></i>
            </div>
            <div class="invalid-feedback"></div>
          </div>
          
          <button type="submit" class="btn btn-primary btn-block btn-login">
            <span class="btn-text">LOGIN</span>
            <span class="loading">
              <span class="spinner-border spinner-border-sm" role="status"></span>
              Memproses...
            </span>
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    $(document).ready(function() {
      // Password toggle functionality
      $('#togglePassword').click(function() {
        const passwordField = $('#password');
        const passwordFieldType = passwordField.attr('type');
        
        if (passwordFieldType === 'password') {
          passwordField.attr('type', 'text');
          $(this).removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
          passwordField.attr('type', 'password');
          $(this).removeClass('fa-eye-slash').addClass('fa-eye');
        }
      });

      // Form validation and submission
      $('#loginForm').on('submit', function(e) {
        e.preventDefault();
        
        const username = $('#username').val().trim();
        const password = $('#password').val().trim();
        
        // Reset previous validation states
        $('.form-control').removeClass('is-invalid');
        $('#errorAlert').hide();
        
        let isValid = true;
        
        // Validate username
        if (username === '') {
          $('#username').addClass('is-invalid');
          $('#username').siblings('.invalid-feedback').text('Username tidak boleh kosong');
          isValid = false;
        } else if (username.length < 3) {
          $('#username').addClass('is-invalid');
          $('#username').siblings('.invalid-feedback').text('Username minimal 3 karakter');
          isValid = false;
        }
        
        // Validate password
        if (password === '') {
          $('#password').addClass('is-invalid');
          $('#password').siblings('.invalid-feedback').text('Password tidak boleh kosong');
          isValid = false;
        } else if (password.length < 3) {
          $('#password').addClass('is-invalid');
          $('#password').siblings('.invalid-feedback').text('Password minimal 3 karakter');
          isValid = false;
        }
        
        if (isValid) {
          // Show loading state
          $('.btn-text').hide();
          $('.loading').show();
          $('.btn-login').prop('disabled', true);
          
          // Submit form after short delay for better UX
          setTimeout(() => {
            this.submit();
          }, 500);
        }
      });

      // Input focus animations
      $('.form-control').on('focus', function() {
        $(this).parent().addClass('focused');
      }).on('blur', function() {
        $(this).parent().removeClass('focused');
      });

      // Check for URL parameters (error messages)
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.has('error')) {
        let errorMessage = '';
        switch (urlParams.get('error')) {
          case 'invalid':
            errorMessage = 'Username atau password salah!';
            break;
          case 'empty':
            errorMessage = 'Username dan password harus diisi!';
            break;
          case 'access':
            errorMessage = 'Akses ditolak!';
            break;
          default:
            errorMessage = 'Terjadi kesalahan, silakan coba lagi!';
        }
        
        $('#errorMessage').text(errorMessage);
        $('#errorAlert').show();
        
        // Auto hide error after 5 seconds
        setTimeout(() => {
          $('#errorAlert').fadeOut();
        }, 5000);
      }

      // Add entrance animation
      $('.login-card').css({
        'opacity': '0',
        'transform': 'translateY(30px)'
      }).animate({
        'opacity': '1'
      }, 600).css('transform', 'translateY(0)');
    });
  </script>

</body>

</html>