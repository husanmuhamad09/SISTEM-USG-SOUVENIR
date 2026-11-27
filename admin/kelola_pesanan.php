<?php
$adminTitle = 'Kelola Pesanan — USGallery Admin';
require_once __DIR__ . '/_layout.php';

$allowedStatuses = [
    'Menunggu Pembayaran',
    'Sedang Diproses',
    'Siap Diambil',
    'Sudah Diambil'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';

    if ($act === 'update_status') {
        $idTrx = isset($_POST['id_transaksi']) ? (int)$_POST['id_transaksi'] : 0;
        $statusBaru = trim($_POST['status'] ?? '');

        if ($idTrx > 0 && in_array($statusBaru, $allowedStatuses, true)) {
            $adminId = (int)($_SESSION['admin']['id_admin'] ?? 0) ?: null;
            $st = $pdo->prepare("
                UPDATE transaksi
                SET status = :status,
                    id_admin = COALESCE(id_admin, :id_admin)
                WHERE id_transaksi = :id
            ");
            $st->execute([
                ':status'   => $statusBaru,
                ':id_admin' => $adminId,
                ':id'       => $idTrx
            ]);
            flash('Status pesanan #' . $idTrx . ' diperbarui menjadi "' . $statusBaru . '".');
        }
        header('Location: kelola_pesanan.php' . (!empty($_GET['q']) ? '?q=' . urlencode($_GET['q']) : ''));
        exit;
    }

    if ($act === 'delete_order') {
        $idTrx = isset($_POST['id_transaksi']) ? (int)$_POST['id_transaksi'] : 0;
        if ($idTrx > 0) {
            $pdo->beginTransaction();
            try {
                $pdo->prepare("DELETE FROM detail_transaksi WHERE id_transaksi = :id")->execute([':id' => $idTrx]);
                $pdo->prepare("DELETE FROM transaksi WHERE id_transaksi = :id")->execute([':id' => $idTrx]);
                $pdo->commit();
                flash('Data pesanan berhasil dihapus.');
            } catch (Throwable $e) {
                $pdo->rollBack();
                flash('Gagal menghapus pesanan.');
            }
        }
        header('Location: kelola_pesanan.php');
        exit;
    }
}

$qOrder = trim($_GET['q'] ?? '');
$sql = "
    SELECT t.*, pl.nama_pelanggan, pl.no_telepon, pl.alamat,
           (SELECT COALESCE(SUM(dt.jumlah), 0) FROM detail_transaksi dt WHERE dt.id_transaksi = t.id_transaksi) AS total_item,
           (SELECT GROUP_CONCAT(CONCAT(pr.nama_produk, ' (', dt.jumlah, ')') SEPARATOR ', ')
            FROM detail_transaksi dt
            LEFT JOIN produk pr ON dt.id_produk = pr.id_produk
            WHERE dt.id_transaksi = t.id_transaksi) AS ringkasan_item
    FROM transaksi t
    LEFT JOIN pelanggan pl ON t.id_pelanggan = pl.id_pelanggan
    WHERE 1=1
";
$params = [];

if ($qOrder !== '') {
    $sql .= " AND (t.kode LIKE :q OR pl.nama_pelanggan LIKE :q OR pl.no_telepon LIKE :q)";
    $params[':q'] = '%' . $qOrder . '%';
}

$sql .= " ORDER BY t.id_transaksi DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1">Kelola Pesanan</h1>
    <p class="text-muted small mb-0">Pantau dan perbarui status pesanan pembeli agar langsung tampil di halaman Lacak Pesanan.</p>
  </div>
  <a href="transaksi.php" class="btn btn-sm btn-success rounded-pill px-3 fw-semibold">+ Transaksi Baru</a>
</div>

<div class="bg-white border rounded-3 p-4 shadow-sm mb-4">
  <form method="get" action="kelola_pesanan.php" class="row g-2 align-items-center">
    <div class="col-sm-9 col-md-10">
      <input type="text" name="q" value="<?= e($qOrder) ?>" class="form-control form-control-sm"
             placeholder="Cari ID / Kode Pesanan (misal: ORD-2026-0002), nama pemesan, atau no. WA...">
    </div>
    <div class="col-sm-3 col-md-2 d-flex gap-1">
      <button type="submit" class="btn btn-sm btn-success w-100 rounded-pill">Cari</button>
      <?php if ($qOrder !== ''): ?>
        <a href="kelola_pesanan.php" class="btn btn-sm btn-outline-secondary rounded-pill px-2">Reset</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<div class="bg-white border rounded-3 p-4 shadow-sm">
  <div class="table-responsive">
    <table class="tbl">
      <thead>
        <tr>
          <th>Kode Pesanan</th>
          <th>Pemesan</th>
          <th>Item</th>
          <th>Total</th>
          <th>Sumber</th>
          <th>Status &amp; Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($orders)): ?>
          <tr>
            <td colspan="6" class="text-center text-muted py-4">Belum ada data pesanan.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($orders as $ord): ?>
            <?php $curStatus = (string)($ord['status'] ?? 'Menunggu Pembayaran'); ?>
            <tr>
              <td>
                <div class="fw-bold text-success"><?= e($ord['kode']) ?></div>
                <div class="small text-muted"><?= e($ord['tanggal_transaksi'] ?? '') ?></div>
                <a href="../lacak.php?kode=<?= urlencode((string)$ord['kode']) ?>" target="_blank"
                   class="small text-decoration-underline">Lihat Lacak</a>
              </td>
              <td>
                <div class="fw-semibold"><?= e($ord['nama_pelanggan'] ?? 'Pelanggan Langsung') ?></div>
                <?php if (!empty($ord['no_telepon'])): ?>
                  <div class="small text-muted"><?= e($ord['no_telepon']) ?></div>
                <?php endif; ?>
                <?php if (!empty($ord['alamat'])): ?>
                  <div class="small text-muted"><?= e($ord['alamat']) ?></div>
                <?php endif; ?>
              </td>
              <td class="small">
                <div class="fw-semibold"><?= (int)($ord['total_item'] ?? 0) ?> pcs</div>
                <div class="text-muted"><?= e($ord['ringkasan_item'] ?? '-') ?></div>
              </td>
              <td class="fw-bold"><?= e(rp((float)($ord['total_transaksi'] ?? 0))) ?></td>
              <td><span class="pill"><?= e($ord['sumber'] ?? 'WhatsApp') ?></span></td>
              <td style="min-width: 230px;">
                <form method="post" action="kelola_pesanan.php" class="d-flex gap-1 mb-1">
                  <input type="hidden" name="act" value="update_status">
                  <input type="hidden" name="id_transaksi" value="<?= (int)$ord['id_transaksi'] ?>">
                  <select name="status" class="form-select form-select-sm">
                    <?php foreach ($allowedStatuses as $stOpt): ?>
                      <option value="<?= e($stOpt) ?>" <?= $curStatus === $stOpt ? 'selected' : '' ?>>
                        <?= e($stOpt) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit" class="btn btn-sm btn-success px-2">Simpan</button>
                </form>
                <form method="post" action="kelola_pesanan.php" class="m-0"
                      onsubmit="return confirm('Hapus pesanan <?= e($ord['kode']) ?>?');">
                  <input type="hidden" name="act" value="delete_order">
                  <input type="hidden" name="id_transaksi" value="<?= (int)$ord['id_transaksi'] ?>">
                  <button type="submit" class="btn btn-sm btn-link text-danger p-0 small text-decoration-none">
                    Hapus
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/_footer.php'; ?>
