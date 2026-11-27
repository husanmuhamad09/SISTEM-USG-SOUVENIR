<?php
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        $idProduk = isset($_POST['id_produk']) ? (int)$_POST['id_produk'] : 0;
        $qty = isset($_POST['qty']) ? max(1, (int)$_POST['qty']) : 1;

        $stmt = $pdo->prepare("SELECT id_produk, nama_produk, harga, stok FROM produk WHERE id_produk = :id LIMIT 1");
        $stmt->execute([':id' => $idProduk]);
        $prod = $stmt->fetch();

        if (!$prod) {
            flash('Produk tidak ditemukan.');
            header('Location: produk.php');
            exit;
        }

        if ((int)$prod['stok'] <= 0) {
            flash('Maaf, stok produk sedang habis.');
            header('Location: detail.php?id=' . $idProduk);
            exit;
        }

        $maxQty = (int)$prod['stok'];
        if (isset($_SESSION['cart'][$idProduk])) {
            $_SESSION['cart'][$idProduk]['qty'] = min($maxQty, (int)$_SESSION['cart'][$idProduk]['qty'] + $qty);
            $_SESSION['cart'][$idProduk]['selected'] = true;
        } else {
            $_SESSION['cart'][$idProduk] = [
                'id_produk' => (int)$prod['id_produk'],
                'qty' => min($maxQty, $qty),
                'selected' => true
            ];
        }

        flash('Produk "' . $prod['nama_produk'] . '" ditambahkan ke keranjang.');
        header('Location: keranjang.php');
        exit;
    }

    if ($action === 'update_qty') {
        $idProduk = isset($_POST['id_produk']) ? (int)$_POST['id_produk'] : 0;
        $qty = isset($_POST['qty']) ? (int)$_POST['qty'] : 1;

        if (isset($_SESSION['cart'][$idProduk])) {
            if ($qty <= 0) {
                unset($_SESSION['cart'][$idProduk]);
                flash('Item dihapus dari keranjang.');
            } else {
                $stmt = $pdo->prepare("SELECT stok FROM produk WHERE id_produk = :id LIMIT 1");
                $stmt->execute([':id' => $idProduk]);
                $maxStok = max(1, (int)($stmt->fetchColumn() ?? 99));
                $_SESSION['cart'][$idProduk]['qty'] = min($maxStok, $qty);
            }
        }
        header('Location: keranjang.php');
        exit;
    }

    if ($action === 'remove') {
        $idProduk = isset($_POST['id_produk']) ? (int)$_POST['id_produk'] : 0;
        unset($_SESSION['cart'][$idProduk]);
        flash('Produk dihapus dari keranjang.');
        header('Location: keranjang.php');
        exit;
    }

    if ($action === 'clear') {
        $_SESSION['cart'] = [];
        flash('Keranjang berhasil dikosongkan.');
        header('Location: keranjang.php');
        exit;
    }

    if ($action === 'checkout_wa') {
        $nama = trim($_POST['nama_pelanggan'] ?? '');
        $telepon = trim($_POST['no_telepon'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $catatan = trim($_POST['catatan'] ?? '');
        $selectedIds = isset($_POST['selected_ids']) && is_array($_POST['selected_ids'])
            ? array_values(array_unique(array_map('intval', $_POST['selected_ids'])))
            : [];

        if ($nama === '' || $telepon === '' || $alamat === '') {
            flash('Mohon lengkapi Nama, No. WhatsApp, dan Alamat terlebih dahulu.');
            header('Location: keranjang.php');
            exit;
        }

        if (empty($selectedIds) || empty($_SESSION['cart'])) {
            flash('Pilih minimal 1 produk di keranjang untuk dipesan.');
            header('Location: keranjang.php');
            exit;
        }

        $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
        $stmt = $pdo->prepare("SELECT id_produk, nama_produk, harga, stok FROM produk WHERE id_produk IN ($placeholders)");
        $stmt->execute($selectedIds);
        $dbProducts = [];
        foreach ($stmt->fetchAll() as $row) {
            $dbProducts[(int)$row['id_produk']] = $row;
        }

        $itemsToOrder = [];
        $totalTransaksi = 0.0;
        $totalQty = 0;

        foreach ($selectedIds as $pid) {
            if (isset($_SESSION['cart'][$pid], $dbProducts[$pid])) {
                $prod = $dbProducts[$pid];
                $qty = max(1, (int)($_SESSION['cart'][$pid]['qty'] ?? 1));
                $harga = (float)$prod['harga'];
                $subtotal = $harga * $qty;

                $itemsToOrder[] = [
                    'id_produk' => $pid,
                    'nama_produk' => $prod['nama_produk'],
                    'qty' => $qty,
                    'harga' => $harga,
                    'subtotal' => $subtotal
                ];
                $totalTransaksi += $subtotal;
                $totalQty += $qty;
            }
        }

        if (empty($itemsToOrder)) {
            flash('Produk terpilih tidak valid atau sudah kosong.');
            header('Location: keranjang.php');
            exit;
        }

        $nextNum = ((int)$pdo->query("SELECT COALESCE(MAX(id_transaksi), 0) FROM transaksi")->fetchColumn()) + 1;
        $kodePesanan = sprintf('ORD-%s-%04d', date('Y'), $nextNum);

        $pdo->beginTransaction();
        try {
            $stmtPel = $pdo->prepare("INSERT INTO pelanggan (nama_pelanggan, no_telepon, alamat) VALUES (:nama, :telp, :alamat)");
            $stmtPel->execute([
                ':nama' => $nama,
                ':telp' => $telepon,
                ':alamat' => $alamat
            ]);
            $idPelanggan = (int)$pdo->lastInsertId();

            $stmtTrx = $pdo->prepare("
                INSERT INTO transaksi (kode, id_admin, id_pelanggan, total_transaksi, status, sumber)
                VALUES (:kode, NULL, :id_pelanggan, :total, 'Menunggu Pembayaran', 'WhatsApp')
            ");
            $stmtTrx->execute([
                ':kode' => $kodePesanan,
                ':id_pelanggan' => $idPelanggan,
                ':total' => $totalTransaksi
            ]);
            $idTransaksi = (int)$pdo->lastInsertId();

            $stmtDet = $pdo->prepare("
                INSERT INTO detail_transaksi (id_transaksi, id_produk, jumlah, harga, subtotal)
                VALUES (:id_trx, :id_prod, :jml, :harga, :sub)
            ");
            foreach ($itemsToOrder as $it) {
                $stmtDet->execute([
                    ':id_trx' => $idTransaksi,
                    ':id_prod' => $it['id_produk'],
                    ':jml' => $it['qty'],
                    ':harga' => $it['harga'],
                    ':sub' => $it['subtotal']
                ]);
                unset($_SESSION['cart'][$it['id_produk']]);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash('Gagal menyimpan pesanan: ' . $e->getMessage());
            header('Location: keranjang.php');
            exit;
        }

        $lines = [];
        foreach ($itemsToOrder as $idx => $it) {
            $ketHarga = $it['harga'] > 0 ? rp($it['subtotal']) : 'Harga via Konsultasi';
            $lines[] = ($idx + 1) . '. ' . $it['nama_produk'] . ' (' . $it['qty'] . ' pcs) - ' . $ketHarga;
        }

        $waText = "Halo Admin *USGallery (UNUSA Souvenir Gallery)*, saya ingin memesan:\n\n"
            . "Kode Pesanan: *{$kodePesanan}*\n"
            . implode("\n", $lines) . "\n\n"
            . "Total Barang: *{$totalQty} pcs*\n"
            . "Estimasi Total: *" . rp($totalTransaksi) . "*\n\n"
            . "Nama Pemesan: {$nama}\n"
            . "No. WA: {$telepon}\n"
            . "Alamat: {$alamat}"
            . ($catatan !== '' ? "\nCatatan: {$catatan}" : '')
            . "\n\nMohon informasi konfirmasi dan proses selanjutnya. Terima kasih.";

        $waClean = preg_replace('/[^0-9]/', '', WA_NUMBER);
        $_SESSION['last_order'] = [
            'kode' => $kodePesanan,
            'nama' => $nama,
            'total_qty' => $totalQty,
            'total_harga' => rp($totalTransaksi),
            'wa_url' => 'https://wa.me/' . $waClean . '?text=' . rawurlencode($waText)
        ];

        header('Location: keranjang.php?ordered=1');
        exit;
    }
}

$pageTitle = 'Keranjang Belanja — USGallery';
require_once __DIR__ . '/includes/layout.php';

$lastOrder = null;
if (isset($_GET['ordered']) && !empty($_SESSION['last_order']) && is_array($_SESSION['last_order'])) {
    $lastOrder = $_SESSION['last_order'];
}

$cartItems = [];
if (!empty($_SESSION['cart'])) {
    $ids = array_map('intval', array_keys($_SESSION['cart']));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("
        SELECT p.*, k.nama_kategori
        FROM produk p
        LEFT JOIN kategori k ON p.id_kategori = k.id_kategori
        WHERE p.id_produk IN ($placeholders)
    ");
    $stmt->execute($ids);
    $rows = $stmt->fetchAll();

    $byId = [];
    foreach ($rows as $r) {
        $byId[(int)$r['id_produk']] = $r;
    }

    foreach ($_SESSION['cart'] as $pid => $cItem) {
        $pid = (int)$pid;
        if (isset($byId[$pid])) {
            $prod = $byId[$pid];
            $qty = max(1, (int)($cItem['qty'] ?? 1));
            $harga = (float)$prod['harga'];
            $cartItems[] = [
                'id_produk' => $pid,
                'nama_produk' => $prod['nama_produk'],
                'kategori' => $prod['nama_kategori'] ?? 'Umum',
                'harga' => $harga,
                'stok' => (int)$prod['stok'],
                'gambar' => $prod['gambar'] ?? '',
                'qty' => $qty,
                'subtotal' => $harga * $qty,
                'selected' => !empty($cItem['selected'])
            ];
        }
    }
}

$waClean = preg_replace('/[^0-9]/', '', WA_NUMBER);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1">Keranjang Belanja</h1>
    <p class="text-muted small mb-0">Pilih barang yang ingin dipesan, lengkapi data diri &amp; alamat, lalu lanjutkan ke WhatsApp.</p>
  </div>
  <a href="produk.php" class="btn btn-sm btn-outline-usg rounded-pill px-3">+ Tambah Produk</a>
</div>

<?php if ($lastOrder): ?>
  <div class="card product-card shadow-sm border-success mb-4">
    <div class="card-body p-4">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
          <span class="badge rounded-pill bg-success mb-2 px-3 py-2">Pesanan Tercatat</span>
          <h2 class="h5 fw-bold mb-1">Kode Pesanan Anda: <span class="text-success"><?= e($lastOrder['kode']) ?></span></h2>
          <p class="small text-muted mb-0">
            Simpan kode ini untuk memantau status pesanan di menu <strong>Lacak Pesanan</strong>.
            (<?= (int)$lastOrder['total_qty'] ?> pcs &bull; <?= e($lastOrder['total_harga']) ?>)
          </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
          <a href="<?= e($lastOrder['wa_url']) ?>" target="_blank" rel="noopener noreferrer"
             class="btn btn-usg rounded-pill px-4 fw-semibold">
            Buka WhatsApp Sekarang
          </a>
          <a href="lacak.php?kode=<?= urlencode($lastOrder['kode']) ?>"
             class="btn btn-outline-usg rounded-pill px-4 fw-semibold">
            Lacak Pesanan Ini
          </a>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if (empty($cartItems)): ?>
  <div class="card product-card p-5 text-center shadow-sm">
    <h2 class="h5 fw-bold mb-2">Keranjang Anda masih kosong</h2>
    <p class="text-muted small mb-4">Yuk pilih merchandise favorit di katalog USGallery terlebih dahulu.</p>
    <div class="d-flex justify-content-center gap-2 flex-wrap">
      <a href="produk.php" class="btn btn-usg rounded-pill px-4">Jelajahi Katalog</a>
      <a href="lacak.php" class="btn btn-outline-usg rounded-pill px-4">Lacak Pesanan</a>
    </div>
  </div>
<?php else: ?>
  <form action="keranjang.php" method="post" id="checkoutForm">
    <input type="hidden" name="action" value="checkout_wa">

    <div class="row g-4 align-items-start">
      <div class="col-lg-8">
        <div class="card product-card shadow-sm">
          <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pb-3 mb-3 border-bottom">
              <div class="form-check m-0">
                <input class="form-check-input" type="checkbox" id="checkAllItems" checked>
                <label class="form-check-label fw-semibold" for="checkAllItems">
                  Pilih Semua Barang (<?= count($cartItems) ?> jenis)
                </label>
              </div>
              <button type="submit" formaction="keranjang.php" formmethod="post"
                      name="action" value="clear" formnovalidate
                      class="btn btn-sm btn-outline-danger rounded-pill px-3"
                      onclick="return confirm('Kosongkan semua isi keranjang?')">
                Kosongkan Keranjang
              </button>
            </div>

            <?php foreach ($cartItems as $index => $item): ?>
              <?php
                $imgPath = !empty($item['gambar']) ? 'uploads/' . ltrim($item['gambar'], '/') : '';
                $hasImg = $imgPath !== '' && file_exists(__DIR__ . '/' . $imgPath);
                $isLast = ($index === count($cartItems) - 1);
                $pid = (int)$item['id_produk'];
              ?>
              <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 py-3 <?= $isLast ? '' : 'border-bottom' ?> cart-row"
                   data-id="<?= $pid ?>"
                   data-nama="<?= e($item['nama_produk']) ?>"
                   data-harga="<?= (float)$item['harga'] ?>"
                   data-qty="<?= (int)$item['qty'] ?>">
                <div class="d-flex align-items-center gap-3">
                  <input class="form-check-input item-checkbox mt-0" type="checkbox"
                         name="selected_ids[]" value="<?= $pid ?>"
                         <?= $item['selected'] ? 'checked' : '' ?>
                         aria-label="Pilih <?= e($item['nama_produk']) ?>">

                  <div class="bg-light border rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                       style="width: 72px; height: 72px; overflow: hidden;">
                    <?php if ($hasImg): ?>
                      <img src="<?= e($imgPath) ?>" alt="<?= e($item['nama_produk']) ?>" class="w-100 h-100 object-fit-cover">
                    <?php else: ?>
                      <span class="text-muted" style="font-size: 0.72rem;">Foto</span>
                    <?php endif; ?>
                  </div>

                  <div>
                    <div class="fw-bold"><?= e($item['nama_produk']) ?></div>
                    <div class="small text-muted">Kategori: <?= e($item['kategori']) ?></div>
                    <div class="small fw-semibold text-success mt-1">
                      <?= $item['harga'] > 0 ? e(rp($item['harga'])) : 'Harga via Konsultasi WA' ?>
                    </div>
                  </div>
                </div>

                <div class="d-flex align-items-center justify-content-between justify-content-md-end gap-3">
                  <div class="d-flex align-items-center gap-1">
                    <button type="submit" formaction="keranjang.php" formmethod="post" formnovalidate
                            name="qty" value="<?= (int)$item['qty'] - 1 ?>"
                            onclick="this.form.querySelector('input[name=action]').value='update_qty'; this.form.insertAdjacentHTML('beforeend','<input type=hidden name=id_produk value=<?= $pid ?>>');"
                            class="btn btn-sm btn-outline-secondary rounded-circle" style="width: 30px; height: 30px; padding: 0;">-</button>
                    <span class="px-2 fw-bold"><?= (int)$item['qty'] ?></span>
                    <button type="submit" formaction="keranjang.php" formmethod="post" formnovalidate
                            name="qty" value="<?= (int)$item['qty'] + 1 ?>"
                            onclick="this.form.querySelector('input[name=action]').value='update_qty'; this.form.insertAdjacentHTML('beforeend','<input type=hidden name=id_produk value=<?= $pid ?>>');"
                            class="btn btn-sm btn-outline-secondary rounded-circle" style="width: 30px; height: 30px; padding: 0;">+</button>
                  </div>

                  <div class="text-end" style="min-width: 105px;">
                    <div class="small text-muted">Subtotal</div>
                    <div class="fw-bold">
                      <?= $item['harga'] > 0 ? e(rp($item['subtotal'])) : 'Menyesuaikan' ?>
                    </div>
                  </div>

                  <button type="submit" formaction="keranjang.php" formmethod="post" formnovalidate
                          class="btn btn-sm btn-outline-danger rounded-pill px-3" title="Hapus item"
                          onclick="if(!confirm('Hapus produk ini dari keranjang?')) return false; this.form.querySelector('input[name=action]').value='remove'; this.form.insertAdjacentHTML('beforeend','<input type=hidden name=id_produk value=<?= $pid ?>>');">
                    Hapus
                  </button>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card product-card shadow-sm">
          <div class="card-body p-4">
            <h2 class="h6 fw-bold mb-3">Data Pemesan &amp; Ringkasan</h2>

            <div class="mb-2">
              <label for="namaPemesan" class="form-label small fw-semibold mb-1">Nama Lengkap / Instansi <span class="text-danger">*</span></label>
              <input type="text" id="namaPemesan" name="nama_pelanggan" required
                     class="form-control form-control-sm" placeholder="Contoh: Ahmad / BEM UNUSA">
            </div>

            <div class="mb-2">
              <label for="noWaPemesan" class="form-label small fw-semibold mb-1">No. WhatsApp Pemesan <span class="text-danger">*</span></label>
              <input type="text" id="noWaPemesan" name="no_telepon" required
                     class="form-control form-control-sm" placeholder="Contoh: 081234567890">
            </div>

            <div class="mb-2">
              <label for="alamatPemesan" class="form-label small fw-semibold mb-1">Alamat <span class="text-danger">*</span></label>
              <textarea id="alamatPemesan" name="alamat" rows="2" required
                        class="form-control form-control-sm" placeholder="Contoh: Jl. Jemursari No. 51-57, Surabaya"></textarea>
            </div>

            <div class="mb-3">
              <label for="catatanPesanan" class="form-label small fw-semibold mb-1">Catatan / Custom Desain</label>
              <textarea id="catatanPesanan" name="catatan" rows="2"
                        class="form-control form-control-sm" placeholder="Contoh: Cetak logo UNUSA ukuran sedang..."></textarea>
            </div>

            <hr>

            <div class="d-flex justify-content-between small mb-2">
              <span class="text-muted">Total barang dipilih</span>
              <span class="fw-bold" id="summaryQty">0 pcs</span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
              <span class="fw-semibold">Total harga</span>
              <span class="fs-5 fw-extrabold text-success" id="summaryTotal">Rp 0</span>
            </div>

            <button type="submit" id="btnPesanWa" class="btn btn-usg w-100 py-2 rounded-pill fw-semibold">
              Pesan via WhatsApp
            </button>
            <div class="form-text text-center mt-2">
              Kode pesanan akan dibuat otomatis, disimpan ke sistem, dan disertakan ke pesan WhatsApp admin.
            </div>
          </div>
        </div>
      </div>
    </div>
  </form>

  <script>
    (function () {
      const form = document.getElementById('checkoutForm');
      const rows = document.querySelectorAll('.cart-row');
      const checkboxes = document.querySelectorAll('.item-checkbox');
      const checkAll = document.getElementById('checkAllItems');
      const summaryQtyEl = document.getElementById('summaryQty');
      const summaryTotalEl = document.getElementById('summaryTotal');
      const namaInput = document.getElementById('namaPemesan');
      const waInput = document.getElementById('noWaPemesan');
      const alamatInput = document.getElementById('alamatPemesan');
      const adminWa = <?= json_encode($waClean) ?>;

      function formatRp(num) {
        return 'Rp ' + Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
      }

      function updateSummary() {
        let totalQty = 0;
        let totalHarga = 0;
        let hasCustomPrice = false;

        rows.forEach((row) => {
          const cb = row.querySelector('.item-checkbox');
          if (!cb || !cb.checked) return;

          const harga = parseFloat(row.dataset.harga || '0');
          const qty = parseInt(row.dataset.qty || '1', 10);

          totalQty += qty;
          if (harga > 0) {
            totalHarga += harga * qty;
          } else {
            hasCustomPrice = true;
          }
        });

        summaryQtyEl.textContent = `${totalQty} pcs`;
        summaryTotalEl.textContent = hasCustomPrice && totalHarga === 0
          ? 'Via Konsultasi WA'
          : (hasCustomPrice ? `${formatRp(totalHarga)} + Custom` : formatRp(totalHarga));

        if (checkAll && checkboxes.length > 0) {
          checkAll.checked = Array.from(checkboxes).every(c => c.checked);
        }
      }

      checkboxes.forEach(c => c.addEventListener('change', updateSummary));
      if (checkAll) {
        checkAll.addEventListener('change', function () {
          checkboxes.forEach(c => { c.checked = checkAll.checked; });
          updateSummary();
        });
      }

      if (form) {
        form.addEventListener('submit', function (e) {
          const submitter = e.submitter;
          const act = submitter ? submitter.getAttribute('name') : '';
          if (act === 'qty' || (submitter && submitter.value === 'clear')) {
            return;
          }

          const checkedCount = Array.from(checkboxes).filter(c => c.checked).length;
          if (checkedCount === 0) {
            e.preventDefault();
            alert('Pilih minimal 1 produk di keranjang terlebih dahulu.');
            return;
          }

          if (!namaInput.value.trim() || !waInput.value.trim() || !alamatInput.value.trim()) {
            e.preventDefault();
            alert('Mohon lengkapi Nama, No. WhatsApp, dan Alamat terlebih dahulu sebelum memesan.');
            return;
          }

          if (!adminWa || adminWa.length < 8) {
            e.preventDefault();
            alert('Nomor WhatsApp admin (WA_NUMBER) belum diatur di config.php.');
          }
        });
      }

      updateSummary();
    })();
  </script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>