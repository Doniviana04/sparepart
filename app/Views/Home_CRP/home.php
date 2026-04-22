<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard – CRP Sparepart System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    :root {
      --sidebar-w: 240px;
      --topbar-h: 60px;
      --primary: #005689;
      --primary-dark: #003f66;
      --teal: #0f766e;
    }

    body {
      font-family: system-ui, sans-serif;
      background: #f0f4f8;
      margin: 0;
    }

    /* ── SIDEBAR ── */
    .sidebar {
      position: fixed;
      top: 0;
      left: 0;
      width: var(--sidebar-w);
      height: 100vh;
      background: linear-gradient(180deg, var(--primary-dark) 0%, var(--primary) 100%);
      color: #fff;
      display: flex;
      flex-direction: column;
      z-index: 100;
      box-shadow: 4px 0 16px rgba(0, 0, 0, .18);
      transition: transform .3s ease;
    }

    .sidebar-brand {
      padding: 1.25rem 1.25rem 1rem;
      border-bottom: 1px solid rgba(255, 255, 255, .12);  
      text-decoration: none;
      color: inherit;
    }
    .sidebar-brand .brand-icon {
      font-size: 1.9rem;
    }
    .brand-name {
      font-size: .85rem;
      font-weight: 700;
      letter-spacing: .4px;
      line-height: 1.2;
    }
    .brand-sub {
      font-size: .7rem;
      opacity: .65;
    }

    .sidebar-nav {
      flex: 1;
      padding: 1rem 0;
      overflow-y: auto;
    }
    .nav-section {
      font-size: .65rem;
      text-transform: uppercase;
      letter-spacing: 1px;
      opacity: .5;
      padding: .75rem 1.25rem .3rem;
    }
    .sidebar-link {
      display: flex;
      align-items: center;
      gap: .7rem;
      padding: .65rem 1.25rem;
      color: rgba(255, 255, 255, .8);
      text-decoration: none;
      font-size: .875rem;
      transition: background .2s, color .2s;
      border-left: 3px solid transparent;
    }
    .sidebar-link:hover,
    .sidebar-link.active {
      background: rgba(255, 255, 255, .12);
      color: #fff;
      border-left-color: #7dd3fc;
    }
    .sidebar-link i { font-size: 1.05rem; width: 20px; text-align: center; }

    .sidebar-footer {
      padding: 1rem 1.25rem;
      border-top: 1px solid rgba(255, 255, 255, .12);
    }

    /* ── TOPBAR ── */
    .topbar {
      position: fixed;
      top: 0;
      left: var(--sidebar-w);
      right: 0;
      height: var(--topbar-h);
      background: #fff;
      border-bottom: 1px solid #e2e8f0;
      display: flex;
      align-items: center;
      padding: 0 1.5rem;
      z-index: 99;
      box-shadow: 0 2px 8px rgba(0, 0, 0, .05);
    }
    .topbar-title {
      font-weight: 600;
      color: var(--primary-dark);
      font-size: 1rem;
    }
    .topbar .user-info small { font-size: .74rem; }

    /* ── MAIN CONTENT ── */
    .main-content {
      margin-left: var(--sidebar-w);
      margin-top: var(--topbar-h);
      padding: 1.75rem;
      min-height: calc(100vh - var(--topbar-h));
    }

    /* ── WELCOME BANNER ── */
    .welcome-banner {
      background: linear-gradient(90deg, var(--primary-dark), var(--teal));
      color: #fff;
      border-radius: 14px;
      padding: 1.5rem 2rem;
      margin-bottom: 1.75rem;
      box-shadow: 0 6px 20px rgba(0, 63, 102, .25);
    }
    .welcome-banner .time-badge {
      background: rgba(255, 255, 255, .15);
      border-radius: 999px;
      padding: .25rem .75rem;
      font-size: .78rem;
    }

    /* ── MENU CARDS ── */
    .menu-card {
      border: none;
      border-radius: 14px;
      box-shadow: 0 4px 18px rgba(0, 0, 0, .07);
      transition: transform .2s, box-shadow .2s;
      text-decoration: none;
      color: inherit;
      display: block;
      height: 100%;
    }
    .menu-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 10px 28px rgba(0, 0, 0, .13);
      color: inherit;
    }
    .menu-card .card-icon {
      font-size: 2.8rem;
    }
    .menu-card.card-crp  { border-top: 5px solid var(--primary); }
    .menu-card.card-user { border-top: 5px solid var(--teal); }
    .menu-card.card-disabled {
      opacity: .42;
      pointer-events: none;
      filter: grayscale(.8);
    }
    .role-badge {
      font-size: .7rem;
      padding: .2rem .55rem;
      border-radius: 999px;
    }

    /* ── RESPONSIVE ── */
    @media (max-width: 767.98px) {
      .sidebar { transform: translateX(-100%); }
      .sidebar.open { transform: translateX(0); }
      .topbar { left: 0; }
      .main-content { margin-left: 0; }
    }
    #sidebarToggle { display: none; }
    @media (max-width: 767.98px) {
      #sidebarToggle { display: inline-flex; }
    }
  </style>
