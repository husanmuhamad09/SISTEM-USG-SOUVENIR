<?php
require_once __DIR__ . '/includes/layout.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    flash('Produk tidak ditemukan.');
    header('Location: produk.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT p.*, k.nama_kategori
    FROM produk p
    LEFT JOIN kategori k ON p.id_kategori = k.id_kategori
    WHERE p.id_produk = :id
    LIMIT 1
");
$stmt->execute([':id' => $id]);
$produk = $stmt->fetch();

if (!$produk) {
    flash('Data produk tidak tersedia.');
    header('Location: produk.php');
    exit;
}

$pageTitle = e($produk['nama_produk']) . ' — USGallery';
$imgPath = !empty($produk['gambar']) ? 'uploads/' . ltrim($produk['gambar'], '/') : '';
$hasImg = $imgPath !== '' && file_exists(__DIR__ . '/' . $imgPath);
$stok = (int)$produk['stok'];
?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb small mb-0">
    <li class="breadcrumb-item"><a href="index.php" class="text-success text-decoration-none">Home</a></li>
    <li class="breadcrumb-item"><a href="produk.php" class="text-success text-decoration-none">Produk</a></li>
    <?php if (!empty($produk['id_kategori'])): ?>
      <li class="breadcrumb-item">
        <a href="produk.php?kategori=<?= (int)$produk['id_kategori'] ?>" class="text-success text-decoration-none">
          <?= e($produk['nama_kategori'] ?? 'Kategori') ?>
        </a>
      </li>
    <?php endif; ?>
    <li class="breadcrumb-item active" aria-current="page"><?= e($produk['nama_produk']) ?></li>
  </ol>
</nav>

<div class="card product-card shadow-sm overflow-hidden mb-5">
  <div class="row g-0 align-items-stretch">
    <div class="col-md-5 bg-light d-flex align-items-center justify-content-center p-4 border-bottom border-md-bottom-0 border-md-end" style="min-height: 360px;">
      <?php if ($hasImg): ?>
        <img src="<?= e($imgPath) ?>" alt="<?= e($produk['nama_produk']) ?>" class="img-fluid rounded-3" style="max-height: 320px; object-fit: cover;">
      <?php else: ?>
        <div class="w-100 h-100 rounded-3 border border-2 border-dashed d-flex flex-column align-items-center justify-content-center p-4 text-center bg-white" style="min-height: 280px;">
          <span class="badge rounded-pill bg-success bg-opacity-10 text-success mb-2 px-3 py-2">
            <?= e($produk['nama_kategori'] ?? 'USGallery') ?>
          </span>
          <div class="fw-bold text-dark mb-1"><?= e($produk['nama_produk']) ?></div>
          <div class="text-muted small">Gambar produk belum tersedia</div>
        </div>
      <?php endif; ?>
    </div>

    <div class="col-md-7">
      <div class="card-body p-4 p-lg-5 d-flex flex-column h-100">
        <div class="mb-2">
          <span class="badge rounded-pill bg-success bg-opacity-10 text-success border border-success-subtle px-3 py-2">
            <?= e($produk['nama_kategori'] ?? 'Merchandise') ?>
          </span>
        </div>

        <h1 class="h3 fw-bold mb-2"><?= e($produk['nama_produk']) ?></h1>

        <div class="fs-4 fw-extrabold text-success mb-3">
          <?= (float)$produk['harga'] > 0 ? e(rp($produk['harga'])) : 'Harga via Konsultasi WhatsApp' ?>
        </div>

        <div class="mb-3 small">
          <strong>Status Stok:</strong>
          <?php if ($stok > 0): ?>
            <span class="text-success fw-semibold">Tersedia (<?= $stok ?> pcs)</span>
          <?php else: ?>
            <span class="text-danger fw-semibold">Stok Habis</span>
          <?php endif; ?>
        </div>

        <p class="text-muted mb-4">
          <?= nl2br(e($produk['deskripsi'] ?? 'Souvenir resmi dari UNUSA Souvenir Gallery (USGallery).')) ?>
        </p>

        <form action="keranjang.php" method="post" class="mt-auto">
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="id_produk" value="<?= (int)$produk['id_produk'] ?>">

          <div class="row g-2 align-items-end">
            <div class="col-4 col-sm-3">
              <label for="qtyInput" class="form-label small fw-semibold mb-1">Jumlah</label>
              <input type="number" id="qtyInput" name="qty" value="1" min="1" max="<?= max(1, $stok) ?>"
                     class="form-control" <?= $stok <= 0 ? 'disabled' : '' ?>>
            </div>
            <div class="col-8 col-sm-5">
              <button type="submit" class="btn btn-usg w-100 py-2 rounded-pill" <?= $stok <= 0 ? 'disabled' : '' ?>>
                <?= $stok > 0 ? '+ Masukkan ke Keranjang' : 'Stok Habis' ?>
              </button>
            </div>
            <div class="col-12 col-sm-4">
              <a href="produk.php" class="btn btn-outline-usg w-100 py-2 rounded-pill">Kembali</a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
