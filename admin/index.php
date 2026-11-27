<?php
$adminTitle = 'Dashboard Admin — USGallery';
require_once __DIR__ . '/_layout.php';

$totalProduk = (int)$pdo->query("SELECT COUNT(*) FROM produk")->fetchColumn();
$totalTransaksi = (int)$pdo->query("SELECT COUNT(*) FROM transaksi")->fetchColumn();
$totalPendapatan = (float)$pdo->query("SELECT COALESCE(SUM(total_transaksi), 0) FROM transaksi")->fetchColumn();
$stokMenipis = (int)$pdo->query("SELECT COUNT(*) FROM produk WHERE stok <= 5")->fetchColumn();

$pendapatanHariIni = (float)$pdo->query("
    SELECT COALESCE(SUM(total_transaksi), 0)
    FROM transaksi
    WHERE DATE(tanggal_transaksi) = CURDATE()
")->fetchColumn();

$pendapatanBulanIni = (float)$pdo->query("
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

$totalMargin = (float)$pdo->query("
    SELECT {$marginExpr}
    FROM detail_transaksi dt
    LEFT JOIN produk p ON dt.id_produk = p.id_produk
")->fetchColumn();

// Grafik penjualan & margin keuntungan 7 hari terakhir
$chartLabels = [];
$mapSales7Hari = [];
$mapMargin7Hari = [];

for ($i = 6; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-{$i} days"));
    $chartLabels[] = date('d M', strtotime($tgl));
    $mapSales7Hari[$tgl] = 0.0;
    $mapMargin7Hari[$tgl] = 0.0;
}

$stmt7 = $pdo->query("
    SELECT DATE(tanggal_transaksi) AS tgl, COALESCE(SUM(total_transaksi), 0) AS total
    FROM transaksi
    WHERE DATE(tanggal_transaksi) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(tanggal_transaksi)
");
foreach ($stmt7->fetchAll() as $r7) {
    $t = (string)$r7['tgl'];
    if (isset($mapSales7Hari[$t])) {
        $mapSales7Hari[$t] = (float)$r7['total'];
    }
}

$stmtM7 = $pdo->query("
    SELECT DATE(t.tanggal_transaksi) AS tgl, {$marginExpr} AS margin_total
    FROM detail_transaksi dt
    INNER JOIN transaksi t ON dt.id_transaksi = t.id_transaksi
    LEFT JOIN produk p ON dt.id_produk = p.id_produk
    WHERE DATE(t.tanggal_transaksi) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(t.tanggal_transaksi)
");
foreach ($stmtM7->fetchAll() as $rm7) {
    $t = (string)$rm7['tgl'];
    if (isset($mapMargin7Hari[$t])) {
        $mapMargin7Hari[$t] = (float)$rm7['margin_total'];
    }
}

$chartSalesTotals = array_values($mapSales7Hari);
$chartMarginTotals = array_values($mapMargin7Hari);

$produkTerlaris = $pdo->query("
    SELECT p.nama_produk, COALESCE(SUM(dt.jumlah), 0) AS terjual
    FROM detail_transaksi dt
    INNER JOIN produk p ON dt.id_produk = p.id_produk
    GROUP BY p.id_produk, p.nama_produk
    ORDER BY terjual DESC, p.id_produk ASC
    LIMIT 5
")->fetchAll();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h1 class="h3 fw-bold mb-1">Dashboard</h1>
    <p class="text-muted small mb-0">Ringkasan performa katalog, penjualan, dan margin keuntungan USGallery.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="transaksi.php" class="btn btn-sm btn-success rounded-pill px-3 fw-semibold">+ Transaksi Baru</a>
    <a href="produk.php" class="btn btn-sm btn-outline-success rounded-pill px-3 fw-semibold">+ Data Produk</a>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-sm-6 col-xl-3">
    <div class="bg-white border rounded-3 p-3 h-100 shadow-sm">
      <div class="small text-uppercase fw-semibold text-muted mb-1">Total Produk</div>
      <div class="fs-4 fw-extrabold text-dark"><?= $totalProduk ?></div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="bg-white border rounded-3 p-3 h-100 shadow-sm">
      <div class="small text-uppercase fw-semibold text-muted mb-1">Total Transaksi</div>
      <div class="fs-4 fw-extrabold text-dark"><?= $totalTransaksi ?></div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="bg-white border rounded-3 p-3 h-100 shadow-sm">
      <div class="small text-uppercase fw-semibold text-muted mb-1">Total Pendapatan</div>
      <div class="fs-4 fw-extrabold text-success"><?= e(rp($totalPendapatan)) ?></div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="bg-white border rounded-3 p-3 h-100 shadow-sm">
      <div class="small text-uppercase fw-semibold text-muted mb-1">Stok Menipis (&le; 5)</div>
      <div class="fs-4 fw-extrabold <?= $stokMenipis > 0 ? 'text-danger' : 'text-dark' ?>"><?= $stokMenipis ?></div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="bg-white border rounded-3 p-3 h-100 shadow-sm">
      <div class="small text-uppercase fw-semibold text-muted mb-1">Pendapatan Hari Ini</div>
      <div class="fs-4 fw-extrabold text-success"><?= e(rp($pendapatanHariIni)) ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="bg-white border rounded-3 p-3 h-100 shadow-sm">
      <div class="small text-uppercase fw-semibold text-muted mb-1">Pendapatan Bulan Ini</div>
      <div class="fs-4 fw-extrabold text-success"><?= e(rp($pendapatanBulanIni)) ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="bg-white border rounded-3 p-3 h-100 shadow-sm">
      <div class="small text-uppercase fw-semibold text-muted mb-1">Total Margin Keuntungan</div>
      <div class="fs-4 fw-extrabold text-primary"><?= e(rp($totalMargin)) ?></div>
    </div>
  </div>
</div>

<div class="row g-4 mb-4">
  <div class="col-lg-8">
    <div class="bg-white border rounded-3 p-4 shadow-sm h-100">
      <h2 class="h6 fw-bold mb-3">Grafik Penjualan 7 Hari Terakhir</h2>
      <div style="position: relative; min-height: 240px;">
        <canvas id="salesChart7Days"></canvas>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="bg-white border rounded-3 p-4 shadow-sm h-100">
      <h2 class="h6 fw-bold mb-3">Produk Terlaris</h2>
      <?php if (empty($produkTerlaris)): ?>
        <p class="small text-muted mb-0">Belum ada data penjualan produk.</p>
      <?php else: ?>
        <ol class="list-group list-group-numbered list-group-flush">
          <?php foreach ($produkTerlaris as $pt): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
              <span class="small fw-semibold"><?= e($pt['nama_produk']) ?></span>
              <span class="badge rounded-pill bg-success bg-opacity-10 text-success border border-success-subtle">
                <?= (int)$pt['terjual'] ?> pcs
              </span>
            </li>
          <?php endforeach; ?>
        </ol>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="bg-white border rounded-3 p-4 shadow-sm">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h2 class="h6 fw-bold mb-0">Grafik Margin Keuntungan 7 Hari Terakhir</h2>
      <div class="small text-muted">Selisih Harga Jual dan Harga Produksi dari item transaksi tercatat.</div>
    </div>
  </div>
  <div style="position: relative; min-height: 240px;">
    <canvas id="marginChart7Days"></canvas>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
  (function () {
    if (typeof Chart === 'undefined') return;
    const labels = <?= json_encode($chartLabels) ?>;

    const salesCanvas = document.getElementById('salesChart7Days');
    if (salesCanvas) {
      new Chart(salesCanvas, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [{
            label: 'Penjualan (Rp)',
            data: <?= json_encode($chartSalesTotals) ?>,
            backgroundColor: 'rgba(22, 101, 52, 0.78)',
            borderColor: '#166534',
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
                callback: function (val) {
                  return 'Rp ' + Number(val).toLocaleString('id-ID');
                }
              }
            }
          }
        }
      });
    }

    const marginCanvas = document.getElementById('marginChart7Days');
    if (marginCanvas) {
      new Chart(marginCanvas, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [{
            label: 'Margin Keuntungan (Rp)',
            data: <?= json_encode($chartMarginTotals) ?>,
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13, 110, 253, 0.14)',
            borderWidth: 2.5,
            fill: true,
            tension: 0.3,
            pointRadius: 4,
            pointBackgroundColor: '#0d6efd'
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
                callback: function (val) {
                  return 'Rp ' + Number(val).toLocaleString('id-ID');
                }
              }
            }
          }
        }
      });
    }
  })();
</script>

<?php require_once __DIR__ . '/_footer.php'; ?>