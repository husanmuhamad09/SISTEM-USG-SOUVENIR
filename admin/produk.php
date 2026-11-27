<?php
$adminTitle = 'Data Produk — USGallery Admin';
require_once __DIR__ . '/_layout.php';

$uploadDir = __DIR__ . '/../uploads';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0775, true);
}

$hasHargaProduksiCol = false;
try {
    $colStmt = $pdo->query("SHOW COLUMNS FROM produk LIKE 'harga_produksi'");
    $hasHargaProduksiCol = (bool)$colStmt->fetch();
} catch (Throwable $e) {
    $hasHargaProduksiCol = false;
}

function handleProductUpload(string $fieldName, string $uploadDir, ?string $oldFile = null): array {
    if (empty($_FILES[$fieldName]) || ($_FILES[$fieldName]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'filename' => $oldFile, 'error' => ''];
    }

    $err = (int)$_FILES[$fieldName]['error'];
    if ($err !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'filename' => $oldFile, 'error' => 'Gagal mengunggah gambar (kode error: ' . $err . ').'];
    }

    $tmpName = (string)$_FILES[$fieldName]['tmp_name'];
    $size = (int)($_FILES[$fieldName]['size'] ?? 0);
  if ($size > 10 * 1024 * 1024) {
    return ['ok' => false, 'filename' => $oldFile, 'error' => 'Ukuran gambar maksimal 10 MB.'];
}

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? (string)finfo_file($finfo, $tmpName) : '';
    if ($finfo) {
        finfo_close($finfo);
    }

    $allowedMime = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif'
    ];

    if (!isset($allowedMime[$mime])) {
        return ['ok' => false, 'filename' => $oldFile, 'error' => 'Format gambar harus JPG, PNG, WEBP, atau GIF.'];
    }

    $ext = $allowedMime[$mime];
    $newName = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destPath = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $newName;

    if (!move_uploaded_file($tmpName, $destPath)) {
        return ['ok' => false, 'filename' => $oldFile, 'error' => 'Tidak dapat memindahkan file gambar ke folder uploads.'];
    }

    if ($oldFile && $oldFile !== $newName) {
        $oldPath = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . basename($oldFile);
        if (is_file($oldPath)) {
            @unlink($oldPath);
        }
    }

    return ['ok' => true, 'filename' => $newName, 'error' => ''];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';

    if ($act === 'add_kategori') {
        $namaKat = trim($_POST['nama_kategori'] ?? '');
        if ($namaKat === '') {
            flash('Nama kategori tidak boleh kosong.');
        } else {
            $cek = $pdo->prepare("SELECT COUNT(*) FROM kategori WHERE LOWER(nama_kategori) = LOWER(:nama)");
            $cek->execute([':nama' => $namaKat]);
            if ((int)$cek->fetchColumn() > 0) {
                flash('Kategori "' . $namaKat . '" sudah ada.');
            } else {
                $ins = $pdo->prepare("INSERT INTO kategori (nama_kategori) VALUES (:nama)");
                $ins->execute([':nama' => $namaKat]);
                flash('Kategori "' . $namaKat . '" berhasil ditambahkan.');
            }
        }
        header('Location: produk.php');
        exit;
    }

    if ($act === 'add_produk') {
        $namaProduk = trim($_POST['nama_produk'] ?? '');
        $idKategori = isset($_POST['id_kategori']) && $_POST['id_kategori'] !== '' ? (int)$_POST['id_kategori'] : null;
        $hargaProduksi = max(0, (float)($_POST['harga_produksi'] ?? 0));
        $harga = max(0, (float)($_POST['harga'] ?? 0));
        $stok = max(0, (int)($_POST['stok'] ?? 0));
        $deskripsi = trim($_POST['deskripsi'] ?? '');

        if ($namaProduk === '') {
            flash('Nama produk wajib diisi.');
            header('Location: produk.php');
            exit;
        }

        $up = handleProductUpload('gambar', $uploadDir, null);
        if (!$up['ok']) {
            flash($up['error']);
            header('Location: produk.php');
            exit;
        }

        if ($hasHargaProduksiCol) {
            $stmt = $pdo->prepare("
                INSERT INTO produk (id_kategori, nama_produk, deskripsi, harga, harga_produksi, stok, gambar)
                VALUES (:id_kat, :nama, :desk, :harga, :harga_prod, :stok, :gambar)
            ");
            $stmt->execute([
                ':id_kat'     => $idKategori,
                ':nama'       => $namaProduk,
                ':desk'       => $deskripsi,
                ':harga'      => $harga,
                ':harga_prod' => $hargaProduksi,
                ':stok'       => $stok,
                ':gambar'     => $up['filename']
            ]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO produk (id_kategori, nama_produk, deskripsi, harga, stok, gambar)
                VALUES (:id_kat, :nama, :desk, :harga, :stok, :gambar)
            ");
            $stmt->execute([
                ':id_kat' => $idKategori,
                ':nama'   => $namaProduk,
                ':desk'   => $deskripsi,
                ':harga'  => $harga,
                ':stok'   => $stok,
                ':gambar' => $up['filename']
            ]);
        }

        flash('Produk "' . $namaProduk . '" berhasil ditambahkan.');
        header('Location: produk.php');
        exit;
    }

    if ($act === 'edit_produk') {
        $idProduk = isset($_POST['id_produk']) ? (int)$_POST['id_produk'] : 0;
        $namaProduk = trim($_POST['nama_produk'] ?? '');
        $idKategori = isset($_POST['id_kategori']) && $_POST['id_kategori'] !== '' ? (int)$_POST['id_kategori'] : null;
        $hargaProduksi = max(0, (float)($_POST['harga_produksi'] ?? 0));
        $harga = max(0, (float)($_POST['harga'] ?? 0));
        $stok = max(0, (int)($_POST['stok'] ?? 0));
        $deskripsi = trim($_POST['deskripsi'] ?? '');

        $stmtOld = $pdo->prepare("SELECT gambar FROM produk WHERE id_produk = :id LIMIT 1");
        $stmtOld->execute([':id' => $idProduk]);
        $oldProd = $stmtOld->fetch();

        if (!$oldProd || $namaProduk === '') {
            flash('Data produk tidak ditemukan atau nama produk kosong.');
            header('Location: produk.php');
            exit;
        }

        $up = handleProductUpload('gambar', $uploadDir, $oldProd['gambar'] ?? null);
        if (!$up['ok']) {
            flash($up['error']);
            header('Location: produk.php?edit=' . $idProduk);
            exit;
        }

        if ($hasHargaProduksiCol) {
            $stmt = $pdo->prepare("
                UPDATE produk
                SET id_kategori = :id_kat,
                    nama_produk = :nama,
                    deskripsi = :desk,
                    harga = :harga,
                    harga_produksi = :harga_prod,
                    stok = :stok,
                    gambar = :gambar
                WHERE id_produk = :id
            ");
            $stmt->execute([
                ':id_kat'     => $idKategori,
                ':nama'       => $namaProduk,
                ':desk'       => $deskripsi,
                ':harga'      => $harga,
                ':harga_prod' => $hargaProduksi,
                ':stok'       => $stok,
                ':gambar'     => $up['filename'],
                ':id'         => $idProduk
            ]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE produk
                SET id_kategori = :id_kat,
                    nama_produk = :nama,
                    deskripsi = :desk,
                    harga = :harga,
                    stok = :stok,
                    gambar = :gambar
                WHERE id_produk = :id
            ");
            $stmt->execute([
                ':id_kat' => $idKategori,
                ':nama'   => $namaProduk,
                ':desk'   => $deskripsi,
                ':harga'  => $harga,
                ':stok'   => $stok,
                ':gambar' => $up['filename'],
                ':id'     => $idProduk
            ]);
        }

        flash('Produk "' . $namaProduk . '" berhasil diperbarui.');
        header('Location: produk.php');
        exit;
    }

    if ($act === 'delete_produk') {
        $idProduk = isset($_POST['id_produk']) ? (int)$_POST['id_produk'] : 0;

        $stmtOld = $pdo->prepare("SELECT nama_produk, gambar FROM produk WHERE id_produk = :id LIMIT 1");
        $stmtOld->execute([':id' => $idProduk]);
        $oldProd = $stmtOld->fetch();

        if ($oldProd) {
            try {
                $del = $pdo->prepare("DELETE FROM produk WHERE id_produk = :id");
                $del->execute([':id' => $idProduk]);

                if (!empty($oldProd['gambar'])) {
                    $filePath = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . basename((string)$oldProd['gambar']);
                    if (is_file($filePath)) {
                        @unlink($filePath);
                    }
                }
                flash('Produk "' . $oldProd['nama_produk'] . '" berhasil dihapus.');
            } catch (Throwable $e) {
                flash('Produk tidak dapat dihapus karena sudah tercatat pada riwayat transaksi.');
            }
        }

        header('Location: produk.php');
        exit;
    }
}

$kategoriList = $pdo->query("SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori ASC")->fetchAll();

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editProduk = null;
if ($editId > 0) {
    $stEdit = $pdo->prepare("SELECT * FROM produk WHERE id_produk = :id LIMIT 1");
    $stEdit->execute([':id' => $editId]);
    $editProduk = $stEdit->fetch();
}

$qSearch = trim($_GET['q'] ?? '');
$filterKat = isset($_GET['kategori']) && $_GET['kategori'] !== '' ? (int)$_GET['kategori'] : 0;

$sql = "
    SELECT p.*, k.nama_kategori
    FROM produk p
    LEFT JOIN kategori k ON p.id_kategori = k.id_kategori
    WHERE 1=1
";
$params = [];

if ($filterKat > 0) {
    $sql .= " AND p.id_kategori = :id_kat";
    $params[':id_kat'] = $filterKat;
}
if ($qSearch !== '') {
    $sql .= " AND (p.nama_produk LIKE :q OR p.deskripsi LIKE :q OR k.nama_kategori LIKE :q)";
    $params[':q'] = '%' . $qSearch . '%';
}
$sql .= " ORDER BY p.id_produk DESC";

$stmtList = $pdo->prepare($sql);
$stmtList->execute($params);
$produkList = $stmtList->fetchAll();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1">Data Produk</h1>
    <p class="text-muted small mb-0">Kelola katalog souvenir, harga produksi, harga jual, stok, dan kategori.</p>
  </div>
  <a href="#formProduk" class="btn btn-sm btn-success rounded-pill px-3 fw-semibold">
    <?= $editProduk ? 'Edit Produk #' . (int)$editProduk['id_produk'] : '+ Tambah Produk Baru' ?>
  </a>
</div>

<?php if (!$hasHargaProduksiCol): ?>
  <div class="alert alert-warning small rounded-3">
    Kolom <code>harga_produksi</code> belum ada di tabel <code>produk</code>. Jalankan query <code>ALTER TABLE produk ADD COLUMN harga_produksi DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER harga;</code> di phpMyAdmin agar harga produksi tersimpan di database.
  </div>
<?php endif; ?>

<div class="row g-4 align-items-start mb-4">
  <!-- Form Tambah / Edit Produk -->
  <div class="col-lg-5" id="formProduk">
    <div class="bg-white border rounded-3 p-4 shadow-sm mb-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h6 fw-bold mb-0">
          <?= $editProduk ? 'Edit Produk #' . (int)$editProduk['id_produk'] : 'Tambah Produk Baru' ?>
        </h2>
        <?php if ($editProduk): ?>
          <a href="produk.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Batal</a>
        <?php endif; ?>
      </div>

      <form method="post" action="produk.php" enctype="multipart/form-data">
        <input type="hidden" name="act" value="<?= $editProduk ? 'edit_produk' : 'add_produk' ?>">
        <?php if ($editProduk): ?>
          <input type="hidden" name="id_produk" value="<?= (int)$editProduk['id_produk'] ?>">
        <?php endif; ?>

        <div class="mb-3">
          <label for="nama_produk" class="form-label small fw-semibold">Nama Produk <span class="text-danger">*</span></label>
          <input type="text" id="nama_produk" name="nama_produk" required
                 value="<?= e($editProduk['nama_produk'] ?? '') ?>"
                 class="form-control form-control-sm" placeholder="Contoh: Black Tumbler">
        </div>

        <div class="mb-3">
          <label for="id_kategori" class="form-label small fw-semibold">Kategori</label>
          <select id="id_kategori" name="id_kategori" class="form-select form-select-sm">
            <option value="">-- Pilih Kategori --</option>
            <?php foreach ($kategoriList as $kat): ?>
              <?php $sel = ((int)($editProduk['id_kategori'] ?? 0) === (int)$kat['id_kategori']) ? 'selected' : ''; ?>
              <option value="<?= (int)$kat['id_kategori'] ?>" <?= $sel ?>><?= e($kat['nama_kategori']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="row g-2 mb-3">
          <div class="col-sm-4">
            <label for="harga_produksi" class="form-label small fw-semibold">Harga Produksi</label>
            <input type="number" id="harga_produksi" name="harga_produksi" min="0" step="500"
                   value="<?= e((string)($editProduk['harga_produksi'] ?? '0')) ?>"
                   class="form-control form-control-sm" placeholder="50000">
          </div>
          <div class="col-sm-4">
            <label for="harga" class="form-label small fw-semibold">Harga Jual (Rp)</label>
            <input type="number" id="harga" name="harga" min="0" step="500"
                   value="<?= e((string)($editProduk['harga'] ?? '0')) ?>"
                   class="form-control form-control-sm" placeholder="55000">
          </div>
          <div class="col-sm-4">
            <label for="stok" class="form-label small fw-semibold">Jumlah Stok</label>
            <input type="number" id="stok" name="stok" min="0"
                   value="<?= e((string)($editProduk['stok'] ?? '10')) ?>"
                   class="form-control form-control-sm">
          </div>
        </div>

        <div class="mb-3">
          <label for="gambar" class="form-label small fw-semibold">Gambar Produk</label>
          <input type="file" id="gambar" name="gambar" accept="image/*" class="form-control form-control-sm">
          <div class="form-text">Format JPG, PNG, WEBP, atau GIF (maks. 3 MB).</div>
          <?php if ($editProduk && !empty($editProduk['gambar'])): ?>
            <div class="d-flex align-items-center gap-2 mt-2">
              <img src="../uploads/<?= e(basename((string)$editProduk['gambar'])) ?>" alt="Preview"
                   class="rounded border" style="width: 48px; height: 48px; object-fit: cover;">
              <span class="small text-muted">Gambar saat ini: <?= e(basename((string)$editProduk['gambar'])) ?></span>
            </div>
          <?php endif; ?>
        </div>

        <div class="mb-3">
          <label for="deskripsi" class="form-label small fw-semibold">Deskripsi</label>
          <textarea id="deskripsi" name="deskripsi" rows="3" class="form-control form-control-sm"
                    placeholder="Spesifikasi bahan, ukuran, atau info custom souvenir..."><?= e($editProduk['deskripsi'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn btn-success w-100 py-2 rounded-pill fw-semibold">
          <?= $editProduk ? 'Simpan Perubahan' : 'Simpan Produk Baru' ?>
        </button>
      </form>
    </div>

    <!-- Tambah Kategori Cepat -->
    <div class="bg-white border rounded-3 p-4 shadow-sm">
      <h2 class="h6 fw-bold mb-2">Tambah Kategori</h2>
      <form method="post" action="produk.php" class="d-flex gap-2">
        <input type="hidden" name="act" value="add_kategori">
        <input type="text" name="nama_kategori" class="form-control form-control-sm"
               placeholder="Nama kategori baru..." required>
        <button type="submit" class="btn btn-sm btn-outline-success rounded-pill px-3 fw-semibold">Tambah</button>
      </form>
    </div>
  </div>

  <!-- Riwayat / Tabel Data Barang -->
  <div class="col-lg-7">
    <div class="bg-white border rounded-3 p-4 shadow-sm">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="h6 fw-bold mb-0">Riwayat Data Barang (<?= count($produkList) ?>)</h2>
        <form method="get" action="produk.php" class="d-flex flex-wrap gap-2">
          <select name="kategori" class="form-select form-select-sm" style="width: auto;">
            <option value="">Semua Kategori</option>
            <?php foreach ($kategoriList as $kat): ?>
              <option value="<?= (int)$kat['id_kategori'] ?>" <?= $filterKat === (int)$kat['id_kategori'] ? 'selected' : '' ?>>
                <?= e($kat['nama_kategori']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <input type="text" name="q" value="<?= e($qSearch) ?>" class="form-control form-control-sm"
                 style="width: 170px;" placeholder="Cari produk...">
          <button type="submit" class="btn btn-sm btn-success rounded-pill px-3">Filter</button>
          <?php if ($qSearch !== '' || $filterKat > 0): ?>
            <a href="produk.php" class="btn btn-sm btn-outline-secondary rounded-pill px-2">Reset</a>
          <?php endif; ?>
        </form>
      </div>

      <div class="table-responsive">
        <table class="tbl">
          <thead>
            <tr>
              <th>ID</th>
              <th>Gambar</th>
              <th>Nama Produk</th>
              <th>Produksi / Jual</th>
              <th>Jumlah</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($produkList)): ?>
              <tr>
                <td colspan="6" class="text-center text-muted py-4">Belum ada data produk.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($produkList as $p): ?>
                <?php
                  $imgFile = !empty($p['gambar']) ? basename((string)$p['gambar']) : '';
                  $hasImg = $imgFile !== '' && is_file($uploadDir . DIRECTORY_SEPARATOR . $imgFile);
                  $stok = (int)$p['stok'];
                  $hJual = (float)($p['harga'] ?? 0);
                  $hProd = (float)($p['harga_produksi'] ?? 0);
                  $marginUnit = $hJual - $hProd;
                ?>
                <tr>
                  <td><?= (int)$p['id_produk'] ?></td>
                  <td>
                    <?php if ($hasImg): ?>
                      <img src="../uploads/<?= e($imgFile) ?>" alt="<?= e($p['nama_produk']) ?>">
                    <?php else: ?>
                      <span class="badge bg-light text-muted border">No Img</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="fw-bold"><?= e($p['nama_produk']) ?></div>
                    <div class="small text-muted"><?= e($p['nama_kategori'] ?? 'Tanpa Kategori') ?></div>
                  </td>
                  <td>
                    <div class="small text-muted">Prod: <?= $hProd > 0 ? e(rp($hProd)) : 'Rp 0' ?></div>
                    <div class="fw-semibold text-success">
                      Jual: <?= $hJual > 0 ? e(rp($hJual)) : 'Konsultasi' ?>
                    </div>
                    <?php if ($hJual > 0 && $hProd > 0): ?>
                      <div class="small <?= $marginUnit >= 0 ? 'text-primary' : 'text-danger' ?>">
                        Margin: <?= e(rp($marginUnit)) ?>/pcs
                      </div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="pill <?= $stok <= 5 ? 'text-danger bg-danger bg-opacity-10' : '' ?>">
                      <?= $stok ?>
                    </span>
                  </td>
                  <td>
                    <div class="d-flex gap-1">
                      <a href="produk.php?edit=<?= (int)$p['id_produk'] ?>#formProduk"
                         class="btn btn-sm btn-outline-primary py-0 px-2">Edit</a>
                      <form method="post" action="produk.php" class="m-0"
                            onsubmit="return confirm('Hapus produk &quot;<?= e($p['nama_produk']) ?>&quot;?');">
                        <input type="hidden" name="act" value="delete_produk">
                        <input type="hidden" name="id_produk" value="<?= (int)$p['id_produk'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2">Hapus</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/_footer.php'; ?>