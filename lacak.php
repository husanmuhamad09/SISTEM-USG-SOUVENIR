<?php
$pageTitle = 'Lacak Pesanan — USGallery';
require_once __DIR__ . '/includes/layout.php';

$kodeInput = strtoupper(trim($_GET['kode'] ?? ''));
$pesanan = null;
$itemsPesanan = [];
$searched = ($kodeInput !== '');

$steps = [
    'Menunggu Pembayaran',
    'Sedang Diproses',
    'Siap Diambil',
    'Sudah Diambil'
];

function getStepIndex(string $status): int {
    $s = strtolower(trim($status));
    if (in_array($s, ['sudah diambil', 'selesai', 'done', 'completed'], true)) {
        return 3;
    }
    if (in_array($s, ['siap diambil', 'siap dikirim', 'ready'], true)) {
        return 2;
    }
    if (in_array($s, ['sedang diproses', 'diproses', 'proses', 'dikonfirmasi', 'lunas', 'processing'], true)) {
        return 1;
    }
    return 0;
}

if ($searched) {
    $stmt = $pdo->prepare("
        SELECT t.*, pl.nama_pelanggan, pl.no_telepon, pl.alamat
        FROM transaksi t
        LEFT JOIN pelanggan pl ON t.id_pelanggan = pl.id_pelanggan
        WHERE UPPER(t.kode) = :kode
        LIMIT 1
    ");
    $stmt->execute([':kode' => $kodeInput]);
    $pesanan = $stmt->fetch();

    if ($pesanan) {
        $stmtDetail = $pdo->prepare("
            SELECT dt.*, pr.nama_produk, pr.gambar
            FROM detail_transaksi dt
            LEFT JOIN produk pr ON dt.id_produk = pr.id_produk
            WHERE dt.id_transaksi = :id_trx
        ");
        $stmtDetail->execute([':id_trx' => (int)$pesanan['id_transaksi']]);
        $itemsPesanan = $stmtDetail->fetchAll();
    }
}

$activeStep = $pesanan ? getStepIndex((string)($pesanan['status'] ?? 'Menunggu')) : 0;
?>

<div class="mb-4">
  <h1 class="h3 fw-bold mb-1">Lacak Pesanan</h1>
  <p class="text-muted small mb-0">Pantau status pesananmu sampai siap diambil.</p>
</div>

<div class="card product-card shadow-sm mb-4">
  <div class="card-body p-4">
    <form method="get" action="lacak.php" class="row g-2 align-items-end">
      <div class="col-sm-9 col-md-10">
        <label for="kodePesanan" class="form-label small fw-bold mb-1">Nomor ID / Kode Pesanan</label>
        <input type="text" id="kodePesanan" name="kode" value="<?= e($kodeInput) ?>"
               class="form-control" placeholder="Contoh: ORD-2026-001" required>
      </div>
      <div class="col-sm-3 col-md-2">
        <button type="submit" class="btn btn-usg w-100 py-2 rounded-3">Lacak</button>
      </div>
    </form>
  </div>
</div>

<?php if ($searched && !$pesanan): ?>
  <div class="alert alert-warning rounded-3">
    Pesanan dengan kode <strong><?= e($kodeInput) ?></strong> belum ditemukan. Pastikan kode pesanan sudah benar atau hubungi admin USGallery.
  </div>
<?php elseif ($pesanan): ?>
  <?php
    $firstItem = $itemsPesanan[0] ?? null;
    $totalQty = 0;
    foreach ($itemsPesanan as $it) {
        $totalQty += (int)($it['jumlah'] ?? 0);
    }
    $imgPath = ($firstItem && !empty($firstItem['gambar'])) ? 'uploads/' . ltrim($firstItem['gambar'], '/') : '';
    $hasImg = $imgPath !== '' && file_exists(__DIR__ . '/' . $imgPath);
  ?>

  <div class="card product-card shadow-sm">
    <div class="card-body p-4">
      <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 pb-3 mb-4 border-bottom">
        <div class="d-flex align-items-center gap-3">
          <div class="bg-light border rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
               style="width: 72px; height: 72px; overflow: hidden;">
            <?php if ($hasImg): ?>
              <img src="<?= e($imgPath) ?>" alt="<?= e($firstItem['nama_produk'] ?? 'Produk') ?>" class="w-100 h-100 object-fit-cover">
            <?php else: ?>
              <span class="text-muted small">Produk</span>
            <?php endif; ?>
          </div>
          <div>
            <div class="fw-bold">
              <?= e($firstItem['nama_produk'] ?? 'Pesanan USGallery') ?>
              <?php if (count($itemsPesanan) > 1): ?>
                <span class="badge bg-secondary ms-1">+<?= count($itemsPesanan) - 1 ?> item lainnya</span>
              <?php endif; ?>
            </div>
            <div class="small text-muted">
              <?= $totalQty ?> pcs &bull; Kode Pesanan: <strong><?= e($pesanan['kode']) ?></strong>
            </div>
            <div class="mt-1">
              <span class="badge rounded-pill bg-success bg-opacity-10 text-success border border-success-subtle">
                <?= e($pesanan['status'] ?? 'Menunggu') ?>
              </span>
            </div>
          </div>
        </div>

        <div class="text-sm-end">
          <div class="small text-muted">Total Transaksi</div>
          <div class="fw-bold text-success fs-6"><?= e(rp($pesanan['total_transaksi'] ?? 0)) ?></div>
        </div>
      </div>

      <h2 class="h6 fw-bold mb-1"><?= e($steps[$activeStep]) ?></h2>
      <p class="small text-muted mb-4">
        Pemesan: <strong><?= e($pesanan['nama_pelanggan'] ?? '-') ?></strong> &bull;
        Alamat: <?= e($pesanan['alamat'] ?? '-') ?> &bull;
        Tanggal: <?= e($pesanan['tanggal_transaksi'] ?? '-') ?>
      </p>

      <div class="row text-center g-3 mb-4">
        <?php foreach ($steps as $idx => $label): ?>
          <?php
            $done = $idx <= $activeStep;
            $current = $idx === $activeStep;
          ?>
          <div class="col-6 col-md-3">
            <div class="p-3 rounded-3 border <?= $current ? 'border-success bg-success bg-opacity-10' : ($done ? 'border-success-subtle bg-light' : 'bg-light opacity-75') ?>">
              <div class="mx-auto mb-2 rounded-circle d-flex align-items-center justify-content-center fw-bold <?= $done ? 'bg-success text-white' : 'bg-secondary text-white' ?>"
                   style="width: 36px; height: 36px;">
                <?= $idx + 1 ?>
              </div>
              <div class="small fw-bold <?= $done ? 'text-success' : 'text-muted' ?>"><?= e($label) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="bg-light border rounded-3 p-3 small text-muted">
        <strong>Info Status:</strong> Admin USGallery akan memperbarui status pesanan Anda seiring proses konfirmasi pembayaran, penyiapan barang, hingga siap diambil/dikirim.
      </div>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>