<?php
$adminTitle = 'Riwayat & Laporan — USGallery Admin';
require_once __DIR__ . '/_layout.php';

$dariTanggal = trim($_GET['dari'] ?? date('Y-m-01'));
$sampaiTanggal = trim($_GET['sampai'] ?? date('Y-m-d'));

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dariTanggal)) {
    $dariTanggal = date('Y-m-01');
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $sampaiTanggal)) {
    $sampaiTanggal = date('Y-m-d');
}
if ($dariTanggal > $sampaiTanggal) {
    [$dariTanggal, $sampaiTanggal] = [$sampaiTanggal, $dariTanggal];
}

$penjualanHariIni = (float)$pdo->query("
    SELECT COALESCE(SUM(total_transaksi), 0)
    FROM transaksi
    WHERE DATE(tanggal_transaksi) = CURDATE()
")->fetchColumn();

$penjualanBulanIni = (float)$pdo->query("
    SELECT COALESCE(SUM(total_transaksi), 0)
    FROM transaksi
    WHERE YEAR(tanggal_transaksi) = YEAR(CURDATE())
      AND MONTH(tanggal_transaksi) = MONTH(CURDATE())
")->fetchColumn();

$hasDetProdCol = false;
$hasProdProdCol = false;
try {
    $hasDetProdCol = (bool)$pdo->query("SHOW COLUMNS FROM detail_transaksi LIKE 'harga_produksi'")->fetch();
    $hasProdProdCol = (bool)$pdo->query("SHOW COLUMNS FROM produk LIKE 'harga_produksi'")->fetch();
} catch (Throwable $e) {
    $hasDetProdCol = false;
    $hasProdProdCol = false;
}

if ($hasDetProdCol && $hasProdProdCol) {
    $marginExpr = "COALESCE(SUM((dt.harga - CASE WHEN dt.harga_produksi > 0 THEN dt.harga_produksi ELSE COALESCE(p.harga_produksi, 0) END) * dt.jumlah), 0)";
} elseif ($hasProdProdCol) {
    $marginExpr = "COALESCE(SUM((dt.harga - COALESCE(p.harga_produksi, 0)) * dt.jumlah), 0)";
} else {
    $marginExpr = "0";
}

$sqlTrx = "
    SELECT t.*, pl.nama_pelanggan, pl.no_telepon, pl.alamat,
           (SELECT COALESCE(SUM(dt.jumlah), 0) FROM detail_transaksi dt WHERE dt.id_transaksi = t.id_transaksi) AS total_item,
           (SELECT GROUP_CONCAT(CONCAT(COALESCE(pr.nama_produk, 'Produk'), ' (', dt.jumlah, ')') SEPARATOR ', ')
            FROM detail_transaksi dt
            LEFT JOIN produk pr ON dt.id_produk = pr.id_produk
            WHERE dt.id_transaksi = t.id_transaksi) AS daftar_item,
           (SELECT {$marginExpr}
            FROM detail_transaksi dt
            LEFT JOIN produk p ON dt.id_produk = p.id_produk
            WHERE dt.id_transaksi = t.id_transaksi) AS margin_trx
    FROM transaksi t
    LEFT JOIN pelanggan pl ON t.id_pelanggan = pl.id_pelanggan
    WHERE DATE(t.tanggal_transaksi) BETWEEN :dari AND :sampai
    ORDER BY t.tanggal_transaksi DESC, t.id_transaksi DESC
";
$stmtTrx = $pdo->prepare($sqlTrx);
$stmtTrx->execute([
    ':dari'   => $dariTanggal,
    ':sampai' => $sampaiTanggal
]);
$riwayatTrx = $stmtTrx->fetchAll();

$totalPeriode = 0.0;
$totalMarginPeriode = 0.0;
$totalPcsPeriode = 0;
$grafikMap = [];

foreach ($riwayatTrx as $row) {
    $nominal = (float)($row['total_transaksi'] ?? 0);
    $margin = (float)($row['margin_trx'] ?? 0);
    $pcs = (int)($row['total_item'] ?? 0);

    $totalPeriode += $nominal;
    $totalMarginPeriode += $margin;
    $totalPcsPeriode += $pcs;

    $tglKey = substr((string)($row['tanggal_transaksi'] ?? ''), 0, 10);
    if ($tglKey !== '') {
        $grafikMap[$tglKey] = ($grafikMap[$tglKey] ?? 0.0) + $nominal;
    }
}
ksort($grafikMap);
$grafikLabels = array_map(fn($d) => date('d M', strtotime($d)), array_keys($grafikMap));
$grafikValues = array_values($grafikMap);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1">Riwayat Transaksi &amp; Laporan</h1>
    <p class="text-muted small mb-0">Rekap transaksi, filter rentang tanggal, margin keuntungan, dan cetak laporan.</p>
  </div>
  <button type="button" onclick="window.print()" class="btn btn-sm btn-dark rounded-pill px-4 fw-semibold no-print">
    Cetak Laporan
  </button>
</div>

<!-- Filter Rentang Tanggal sesuai Wireframe -->
<div class="bg-white border rounded-3 p-4 shadow-sm mb-4 no-print">
  <h2 class="h6 fw-bold mb-3">Filter Laporan Periode</h2>
  <form method="get" action="laporan.php" class="row g-3 align-items-end">
    <div class="col-sm-4 col-md-4">
      <label for="dari" class="form-label small fw-semibold mb-1">Dari Tanggal</label>
      <input type="date" id="dari" name="dari" value="<?= e($dariTanggal) ?>" class="form-control form-control-sm">
    </div>
    <div class="col-sm-4 col-md-4">
      <label for="sampai" class="form-label small fw-semibold mb-1">Sampai Tanggal</label>
      <input type="date" id="sampai" name="sampai" value="<?= e($sampaiTanggal) ?>" class="form-control form-control-sm">
    </div>
    <div class="col-sm-4 col-md-4 d-flex gap-2">
      <button type="submit" class="btn btn-sm btn-success rounded-pill px-4 fw-semibold">Terapkan</button>
      <a href="laporan.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Bulan Ini</a>
    </div>
  </form>
</div>

<!-- Ringkasan Penjualan -->
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="bg-white border rounded-3 p-3 h-100 shadow-sm">
      <div class="small text-uppercase fw-semibold text-muted mb-1">Penjualan Hari Ini</div>
      <div class="fs-5 fw-extrabold text-success"><?= e(rp($penjualanHariIni)) ?></div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="bg-white border rounded-3 p-3 h-100 shadow-sm">
      <div class="small text-uppercase fw-semibold text-muted mb-1">Penjualan Bulan Ini</div>
      <div class="fs-5 fw-extrabold text-success"><?= e(rp($penjualanBulanIni)) ?></div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="bg-white border rounded-3 p-3 h-100 shadow-sm">
      <div class="small text-uppercase fw-semibold text-muted mb-1">Total Periode Dipilih</div>
      <div class="fs-5 fw-extrabold text-dark"><?= e(rp($totalPeriode)) ?></div>
      <div class="small text-muted"><?= count($riwayatTrx) ?> transaksi &bull; <?= $totalPcsPeriode ?> pcs</div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="bg-white border rounded-3 p-3 h-100 shadow-sm">
      <div class="small text-uppercase fw-semibold text-muted mb-1">Margin Periode Dipilih</div>
      <div class="fs-5 fw-extrabold text-primary"><?= e(rp($totalMarginPeriode)) ?></div>
    </div>
  </div>
</div>

<!-- Daftar Transaksi & Grafik -->
<div class="row g-4 align-items-start">
  <div class="col-lg-8">
    <div class="bg-white border rounded-3 p-4 shadow-sm">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h6 fw-bold mb-0">
          Daftar Transaksi (<?= e(date('d/m/Y', strtotime($dariTanggal))) ?> &ndash; <?= e(date('d/m/Y', strtotime($sampaiTanggal))) ?>)
        </h2>
      </div>

      <div class="table-responsive">
        <table class="tbl">
          <thead>
            <tr>
              <th>ID / Kode</th>
              <th>Tanggal</th>
              <th>Item</th>
              <th>Total</th>
              <th>Metode / Sumber</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($riwayatTrx)): ?>
              <tr>
                <td colspan="6" class="text-center text-muted py-4">
                  Tidak ada transaksi pada rentang tanggal yang dipilih.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($riwayatTrx as $trx): ?>
                <tr>
                  <td>
                    <div class="fw-bold text-success"><?= e($trx['kode']) ?></div>
                    <?php if (!empty($trx['nama_pelanggan'])): ?>
                      <div class="small text-muted"><?= e($trx['nama_pelanggan']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td class="small text-muted">
                    <?= e(date('d/m/Y H:i', strtotime((string)($trx['tanggal_transaksi'] ?? 'now')))) ?>
                  </td>
                  <td class="small">
                    <div class="fw-semibold"><?= (int)($trx['total_item'] ?? 0) ?> pcs</div>
                    <div class="text-muted"><?= e($trx['daftar_item'] ?? '-') ?></div>
                  </td>
                  <td class="fw-bold"><?= e(rp((float)($trx['total_transaksi'] ?? 0))) ?></td>
                  <td><span class="pill"><?= e($trx['sumber'] ?? 'WhatsApp') ?></span></td>
                  <td class="small fw-semibold"><?= e($trx['status'] ?? 'Menunggu Pembayaran') ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="bg-white border rounded-3 p-4 shadow-sm">
      <h2 class="h6 fw-bold mb-3">Grafik Periode</h2>
      <?php if (empty($grafikLabels)): ?>
        <p class="small text-muted mb-0">Belum ada data grafik untuk periode ini.</p>
      <?php else: ?>
        <div style="position: relative; min-height: 240px;">
          <canvas id="reportChart"></canvas>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if (!empty($grafikLabels)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
  (function () {
    const el = document.getElementById('reportChart');
    if (!el || typeof Chart === 'undefined') return;

    new Chart(el, {
      type: 'bar',
      data: {
        labels: <?= json_encode($grafikLabels) ?>,
        datasets: [{
          label: 'Penjualan (Rp)',
          data: <?= json_encode($grafikValues) ?>,
          backgroundColor: 'rgba(21, 128, 61, 0.78)',
          borderColor: '#15803d',
          borderWidth: 1,
          borderRadius: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function (v) {
                return 'Rp ' + Number(v).toLocaleString('id-ID');
              }
            }
          }
        }
      }
    });
  })();
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/_footer.php'; ?>