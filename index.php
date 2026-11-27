<?php require 'config.php'; require 'includes/layout.php';
$kat=$pdo->query("SELECT * FROM kategori")->fetchAll();
$best=$pdo->query("SELECT p.*,k.nama_kategori FROM produk p JOIN kategori k USING(id_kategori) LEFT JOIN detail_transaksi d ON d.id_produk=p.id_produk GROUP BY p.id_produk ORDER BY COALESCE(SUM(d.jumlah),0) DESC,p.id_produk DESC LIMIT 4")->fetchAll();
head('Beranda');?>
<section class="hero"><div><h1>Souvenir kampus yang berkualitas.</h1><p>Lihat produk UNUSA Souvenir Gallery, pilih yang kamu suka, lalu pesan langsung lewat WhatsApp.</p>
<a class="btn" href="produk.php">Lihat produk</a> <a class="btn ghost" href="https://wa.me/<?=WA_NUMBER?>">Chat admin</a></div></section>
<section><h2>Kategori</h2><div class="chips"><?php foreach($kat as $k):?><a href="produk.php?kat=<?=$k['id_kategori']?>"><?=e($k['nama_kategori'])?></a><?php endforeach;?></div></section>
<section><h2>Produk terlaris</h2><div class="grid"><?php foreach($best as $p) card($p);?></div></section>
<section><h2>Cara memesan</h2><ol class="steps"><li>Pilih produk dan masukkan ke keranjang.</li><li>Isi nama dan nomor HP.</li><li>Tekan Pesan via WhatsApp. Pesan terisi otomatis, tinggal kirim.</li></ol></section>
<section><h2>Pertanyaan umum</h2>
<details><summary>Apakah bisa custom desain dan logo?</summary><p>Bisa. Pilih kategori Custom lalu kirim desainmu lewat WhatsApp.</p></details>
<details><summary>Metode pembayaran apa yang tersedia?</summary><p>Pembayaran dikonfirmasi lewat WhatsApp setelah pesananmu kami terima.</p></details>
<details><summary>Berapa minimal pemesanan?</summary><p>Tanyakan ke admin lewat WhatsApp, tergantung produk.</p></details></section>
<?php foot();