</head>
<body>

<?php
  $jabatan      = (int) (session()->get('kode_jabatan') ?? session()->get('level') ?? 0);
  $name         = session()->get('name') ?? 'User';
  $username     = session()->get('username') ?? '';
  $hasCrpAccess = (bool) (session()->get('can_access_crp') ?? false);
  $displayRole  = trim((string) (session()->get('jabatan') ?? ''));
  if ($displayRole === '') {
    $displayRole = 'Pengguna';
  }
?>

<!-- ── SIDEBAR ── -->
<aside class="sidebar" id="sidebar">
  <a href="<?= base_url('home') ?>" class="sidebar-brand d-flex align-items-center gap-2 text-decoration-none">
    <i class="bi bi-gear-wide-connected brand-icon text-info"></i>
    <div>
      <div class="brand-name">CRP Sparepart</div>
      <div class="brand-sub">Management System</div>
    </div>
  </a>

  <nav class="sidebar-nav">
    <div class="nav-section">Menu Utama</div>
    <a href="<?= base_url('home') ?>" class="sidebar-link active">
      <i class="bi bi-house-door-fill"></i> Beranda
    </a>

    <?php if ($hasCrpAccess): ?>
    <div class="nav-section">CRP</div>
    <a href="<?= base_url('crp') ?>" class="sidebar-link">
      <i class="bi bi-clipboard-data-fill"></i> CRP Dashboard
    </a>
    <a href="<?= base_url('history-admin') ?>" class="sidebar-link">
      <i class="bi bi-clock-history"></i> History Admin
    </a>
    <?php endif; ?>

    <div class="nav-section">Monitor</div>
    <a href="<?= base_url('monitor-user') ?>" class="sidebar-link">
      <i class="bi bi-eye-fill"></i> Monitor User
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="d-flex align-items-center gap-2 mb-2">
      <i class="bi bi-person-circle fs-5 opacity-75"></i>
      <div style="line-height:1.15;">
        <div class="fw-semibold" style="font-size:.82rem;"><?= esc($name) ?></div>
        <div style="font-size:.7rem; opacity:.6;"><?= esc($username) ?></div>
      </div>
    </div>
    <a href="<?= base_url('logout') ?>" class="btn btn-sm btn-outline-light w-100">
      <i class="bi bi-box-arrow-right me-1"></i> Logout
    </a>
  </div>
</aside>

<!-- ── TOPBAR ── -->
<header class="topbar">
  <button class="btn btn-sm btn-outline-secondary me-3" id="sidebarToggle">
    <i class="bi bi-list"></i>
  </button>
  <span class="topbar-title me-auto">
    <i class="bi bi-house-door me-1 text-secondary"></i> Beranda
  </span>
</header>

