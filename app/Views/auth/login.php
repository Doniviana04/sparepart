<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login – CRP Sparepart System</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #ffffff;
      color: #334155;
      overflow-x: hidden;
    }

    /* Sisi Kiri - Area Branding */
    .brand-section {
      background: linear-gradient(145deg, #00223E 0%, #1D976C 100%);
      position: relative;
      overflow: hidden;
    }

    /* Efek Pola Geometris Halus di Latar Belakang (Opsional) */
    .brand-section::before {
      content: '';
      position: absolute;
      top: -50%; left: -50%; width: 200%; height: 200%;
      background: radial-gradient(circle, rgba(255,255,255,0.05) 10%, transparent 10%),
                  radial-gradient(circle, rgba(255,255,255,0.05) 10%, transparent 10%);
      background-size: 60px 60px;
      background-position: 0 0, 30px 30px;
      opacity: 0.3;
      z-index: 1;
    }

    .brand-content {
      position: relative;
      z-index: 2;
    }

    .brand-logo {
      height: 80px;
      object-fit: contain;
      margin-bottom: 2rem;
      filter: drop-shadow(0 4px 6px rgba(0,0,0,0.2));
    }

    /* Sisi Kanan - Area Form */
    .login-section {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .login-container {
      width: 100%;
      max-width: 420px;
      padding: 2rem;
    }

    .login-header h2 {
      font-weight: 700;
      color: #0f172a;
      letter-spacing: -0.5px;
    }

    /* Styling Form Input */
    .form-floating > .form-control {
      border: 1.5px solid #e2e8f0;
      border-radius: 8px;
      padding-right: 40px; /* Ruang untuk icon mata */
    }

    .form-floating > .form-control:focus {
      border-color: #1D976C;
      box-shadow: 0 0 0 4px rgba(29, 151, 108, 0.1);
    }

    .form-floating > label {
      color: #64748b;
    }

    /* Styling Tombol Toggle Password */
    .password-wrapper {
      position: relative;
    }

    .btn-toggle-password {
      position: absolute;
      top: 50%;
      right: 12px;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: #94a3b8;
      cursor: pointer;
      z-index: 10;
      padding: 0 5px;
    }

    .btn-toggle-password:hover {
      color: #1D976C;
    }

    .btn-toggle-password:focus {
      outline: none;
    }

    /* Styling Tombol Login */
    .btn-primary-custom {
      background-color: #00223E;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      padding: 0.85rem;
      transition: all 0.3s ease;
    }

    .btn-primary-custom:hover {
      background-color: #1D976C;
      box-shadow: 0 4px 12px rgba(29, 151, 108, 0.2);
    }

    .footer-text {
      color: #94a3b8;
      font-size: 0.8rem;
    }
  </style>
</head>
<body>

<div class="container-fluid p-0">
  <div class="row g-0">
    
    <div class="col-lg-6 col-md-5 d-none d-md-flex brand-section flex-column justify-content-center align-items-center text-center text-white p-5">
      <div class="brand-content">
        <img src="<?= base_url('images/CBI_logo.png') ?>" alt="CBI Logo" class="brand-logo">
        <h1 class="fw-bold mb-3">CRP Sparepart System</h1>
        <p class="lead opacity-75 fw-light" style="max-width: 400px; margin: 0 auto;">
          Sistem manajemen suku cadang terintegrasi untuk efisiensi operasional dan kontrol inventaris yang lebih baik.
        </p>
      </div>
    </div>

    <div class="col-lg-6 col-md-7 login-section">
      <div class="login-container">
        
        <div class="login-header mb-5">
          <img src="<?= base_url('images/CBI_logo.png') ?>" alt="CBI Logo" class="d-md-none mb-4" style="height: 50px;">
          <h2>Selamat Datang</h2>
          <p class="text-muted">Silakan masukkan kredensial Anda untuk melanjutkan.</p>
        </div>

        <?php if (session()->getFlashdata('error')): ?>
          <div class="alert alert-danger d-flex align-items-center gap-2 py-2" role="alert" style="font-size: 0.9rem; border-radius: 8px;">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div><?= esc(session()->getFlashdata('error')) ?></div>
          </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('info')): ?>
          <div class="alert alert-info d-flex align-items-center gap-2 py-2" role="alert" style="font-size: 0.9rem; border-radius: 8px;">
            <i class="bi bi-info-circle-fill"></i>
            <div><?= esc(session()->getFlashdata('info')) ?></div>
          </div>
        <?php endif; ?>

        <form action="<?= base_url('login') ?>" method="POST" autocomplete="off" novalidate>
          
          <div class="form-floating mb-4">
            <input 
              type="text" 
              class="form-control" 
              id="username" 
              name="username" 
              value="<?= esc(old('username', '')) ?>" 
              placeholder="Username" 
              required 
              autofocus
            >
            <label for="username">Username</label>
          </div>

          <div class="form-floating mb-4 password-wrapper">
            <input 
              type="password" 
              class="form-control" 
              id="password" 
              name="password" 
              placeholder="Password" 
              required
            >
            <label for="password">Password</label>
            
            <button class="btn-toggle-password" type="button" id="togglePassword" tabindex="-1" aria-label="Tampilkan password">
              <i class="bi bi-eye" id="eyeIcon"></i>
            </button>
          </div>

          <div class="d-flex justify-content-between align-items-center mb-4" style="font-size: 0.9rem;">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" value="" id="rememberMe">
              <label class="form-check-label text-muted" for="rememberMe" style="cursor: pointer;">
                Ingat sesi saya
              </label>
            </div>
            </div>

          <button type="submit" class="btn btn-primary-custom w-100 text-white mb-4">
            Masuk ke Sistem
          </button>

        </form>

        <!-- <div class="text-center footer-text mt-5">
          &copy; <?= date('Y') ?> PT. CBI. All rights reserved.<br>
          Versi 1.0.0
        </div> -->

      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script>
  // Toggle Password Visibility khusus untuk Floating Label
  document.getElementById('togglePassword').addEventListener('click', function () {
    const pwd = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');
    if (pwd.type === 'password') {
      pwd.type = 'text';
      icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
      pwd.type = 'password';
      icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
  });
</script>

</body>
</html>