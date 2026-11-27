<?php
$adminTitle = 'Transaksi Baru — USGallery Admin';
require_once __DIR__ . '/_layout.php';

if (!isset($_SESSION['admin_pos_cart']) || !is_array($_SESSION['admin_pos_cart'])) {
    $_SESSION['admin_pos_cart'] = [];
}

$hasDetProdCol = false;
$hasProdProdCol = false;
try {
    $hasDetProdCol = (bool)$pdo->query("SHOW COLUMNS FROM detail_transaksi LIKE 'harga_produksi'")->fetch();
    $hasProdProdCol = (bool)$pdo->query("SHOW COLUMNS FROM produk LIKE 'harga_produksi'")->fetch();
} catch (Throwable $e) {
    $hasDetProdCol = false;
    $hasProdProdCol = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posAct = $_POST['pos_act'] ?? '';

    if ($posAct === 'add_item') {
        $idProd = isset($_POST['id_produk']) ? (int)$_POST['id_produk'] : 0;
        $qtyAdd = isset($_POST['qty']) ? max(1, (int)$_POST['qty']) : 1;

        $st = $pdo->prepare("SELECT id_produk, nama_produk, stok FROM produk WHERE id_produk = :id LIMIT 1");
        $st->execute([':id' => $idProd]);
        $pr = $st->fetch();

        if (!$pr || (int)$pr['stok'] <= 0) {
            flash('Produk tidak tersedia atau stok habis.');
        } else {
            $maxStok = (int)$pr['stok'];
            $curQty = (int)($_SESSION['admin_pos_cart'][$idProd] ?? 0);
            $_SESSION['admin_pos_cart'][$idProd] = min($maxStok, $curQty + $qtyAdd);
            flash('"' . $pr['nama_produk'] . '" ditambahkan ke keranjang transaksi.');
        }
        header('Location: transaksi.php');
        exit;
    }

    if ($posAct === 'set_qty') {
        $idProd = isset($_POST['id_produk']) ? (int)$_POST['id_produk'] : 0;
        $newQty = isset($_POST['qty']) ? (int)$_POST['qty'] : 0;

        if ($newQty <= 0) {
            unset($_SESSION['admin_pos_cart'][$idProd]);
        } else {
            $st = $pdo->prepare("SELECT stok FROM produk WHERE id_produk = :id LIMIT 1");
            $st->execute([':id' => $idProd]);
            $maxStok = (int)($st->fetchColumn() ?? 0);
            if ($maxStok > 0) {
                $_SESSION['admin_pos_cart'][$idProd] = min($maxStok, $newQty);
            } else {
                unset($_SESSION['admin_pos_cart'][$idProd]);
            }
        }
        header('Location: transaksi.php');
        exit;
    }

    if ($posAct === 'clear_cart') {
        $_SESSION['admin_pos_cart'] = [];
        flash('Keranjang transaksi dikosongkan.');
        header('Location: transaksi.php');
        exit;
    }

    if ($posAct === 'pay_now') {
        if (empty($_SESSION['admin_pos_cart'])) {
            flash('Keranjang transaksi masih kosong.');
            header('Location: transaksi.php');
            exit;
        }

        $namaPel = trim($_POST['nama_pelanggan'] ?? '');
        $noTelp = trim($_POST['no_telepon'] ?? '');
        $alamatPel = trim($_POST['alamat'] ?? '');
        $metode = 'Tunai (Cash)';

        $ids = array_map('intval', array_keys($_SESSION['admin_pos_cart']));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stProd = $pdo->prepare("SELECT * FROM produk WHERE id_produk IN ($placeholders)");
        $stProd->execute($ids);

        $prodMap = [];
        foreach ($stProd->fetchAll() as $r) {
            $prodMap[(int)$r['id_produk']] = $r;
        }

        $items = [];
        $totalBayar = 0.0;

        foreach ($_SESSION['admin_pos_cart'] as $pid => $qVal) {
            $pid = (int)$pid;
            $qVal = (int)$qVal;
            if ($qVal <= 0 || !isset($prodMap[$pid])) continue;

            $pRow = $prodMap[$pid];
            if ($qVal > (int)$pRow['stok']) {
                flash('Stok "' . $pRow['nama_produk'] . '" tidak mencukupi (tersedia: ' . (int)$pRow['stok'] . ').');
                header('Location: transaksi.php');
                exit;
            }

            $hargaJual = (float)$pRow['harga'];
            $hargaProd = $hasProdProdCol ? (float)($pRow['harga_produksi'] ?? 0) : 0.0;
            $subtotal = $hargaJual * $qVal;

            $items[] = [
                'id_produk'      => $pid,
                'nama_produk'    => $pRow['nama_produk'],
                'qty'            => $qVal,
                'harga'          => $hargaJual,
                'harga_produksi' => $hargaProd,
                'subtotal'       => $subtotal
            ];
            $totalBayar += $subtotal;
        }

        if (empty($items)) {
            flash('Tidak ada item valid untuk diproses.');
            header('Location: transaksi.php');
            exit;
        }

        $nextNum = ((int)$pdo->query("SELECT COALESCE(MAX(id_transaksi), 0) FROM transaksi")->fetchColumn()) + 1;
        $kodePesanan = sprintf('ORD-%s-%04d', date('Y'), $nextNum);
        $adminId = (int)($_SESSION['admin']['id_admin'] ?? 0) ?: null;

        $pdo->beginTransaction();
        try {
            $stPel = $pdo->prepare("
                INSERT INTO pelanggan (nama_pelanggan, no_telepon, alamat)
                VALUES (:nama, :telp, :alamat)
            ");
            $stPel->execute([
                ':nama'   => $namaPel !== '' ? $namaPel : 'Pelanggan Walk-in',
                ':telp'   => $noTelp !== '' ? $noTelp : '-',
                ':alamat' => $alamatPel !== '' ? $alamatPel : 'Toko / Kampus UNUSA'
            ]);
            $idPelanggan = (int)$pdo->lastInsertId();

            $stTrx = $pdo->prepare("
                INSERT INTO transaksi (kode, id_admin, id_pelanggan, total_transaksi, status, sumber)
                VALUES (:kode, :id_admin, :id_pel, :total, 'Sudah Diambil', 'Admin')
            ");
            $stTrx->execute([
                ':kode'     => $kodePesanan,
                ':id_admin' => $adminId,
                ':id_pel'   => $idPelanggan,
                ':total'    => $totalBayar
            ]);
            $idTrx = (int)$pdo->lastInsertId();

            if ($hasDetProdCol) {
                $stDet = $pdo->prepare("
                    INSERT INTO detail_transaksi (id_transaksi, id_produk, jumlah, harga, harga_produksi, subtotal)
                    VALUES (:id_trx, :id_prod, :jml, :harga, :h_prod, :sub)
                ");
            } else {
                $stDet = $pdo->prepare("
                    INSERT INTO detail_transaksi (id_transaksi, id_produk, jumlah, harga, subtotal)
                    VALUES (:id_trx, :id_prod, :jml, :harga, :sub)
                ");
            }

            $stUpdStok = $pdo->prepare("UPDATE produk SET stok = stok - :qty WHERE id_produk = :id_prod");

            foreach ($items as $it) {
                if ($hasDetProdCol) {
                    $stDet->execute([
                        ':id_trx'  => $idTrx,
                        ':id_prod' => $it['id_produk'],
                        ':jml'     => $it['qty'],
                        ':harga'   => $it['harga'],
                        ':h_prod'  => $it['harga_produksi'],
                        ':sub'     => $it['subtotal']
                    ]);
                } else {
                    $stDet->execute([
                        ':id_trx'  => $idTrx,
                        ':id_prod' => $it['id_produk'],
                        ':jml'     => $it['qty'],
                        ':harga'   => $it['harga'],
                        ':sub'     => $it['subtotal']
                    ]);
                }

                $stUpdStok->execute([
                    ':qty'     => $it['qty'],
                    ':id_prod' => $it['id_produk']
                ]);
            }

            $pdo->commit();
            $_SESSION['admin_pos_cart'] = [];
            $_SESSION['last_pos_order'] = [
                'kode'   => $kodePesanan,
                'total'  => rp($totalBayar),
                'metode' => $metode
            ];
            flash('Transaksi berhasil disimpan dengan Kode Pesanan: ' . $kodePesanan);
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash('Gagal memproses transaksi: ' . $e->getMessage());
        }

        header('Location: transaksi.php');
        exit;
    }
}

$lastPosOrder = $_SESSION['last_pos_order'] ?? null;
if ($lastPosOrder) {
    unset($_SESSION['last_pos_order']);
}

$kategoriList = $pdo->query("SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori ASC")->fetchAll();
$qPos = trim($_GET['q'] ?? '');
$katPos = isset($_GET['kategori']) && $_GET['kategori'] !== '' ? (int)$_GET['kategori'] : 0;

$sqlProd = "
    SELECT p.*, k.nama_kategori
    FROM produk p
    LEFT JOIN kategori k ON p.id_kategori = k.id_kategori
    WHERE 1=1
";
$paramsProd = [];
if ($katPos > 0) {
    $sqlProd .= " AND p.id_kategori = :kat";
    $paramsProd[':kat'] = $katPos;
}
if ($qPos !== '') {
    $sqlProd .= " AND (p.nama_produk LIKE :q OR k.nama_kategori LIKE :q)";
    $paramsProd[':q'] = '%' . $qPos . '%';
}
$sqlProd .= " ORDER BY p.id_produk ASC";
$stAllProd = $pdo->prepare($sqlProd);
$stAllProd->execute($paramsProd);
$katalogPos = $stAllProd->fetchAll();

// Ambil semua produk untuk hitung keranjang aktif
$allProdMap = [];
foreach ($pdo->query("SELECT * FROM produk")->fetchAll() as $ap) {
    $allProdMap[(int)$ap['id_produk']] = $ap;
}

$posCartRows = [];
$posGrandTotal = 0.0;
$posTotalQty = 0;
foreach ($_SESSION['admin_pos_cart'] as $pid => $qVal) {
    $pid = (int)$pid;
    $qVal = (int)$qVal;
    if ($qVal > 0 && isset($allProdMap[$pid])) {
        $p = $allProdMap[$pid];
        $sub = (float)$p['harga'] * $qVal;
        $posCartRows[] = [
            'id_produk'   => $pid,
            'nama_produk' => $p['nama_produk'],
            'harga'       => (float)$p['harga'],
            'stok'        => (int)$p['stok'],
            'qty'         => $qVal,
            'subtotal'    => $sub
        ];
        $posGrandTotal += $sub;
        $posTotalQty += $qVal;
    }
}
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h1 class="h3 fw-bold mb-1">Transaksi Baru</h1>
    <p class="text-muted small mb-0">Input transaksi langsung oleh admin dengan pembuatan kode pesanan otomatis.</p>
  </div>
  <a href="kelola_pesanan.php" class="btn btn-sm btn-outline-success rounded-pill px-3 fw-semibold">
    Lihat Kelola Pesanan
  </a>
</div>

<?php if ($lastPosOrder): ?>
  <div class="alert alert-success border-success rounded-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
      <strong>Transaksi Tercatat!</strong> Kode Pesanan:
      <span class="badge bg-success fs-6"><?= e($lastPosOrder['kode']) ?></span>
      &bull; Metode: <strong><?= e($lastPosOrder['metode']) ?></strong>
      &bull; Total: <strong><?= e($lastPosOrder['total']) ?></strong>
    </div>
    <a href="../lacak.php?kode=<?= urlencode($lastPosOrder['kode']) ?>" target="_blank"
       class="btn btn-sm btn-outline-success rounded-pill px-3">
      Cek di Lacak Pesanan
    </a>
  </div>
<?php endif; ?>

<div class="row g-4 align-items-start">
  <!-- Katalog Produk Kasir -->
  <div class="col-lg-8">
    <div class="bg-white border rounded-3 p-3 shadow-sm mb-3">
      <form method="get" action="transaksi.php" class="row g-2">
        <div class="col-sm-6">
          <input type="text" name="q" value="<?= e($qPos) ?>" class="form-control form-control-sm"
                 placeholder="Cari produk atau kategori...">
        </div>
        <div class="col-sm-4">
          <select name="kategori" class="form-select form-select-sm">
            <option value="">Semua Kategori</option>
            <?php foreach ($kategoriList as $kat): ?>
              <option value="<?= (int)$kat['id_kategori'] ?>" <?= $katPos === (int)$kat['id_kategori'] ? 'selected' : '' ?>>
                <?= e($kat['nama_kategori']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-sm-2 d-flex gap-1">
          <button type="submit" class="btn btn-sm btn-success w-100 rounded-3">Cari</button>
          <?php if ($qPos !== '' || $katPos > 0): ?>
            <a href="transaksi.php" class="btn btn-sm btn-outline-secondary rounded-3">Reset</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <div class="row g-3">
      <?php foreach ($katalogPos as $p): ?>
        <?php
          $pid = (int)$p['id_produk'];
          $stok = (int)$p['stok'];
          $inCart = (int)($_SESSION['admin_pos_cart'][$pid] ?? 0);
          $imgFile = !empty($p['gambar']) ? basename((string)$p['gambar']) : '';
          $hasImg = $imgFile !== '' && is_file(__DIR__ . '/../uploads/' . $imgFile);
        ?>
        <div class="col-sm-6 col-md-4">
          <div class="card h-100 border rounded-3 shadow-sm">
            <div class="bg-light d-flex align-items-center justify-content-center border-bottom"
                 style="height: 130px; overflow: hidden;">
              <?php if ($hasImg): ?>
                <img src="../uploads/<?= e($imgFile) ?>" alt="<?= e($p['nama_produk']) ?>" class="w-100 h-100 object-fit-cover">
              <?php else: ?>
                <span class="text-muted small"><?= e($p['nama_kategori'] ?? 'Produk') ?></span>
              <?php endif; ?>
            </div>
            <div class="card-body p-3 d-flex flex-column">
              <div class="fw-bold small mb-1"><?= e($p['nama_produk']) ?></div>
              <div class="fw-bold text-success small mb-1"><?= e(rp((float)$p['harga'])) ?></div>
              <div class="text-muted mb-2" style="font-size: 0.78rem;">Stok: <?= $stok ?></div>

              <div class="mt-auto">
                <?php if ($stok <= 0): ?>
                  <button type="button" class="btn btn-sm btn-secondary w-100 rounded-pill" disabled>Stok Habis</button>
                <?php elseif ($inCart > 0): ?>
                  <form method="post" action="transaksi.php" class="d-flex align-items-center justify-content-between gap-1 m-0">
                    <input type="hidden" name="pos_act" value="set_qty">
                    <input type="hidden" name="id_produk" value="<?= $pid ?>">
                    <button type="submit" name="qty" value="<?= $inCart - 1 ?>"
                            class="btn btn-sm btn-outline-secondary rounded-circle" style="width: 28px; height: 28px; padding: 0;">-</button>
                    <span class="small fw-bold"><?= $inCart ?></span>
                    <button type="submit" name="qty" value="<?= min($stok, $inCart + 1) ?>"
                            class="btn btn-sm btn-outline-secondary rounded-circle" style="width: 28px; height: 28px; padding: 0;">+</button>
                  </form>
                <?php else: ?>
                  <form method="post" action="transaksi.php" class="m-0">
                    <input type="hidden" name="pos_act" value="add_item">
                    <input type="hidden" name="id_produk" value="<?= $pid ?>">
                    <input type="hidden" name="qty" value="1">
                    <button type="submit" class="btn btn-sm btn-success w-100 rounded-pill">+ Tambah</button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Keranjang Transaksi Admin -->
  <div class="col-lg-4">
    <div class="bg-white border rounded-3 p-4 shadow-sm">
      <h2 class="h6 fw-bold mb-3">Keranjang Belanja Admin</h2>

      <?php if (empty($posCartRows)): ?>
        <div class="text-center text-muted small py-4 border rounded-3 bg-light mb-3">
          Keranjang masih kosong. Klik <strong>+ Tambah</strong> pada produk.
        </div>
      <?php else: ?>
        <div class="mb-3">
          <?php foreach ($posCartRows as $r): ?>
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom small">
              <div>
                <div class="fw-bold"><?= e($r['nama_produk']) ?></div>
                <div class="text-muted"><?= (int)$r['qty'] ?> x <?= e(rp($r['harga'])) ?></div>
              </div>
              <div class="d-flex align-items-center gap-2">
                <span class="fw-bold"><?= e(rp($r['subtotal'])) ?></span>
                <form method="post" action="transaksi.php" class="m-0">
                  <input type="hidden" name="pos_act" value="set_qty">
                  <input type="hidden" name="id_produk" value="<?= (int)$r['id_produk'] ?>">
                  <input type="hidden" name="qty" value="0">
                  <button type="submit" class="btn btn-sm btn-link text-danger p-0 text-decoration-none">&times;</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="post" action="transaksi.php">
        <input type="hidden" name="pos_act" value="pay_now">
        <input type="hidden" name="metode_pembayaran" value="Tunai (Cash)">

        <div class="mb-2">
          <label class="form-label small fw-semibold mb-1">Nama Pelanggan (Opsional)</label>
          <input type="text" name="nama_pelanggan" class="form-control form-control-sm"
                 placeholder="Kosongkan jika pembeli walk-in">
        </div>
        <div class="mb-2">
          <label class="form-label small fw-semibold mb-1">No. Telepon / WA (Opsional)</label>
          <input type="text" name="no_telepon" class="form-control form-control-sm" placeholder="08...">
        </div>
        <div class="mb-2">
          <label class="form-label small fw-semibold mb-1">Alamat (Opsional)</label>
          <input type="text" name="alamat" class="form-control form-control-sm" placeholder="Surabaya / Kampus UNUSA">
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold mb-1">Metode Pembayaran</label>
          <input type="text" value="Tunai (Cash)" class="form-control form-control-sm bg-light" readonly>
        </div>

        <hr>

        <div class="d-flex justify-content-between small mb-1">
          <span class="text-muted">Total Item</span>
          <span class="fw-bold"><?= $posTotalQty ?> pcs</span>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <span class="fw-semibold">Total</span>
          <span class="fs-5 fw-extrabold text-success"><?= e(rp($posGrandTotal)) ?></span>
        </div>

        <button type="submit" class="btn btn-success w-100 py-2 rounded-pill fw-semibold mb-2"
                <?= empty($posCartRows) ? 'disabled' : '' ?>>
          Bayar Sekarang
        </button>
      </form>

      <?php if (!empty($posCartRows)): ?>
        <form method="post" action="transaksi.php" class="m-0">
          <input type="hidden" name="pos_act" value="clear_cart">
          <button type="submit" class="btn btn-sm btn-outline-secondary w-100 rounded-pill">
            Kosongkan Keranjang
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/_footer.php'; ?>