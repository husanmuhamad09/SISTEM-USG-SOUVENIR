<?php
$adminTitle = 'Data Pelanggan — USGallery Admin';
require_once __DIR__ . '/_layout.php';

$qPel = trim($_GET['q'] ?? '');

$sql = "
    SELECT pl.*,
           COUNT(t.id_transaksi) AS jumlah_transaksi,
           COALESCE(SUM(t.total_transaksi), 0) AS total_belanja,
           MAX(t.tanggal_transaksi) AS transaksi_terakhir
    FROM pelanggan pl
    LEFT JOIN transaksi t ON pl.id_pelanggan = t.id_pelanggan
    WHERE 1=1
";
$params = [];

if ($qPel !== '') {
    $sql .= " AND (pl.nama_pelanggan LIKE :q OR pl.no_telepon LIKE :q OR pl.alamat LIKE :q)";
    $params[':q'] = '%' . $qPel . '%';
}

$sql .= " GROUP BY pl.id_pelanggan ORDER BY pl.id_pelanggan DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pelangganList = $stmt->fetchAll();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1">Data Pelanggan</h1>
    <p class="text-muted small mb-0">Daftar pemesan yang tercatat dari checkout WhatsApp maupun transaksi admin.</p>
  </div>
  <a href="kelola_pesanan.php" class="btn btn-sm btn-outline-success rounded-pill px-3 fw-semibold">
    Lihat Kelola Pesanan
  </a>
</div>

<div class="bg-white border rounded-3 p-4 shadow-sm mb-4">
  <form method="get" action="pelanggan.php" class="row g-2 align-items-center">
    <div class="col-sm-9 col-md-10">
      <input type="text" name="q" value="<?= e($qPel) ?>" class="form-control form-control-sm"
             placeholder="Cari nama pelanggan, no. telepon/WA, atau alamat...">
    </div>
    <div class="col-sm-3 col-md-2 d-flex gap-1">
      <button type="submit" class="btn btn-sm btn-success w-100 rounded-pill">Cari</button>
      <?php if ($qPel !== ''): ?>
        <a href="pelanggan.php" class="btn btn-sm btn-outline-secondary rounded-pill px-2">Reset</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<div class="bg-white border rounded-3 p-4 shadow-sm">
  <div class="table-responsive">
    <table class="tbl">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nama Pelanggan</th>
          <th>No. Telepon / WA</th>
          <th>Alamat</th>
          <th>Transaksi</th>
          <th>Total Belanja</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($pelangganList)): ?>
          <tr>
            <td colspan="6" class="text-center text-muted py-4">Belum ada data pelanggan tercatat.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($pelangganList as $pl): ?>
            <tr>
              <td><?= (int)$pl['id_pelanggan'] ?></td>
              <td class="fw-bold"><?= e($pl['nama_pelanggan'] ?? '-') ?></td>
              <td><?= e($pl['no_telepon'] ?: '-') ?></td>
              <td class="small text-muted"><?= e($pl['alamat'] ?: '-') ?></td>
              <td><span class="pill"><?= (int)($pl['jumlah_transaksi'] ?? 0) ?>x</span></td>
              <td class="fw-bold text-success"><?= e(rp((float)($pl['total_belanja'] ?? 0))) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/_footer.php'; ?>