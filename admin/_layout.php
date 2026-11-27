<?php
require_once __DIR__ . '/../config.php';
need_admin();

$adminUser = $_SESSION['admin']['username'] ?? 'admin';
$currentAdminPage = basename($_SERVER['PHP_SELF'] ?? 'index.php');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($adminTitle ?? 'Admin — USGallery') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/style.css">
  <style>
    body {
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
    }
    .side-sub {
      font-size: 0.72rem;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      color: rgba(255, 255, 255, 0.65);
      padding: 8px 12px 4px;
      font-weight: 700;
    }
  </style>
</head>
<body>
  <div class="adm">
    <aside class="side">
      <a href="index.php" class="brand">
        UNUSA <span>Souvenir</span>
        <div class="small fw-normal" style="color: rgba(255,255,255,0.75); font-size: 0.78rem;">USGallery Admin</div>
      </a>

      <div class="side-sub">Menu Utama</div>
      <a href="index.php" class="<?= $currentAdminPage === 'index.php' ? 'on' : '' ?>">Dashboard</a>
      <a href="transaksi.php" class="<?= $currentAdminPage === 'transaksi.php' ? 'on' : '' ?>">Transaksi Baru</a>
      <a href="produk.php" class="<?= $currentAdminPage === 'produk.php' ? 'on' : '' ?>">Data Produk</a>
      <a href="kelola_pesanan.php" class="<?= $currentAdminPage === 'kelola_pesanan.php' ? 'on' : '' ?>">Kelola Pesanan</a>
      <a href="pelanggan.php" class="<?= $currentAdminPage === 'pelanggan.php' ? 'on' : '' ?>">Pelanggan</a>
      <a href="laporan.php" class="<?= $currentAdminPage === 'laporan.php' ? 'on' : '' ?>">Laporan</a>

      <hr class="border-light border-opacity-25 my-3">

      <div class="side-sub">Akun &amp; Toko</div>
      <a href="../index.php" target="_blank" rel="noopener noreferrer">Lihat Katalog Pembeli</a>
      <div class="px-3 py-2 small" style="color: rgba(255,255,255,0.85);">
        Profil Admin: <strong><?= e($adminUser) ?></strong>
      </div>
      <button type="button" class="btn btn-sm btn-outline-light w-100 mt-1 text-start px-3"
              data-bs-toggle="modal" data-bs-target="#logoutModal">
        Log Out
      </button>
    </aside>

    <div class="cont">
      <?php if ($msg = flash()): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
          <?= e($msg) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>