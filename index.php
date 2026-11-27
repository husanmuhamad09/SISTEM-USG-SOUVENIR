<?php
$pageTitle = 'USGallery — UNUSA Souvenir Gallery';
require_once __DIR__ . '/includes/layout.php';

$bestSeller = $pdo->query("
    SELECT p.*, k.nama_kategori
    FROM produk p
    LEFT JOIN kategori k ON p.id_kategori = k.id_kategori
    ORDER BY p.id_produk ASC
    LIMIT 3
")->fetchAll();

$valueList = [
    [
        'judul' => 'Custom Desain & Logo',
        'deskripsi' => 'Cocok untuk seminar, wisuda, kepanitiaan, hingga souvenir resmi kegiatan kampus.'
    ],
    [
        'judul' => 'Pesan Otomatis via WA',
        'deskripsi' => 'Detail produk di keranjang langsung tersusun rapi ke WhatsApp tanpa ketik ulang.'
    ],
    [
        'judul' => 'Pantau Status Pesanan',
        'deskripsi' => 'Cek perkembangan pesanan Anda kapan saja lewat menu Lacak Pesanan.'
    ]
];

$testimoniList = [
    [
        'nama' => 'Aulia Rahma',
        'inisial' => 'AR',
        'status' => 'Mahasiswa UNUSA',
        'pesan' => 'Souvenir dari USGallery sangat berkualitas! Desainnya bagus, pemesanannya praktis, dan sangat pas untuk kebutuhan kegiatan kampus.'
    ],
    [
        'nama' => 'Rizky Pratama',
        'inisial' => 'RP',
        'status' => 'Panitia Kegiatan Mahasiswa UNUSA',
        'pesan' => 'Pesan tumbler dan totebag untuk acara kampus jadi jauh lebih mudah. Info produk jelas dan koordinasi via WhatsApp cepat.'
    ],
    [
        'nama' => 'Nadia Putri',
        'inisial' => 'NP',
        'status' => 'Alumni UNUSA',
        'pesan' => 'Pilihan merchandise-nya menarik dan khas kampus. Cocok buat kado wisuda maupun perlengkapan sehari-hari.'
    ]
];

$faqList = [
    [
        'q' => '1. Berapa minimal pemesanan (MOQ)?',
        'a' => 'Untuk produk katalog reguler bisa dipesan mulai satuan, sedangkan untuk pesanan custom atau jumlah besar dapat disesuaikan dengan kebutuhan acara.'
    ],
    [
        'q' => '2. Apakah bisa custom desain dan logo kampus?',
        'a' => 'Bisa. Anda dapat memilih kategori Custom atau mencantumkan catatan desain/logo saat melakukan pemesanan melalui WhatsApp.'
    ],
    [
        'q' => '3. Bagaimana alur pemesanan produk?',
        'a' => 'Pilih produk atau masukkan ke keranjang, periksa detail pesanan, lalu klik tombol pemesanan WhatsApp agar pesan otomatis tersusun untuk dikonfirmasi pihak USGallery.'
    ],
    [
        'q' => '4. Metode pembayaran apa saja yang tersedia?',
        'a' => 'Pembayaran dan konfirmasi detail transaksi dikoordinasikan langsung bersama pihak USGallery setelah pesanan dikirim via WhatsApp.'
    ],
    [
        'q' => '5. Bagaimana cara pengiriman atau pengambilan pesanan?',
        'a' => 'Pesanan dapat diambil langsung maupun dikirim sesuai kesepakatan saat konfirmasi, dan statusnya dapat dipantau melalui menu Lacak Pesanan.'
    ]
];
?>

<section class="p-4 p-md-5 mb-4 text-white rounded-4 shadow-sm" style="background: linear-gradient(120deg, rgba(12, 36, 25, 0.82), rgba(20, 108, 67, 0.72)), url('assets/bg-unusa.jpg') center/cover no-repeat;">
  <div class="row align-items-center g-4 py-2">
    <div class="col-lg-7">
      <span class="badge rounded-pill bg-success bg-opacity-75 border border-light border-opacity-25 px-3 py-2 mb-3">
        UNUSA Souvenir Gallery
      </span>
      <h1 class="fw-bold mb-3" style="line-height: 1.2;">Butuh Souvenir Kampus Berkualitas</h1>
      <p class="mb-4" style="color: rgba(255,255,255,0.92);">
        <strong>USGallery</strong> menyediakan berbagai pilihan merchandise dan souvenir kampus untuk seminar, wisuda, kepanitiaan, hingga kebutuhan personal dengan pemesanan praktis via WhatsApp.
      </p>
      <div class="d-flex flex-wrap gap-2">
        <a href="produk.php" class="btn btn-success px-4 py-2 fw-semibold rounded-pill">Lihat Katalog Produk</a>
        <a href="lacak.php" class="btn btn-outline-light px-4 py-2 rounded-pill">Lacak Pesanan</a>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="rounded-4 p-4 text-center" style="background: rgba(255, 255, 255, 0.14); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.28);">
        <div class="fw-semibold mb-2 text-uppercase small" style="letter-spacing: 1.2px; color: #d8f3e4;">Official Merchandise</div>
        <div class="display-6 fw-extrabold mb-2">USGallery</div>
        <p class="small mb-0" style="color: rgba(255,255,255,0.9);">
          Katalog Resmi &bull; Custom Souvenir &bull; Pesan Otomatis via WA
        </p>
      </div>
    </div>
  </div>
</section>

<section class="mb-5">
  <div class="row g-3">
    <?php foreach ($valueList as $val): ?>
      <div class="col-md-4">
        <div class="bg-white border rounded-3 p-3 h-100">
          <div class="fw-bold text-success mb-1"><?= e($val['judul']) ?></div>
          <p class="small text-muted mb-0"><?= e($val['deskripsi']) ?></p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="mb-5">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-2 mb-3">
    <div>
      <h2 class="h4 fw-bold mb-1">Produk Best Seller</h2>
      <p class="text-muted small mb-0">Pilihan favorit untuk setiap momen spesial kampus.</p>
    </div>
    <div class="d-flex flex-wrap gap-1">
      <a href="produk.php" class="btn btn-sm btn-outline-usg rounded-pill px-3">All</a>
      <?php foreach ($kategoriList as $kat): ?>
        <a href="produk.php?kategori=<?= (int)$kat['id_kategori'] ?>" class="btn btn-sm btn-outline-usg rounded-pill px-3">
          <?= e($kat['nama_kategori']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="row g-4">
    <?php foreach ($bestSeller as $idx => $p): ?>
      <?php
        $imgPath = !empty($p['gambar']) ? 'uploads/' . ltrim($p['gambar'], '/') : '';
        $hasImg = $imgPath !== '' && file_exists(__DIR__ . '/' . $imgPath);
      ?>
      <div class="col-md-4">
        <div class="card product-card h-100 shadow-sm">
          <div class="position-relative bg-light d-flex align-items-center justify-content-center border-bottom" style="height: 205px; overflow: hidden; border-radius: 14px 14px 0 0;">
            <span class="position-absolute top-0 start-0 m-2 badge rounded-pill bg-success">
              <?= $idx === 0 ? 'Best Seller' : e($p['nama_kategori'] ?? 'Souvenir') ?>
            </span>
            <?php if ($hasImg): ?>
              <img src="<?= e($imgPath) ?>" alt="<?= e($p['nama_produk']) ?>" class="w-100 h-100 object-fit-cover">
            <?php else: ?>
              <div class="text-center px-3">
                <div class="fw-bold text-secondary small"><?= e($p['nama_kategori'] ?? 'USGallery') ?></div>
                <div class="text-muted small">Gambar belum diunggah</div>
              </div>
            <?php endif; ?>
          </div>
          <div class="card-body d-flex flex-column">
            <h3 class="h6 fw-bold mb-1"><?= e($p['nama_produk']) ?></h3>
            <p class="text-muted small flex-grow-1"><?= e($p['deskripsi'] ?? '') ?></p>
            <div class="fw-bold text-success fs-6 mb-3">
              <?= (float)$p['harga'] > 0 ? e(rp($p['harga'])) : 'Harga via Konsultasi WA' ?>
            </div>
            <div class="d-flex gap-2">
              <a href="detail.php?id=<?= (int)$p['id_produk'] ?>" class="btn btn-sm btn-outline-usg w-50 rounded-pill">Detail</a>
              <form action="keranjang.php" method="post" class="w-50 m-0">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="id_produk" value="<?= (int)$p['id_produk'] ?>">
                <input type="hidden" name="qty" value="1">
                <button type="submit" class="btn btn-sm btn-usg w-100 rounded-pill">+ Keranjang</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="mb-5">
  <div class="mb-3">
    <h2 class="h4 fw-bold mb-1">Testimoni</h2>
    <p class="text-muted small mb-0">Kepuasan civitas akademika dan pelanggan adalah prioritas kami.</p>
  </div>

  <div class="row g-4">
    <?php foreach ($testimoniList as $t): ?>
      <div class="col-md-4">
        <div class="card product-card h-100 shadow-sm">
          <div class="card-body">
            <div class="d-flex align-items-center gap-3 mb-2">
              <div class="avatar-circle"><?= e($t['inisial']) ?></div>
              <div>
                <div class="fw-bold"><?= e($t['nama']) ?></div>
                <div class="text-muted small"><?= e($t['status']) ?></div>
              </div>
            </div>
            <div class="text-warning small mb-2">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
            <p class="small text-secondary mb-0">&ldquo;<?= e($t['pesan']) ?>&rdquo;</p>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="mb-4">
  <div class="mb-3">
    <h2 class="h4 fw-bold mb-1">FAQ</h2>
    <p class="text-muted small mb-0">Pertanyaan yang sering diajukan seputar pemesanan di USGallery.</p>
  </div>

  <div class="accordion shadow-sm rounded-3 overflow-hidden" id="faqAccordion">
    <?php foreach ($faqList as $idx => $faq): ?>
      <div class="accordion-item border-0 border-bottom">
        <h3 class="accordion-header" id="faqHeading<?= $idx ?>">
          <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse<?= $idx ?>" aria-expanded="false" aria-controls="faqCollapse<?= $idx ?>">
            <?= e($faq['q']) ?>
          </button>
        </h3>
        <div id="faqCollapse<?= $idx ?>" class="accordion-collapse collapse" aria-labelledby="faqHeading<?= $idx ?>" data-bs-parent="#faqAccordion">
          <div class="accordion-body small text-muted bg-white">
            <?= e($faq['a']) ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>