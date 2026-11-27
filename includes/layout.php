<?php
require_once __DIR__ . '/../config.php';

$kategoriList =$pdo->query("SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori ASC")->fetchAll();
$cartCount = 0;
foreach (cart() as $item) {
    $cartCount += (int)($item['qty'] ?? 1);
}
$currentPage = basename($_SERVER['PHP_SELF'] ?? 'index.php');
$searchQuery =$_GET['q'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? 'USGallery — UNUSA Souvenir Gallery') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/style.css">
  <style>
    :root {
      --usg-green: #146c43;
      --usg-green-dark: #0e4f31;
      --usg-green-soft: #e9f5ee;
      --usg-ink: #17201c;
      --usg-muted: #5c6b64;
    }

    body {
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
      color: var(--usg-ink);
      background-color: #f6f8f7;
    }

    .navbar-usg {
      background: rgba(255, 255, 255, 0.96);
      backdrop-filter: blur(8px);
      border-bottom: 1px solid #e2e8e4;
    }

    .brand-box {
      background: var(--usg-green);
      color: #fff !important;
      padding: 6px 14px;
      font-weight: 800;
      letter-spacing: 0.3px;
      text-decoration: none;
      border-radius: 8px;
      transition: background-color 0.2s ease;
    }

    .brand-box:hover {
      background: var(--usg-green-dark);
    }

    .nav-link-usg {
      color: var(--usg-ink);
      font-weight: 600;
      font-size: 0.93rem;
      text-decoration: none;
      padding: 7px 12px;
      border-radius: 8px;
      transition: all 0.2s ease;
    }

    .nav-link-usg:hover,
    .nav-link-usg.active {
      background: var(--usg-green-soft);
      color: var(--usg-green-dark);
    }

    .dropdown-menu-usg {
      border-radius: 10px;
      border: 1px solid #dfe6e2;
      box-shadow: 0 10px 25px rgba(15, 23, 20, 0.08);
      padding: 6px;
      min-width: 210px;
    }

    .dropdown-menu-usg .dropdown-item {
      border-radius: 7px;
      padding: 8px 12px;
      font-size: 0.88rem;
      font-weight: 500;
      white-space: normal;
    }

    .dropdown-menu-usg .dropdown-item:hover {
      background: var(--usg-green-soft);
      color: var(--usg-green-dark);
    }

    .btn-usg {
      background-color: var(--usg-green);
      border-color: var(--usg-green);
      color: #fff;
      font-weight: 600;
    }

    .btn-usg:hover,
    .btn-usg:focus {
      background-color: var(--usg-green-dark);
      border-color: var(--usg-green-dark);
      color: #fff;
    }

    .btn-outline-usg {
      border-color: var(--usg-green);
      color: var(--usg-green);
      font-weight: 600;
    }

    .btn-outline-usg:hover {
      background-color: var(--usg-green);
      border-color: var(--usg-green);
      color: #fff;
    }

    .product-card {
      border: 1px solid #e3e9e5;
      border-radius: 14px;
      transition: transform 0.22s ease, box-shadow 0.22s ease;
      background: #fff;
    }

    .product-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 24px rgba(20, 108, 67, 0.1) !important;
    }

    .avatar-circle {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      background: var(--usg-green-soft);
      color: var(--usg-green-dark);
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .footer-usg {
      background: #14221b;
      color: #dce5e0;
      padding: 28px 0;
      margin-top: 56px;
    }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-usg sticky-top">
  <div class="container py-1">
    <a class="brand-box me-3" href="index.php">USGallery</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navUsg">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navUsg">
      <ul class="navbar-nav mx-auto align-items-lg-center gap-lg-1">
        <li class="nav-item">
          <a class="nav-link-usg <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="index.php">Home</a>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link-usg dropdown-toggle <?= in_array($currentPage, ['produk.php', 'detail.php']) ? 'active' : '' ?>"
             href="produk.php" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Produk
          </a>
          <ul class="dropdown-menu dropdown-menu-usg">
            <?php foreach ($kategoriList as$kat): ?>
              <li>
                <a class="dropdown-item" href="produk.php?kategori=<?= (int)$kat['id_kategori'] ?>">
                  <?= e($kat['nama_kategori']) ?>
                </a>
              </li>
            <?php endforeach; ?>
            <li><hr class="dropdown-divider my-1"></li>
            <li>
              <a class="dropdown-item fw-bold text-success" href="produk.php">All Produk</a>
            </li>
          </ul>
        </li>

        <li class="nav-item">
          <a class="nav-link-usg <?= $currentPage === 'lacak.php' ? 'active' : '' ?>" href="lacak.php">Lacak Pesanan</a>
        </li>
      </ul>

      <div class="d-flex align-items-center gap-2 mt-2 mt-lg-0">
        <form action="produk.php" method="get" class="d-flex align-items-center" role="search">
          <input type="text" name="q" value="<?= e($searchQuery) ?>" class="form-control form-control-sm rounded-pill px-3" placeholder="Cari souvenir..." aria-label="Pencarian">
          <button type="submit" class="btn btn-sm btn-usg rounded-pill px-3 ms-1" title="Cari">Cari</button>
        </form>

       <a href="keranjang.php" class="btn btn-sm btn-usg rounded-pill px-3 d-inline-flex align-items-center gap-1" title="Keranjang">
  <span>Keranjang</span>
  <?php if ($cartCount > 0): ?>
    <span class="badge rounded-pill bg-white text-success fw-bold"><?= $cartCount ?></span>
  <?php endif; ?>
</a>
      </div>
    </div>
  </div>
</nav>

<main class="container my-4">
  <?php if ($msg = flash()): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
      <?= e($msg) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>