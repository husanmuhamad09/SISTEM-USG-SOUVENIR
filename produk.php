<?php
$pageTitle = 'Katalog Produk — USGallery';
require_once __DIR__ . '/includes/layout.php';

$kategoriId = isset($_GET['kategori']) && $_GET['kategori'] !== '' ? (int)$_GET['kategori'] : 0;
$q = trim($_GET['q'] ?? '');

$sql = "
    SELECT p.*, k.nama_kategori
    FROM produk p
    LEFT JOIN kategori k ON p.id_kategori = k.id_kategori
    WHERE 1=1
";
$params = [];

if ($kategoriId > 0) {
    $sql .= " AND p.id_kategori = :id_kategori";
    $params[':id_kategori'] = $kategoriId;
}

if ($q !== '') {
    $sql .= " AND (p.nama_produk LIKE :q OR p.deskripsi LIKE :q OR k.nama_kategori LIKE :q)";
    $params[':q'] = '%' . $q . '%';
}

$sql .= " ORDER BY p.id_produk ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produkList = $stmt->fetchAll();

$namaKategoriAktif = 'Semua Produk';
if ($kategoriId > 0) {
    foreach ($kategoriList as $kat) {
        if ((int)$kat['id_kategori'] === $kategoriId) {
            $namaKategoriAktif = $kat['nama_kategori'];
            break;
        }
    }
}
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1"><?= e($namaKategoriAktif) ?></h1>
    <p class="text-muted small mb-0">
      <?php if ($q !== ''): ?>
        Hasil pencarian untuk &ldquo;<strong><?= e($q) ?></strong>&rdquo; (<?= count($produkList) ?> produk)
      <?php else: ?>
        Menampilkan <?= count($produkList) ?> merchandise USGallery
      <?php endif; ?>
    </p>
  </div>

  <div class="d-flex flex-wrap gap-1">
    <a href="produk.php" class="btn btn-sm <?= $kategoriId === 0 ? 'btn-dark' : 'btn-outline-dark' ?>">All</a>
    <?php foreach ($kategoriList as $kat): ?>
      <a href="produk.php?kategori=<?= (int)$kat['id_kategori'] ?>"
         class="btn btn-sm <?= $kategoriId === (int)$kat['id_kategori'] ? 'btn-dark' : 'btn-outline-dark' ?>">
        <?= e($kat['nama_kategori']) ?>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<?php if (empty($produkList)): ?>
  <div class="alert alert-secondary">
    Produk tidak ditemukan. Coba pilih kategori lain atau reset kata kunci pencarian.
  </div>
<?php else: ?>
  <div class="row g-4">
    <?php foreach ($produkList as $p): ?>
      <?php
        $imgPath = !empty($p['gambar']) ? 'uploads/' . ltrim($p['gambar'], '/') : '';
        $hasImg = $imgPath !== '' && file_exists(__DIR__ . '/' . $imgPath);
        $stok = (int)$p['stok'];
      ?>
      <div class="col-sm-6 col-lg-4">
        <div class="card h-100 shadow-sm">
          <div class="bg-light d-flex align-items-center justify-content-center border-bottom" style="height: 200px; overflow: hidden;">
            <?php if ($hasImg): ?>
              <img src="<?= e($imgPath) ?>" alt="<?= e($p['nama_produk']) ?>" class="w-100 h-100 object-fit-cover">
            <?php else: ?>
              <span class="text-muted small">Gambar Produk</span>
            <?php endif; ?>
          </div>
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
              <h2 class="h6 fw-bold mb-0"><?= e($p['nama_produk']) ?></h2>
              <span class="badge bg-secondary"><?= e($p['nama_kategori'] ?? 'Umum') ?></span>
            </div>
            <p class="text-muted small flex-grow-1"><?= e($p['deskripsi'] ?? '') ?></p>
            <div class="d-flex justify-content-between align-items-center mb-3">
              <span class="fw-bold"><?= (float)$p['harga'] > 0 ? e(rp($p['harga'])) : 'Harga via WA' ?></span>
              <span class="small text-muted">Stok: <?= $stok ?></span>
            </div>
            <div class="d-flex gap-2">
              <a href="detail.php?id=<?= (int)$p['id_produk'] ?>" class="btn btn-sm btn-outline-dark w-50">Detail</a>
              <form action="keranjang.php" method="post" class="w-50 m-0">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="id_produk" value="<?= (int)$p['id_produk'] ?>">
                <input type="hidden" name="qty" value="1">
                <button type="submit" class="btn btn-sm btn-dark w-100" <?= $stok <= 0 ? 'disabled' : '' ?>>
                  <?= $stok > 0 ? '+ Keranjang' : 'Habis' ?>
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>