<!-- ── MAIN CONTENT ── -->
<main class="main-content">

  <!-- Welcome Banner -->
  <div class="welcome-banner d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div>
      <h5 class="fw-bold mb-1">Selamat Datang, <?= esc($name) ?>! 👋</h5>
      <p class="mb-0 opacity-80" style="font-size:.875rem;">
        Anda login sebagai <strong><?= esc($displayRole) ?></strong>.
        Pilih menu di bawah untuk memulai.
      </p>
    </div>
    <span class="time-badge" id="liveClock"></span>
  </div>

  <!-- Info Flash -->
  <?php if (session()->getFlashdata('info')): ?>
    <div class="alert alert-info alert-dismissible fade show mb-3" role="alert">
      <i class="bi bi-info-circle-fill me-1"></i>
      <?= esc(session()->getFlashdata('info')) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-1"></i>
      <?= esc(session()->getFlashdata('error')) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Menu Cards -->
  <div class="row g-4">

    <?php if ($hasCrpAccess): ?>
    <!-- CRP Dashboard – kode_jabatan 1-6 -->
    <div class="col-md-6 col-lg-4">
      <a href="<?= base_url('crp') ?>" class="menu-card card card-crp p-4">
        <div class="d-flex align-items-center gap-3 mb-3">
          <div class="card-icon text-primary"><i class="bi bi-clipboard-data-fill"></i></div>
          <div>
            <h6 class="fw-bold mb-0">CRP Dashboard</h6>
          </div>
        </div>
        <p class="text-muted small mb-3">
          Kelola dan tandai item sparepart yang perlu di-control. 
          Lihat analisis usage, amount, target 5%, dan achievement CRP.
        </p>
        <div class="d-flex align-items-center gap-1 text-primary fw-semibold small">
          Buka Dashboard <i class="bi bi-arrow-right-short fs-5"></i>
        </div>
      </a>
    </div>

    <div class="col-md-6 col-lg-4">
      <a href="<?= base_url('history-admin') ?>" class="menu-card card card-crp p-4">
        <div class="d-flex align-items-center gap-3 mb-3">
          <div class="card-icon text-primary"><i class="bi bi-clock-history"></i></div>
          <div>
            <h6 class="fw-bold mb-0">History Admin</h6>
          </div>
        </div>
        <p class="text-muted small mb-3">
          Lihat rekap history monitoring mingguan per bulan untuk evaluasi pemakaian dan perbandingan total qty.
        </p>
        <div class="d-flex align-items-center gap-1 text-primary fw-semibold small">
          Buka History <i class="bi bi-arrow-right-short fs-5"></i>
        </div>
      </a>
    </div>
    <?php endif; ?>

    <!-- Monitor User – semua role -->
    <div class="col-md-6 col-lg-4">
      <a href="<?= base_url('monitor-user') ?>" class="menu-card card card-user p-4">
        <div class="d-flex align-items-center gap-3 mb-3">
          <div class="card-icon" style="color:#0f766e;"><i class="bi bi-eye-fill"></i></div>
          <div>
            <h6 class="fw-bold mb-0">Monitor User</h6>
          </div>
        </div>
        <p class="text-muted small mb-3">
          Lihat daftar item sparepart yang sudah ditandai untuk di-control 
          berdasarkan penandaan dari CRP Dashboard.
        </p>
        <div class="d-flex align-items-center gap-1 fw-semibold small" style="color:#0f766e;">
          Buka Monitor <i class="bi bi-arrow-right-short fs-5"></i>
        </div>
      </a>
    </div>

    <?php if (!$hasCrpAccess): ?>
    <!-- Placeholder CRP – kode_jabatan 7, tampil abu-abu -->
    <div class="col-md-6 col-lg-4">
      <div class="menu-card card card-crp card-disabled p-4">
        <div class="d-flex align-items-center gap-3 mb-3">
          <div class="card-icon text-secondary"><i class="bi bi-clipboard-data-fill"></i></div>
          <div>
            <h6 class="fw-bold mb-0">CRP Dashboard</h6>
            <span class="badge bg-secondary role-badge">Jabatan 7 Tidak Diizinkan</span>
          </div>
        </div>
        <p class="text-muted small mb-3">Halaman ini hanya dapat diakses oleh user jabatan 1 sampai 6.</p>
        <div class="d-flex align-items-center gap-1 text-secondary fw-semibold small">
          <i class="bi bi-lock-fill me-1"></i> Akses Terbatas
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script>
  /* Live clock */
  function tick() {
    const now = new Date();
    document.getElementById('liveClock').textContent =
      now.toLocaleDateString('id-ID', { weekday:'long', year:'numeric', month:'long', day:'numeric' })
      + '  ' +
      now.toLocaleTimeString('id-ID');
  }
  tick();
  setInterval(tick, 1000);

  /* Mobile sidebar toggle */
  const sidebar = document.getElementById('sidebar');
  document.getElementById('sidebarToggle').addEventListener('click', () => {
    sidebar.classList.toggle('open');
  });
  document.addEventListener('click', e => {
    if (!sidebar.contains(e.target) && !document.getElementById('sidebarToggle').contains(e.target)) {
      sidebar.classList.remove('open');
    }
  });
</script>

</body>
</html>
