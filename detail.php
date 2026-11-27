<?php require 'config.php'; require 'includes/layout.php';
$st=$pdo->prepare("SELECT p.*,k.nama_kategori FROM produk p JOIN kategori k USING(id_kategori) WHERE id_produk=?");$st->execute([(int)($_GET['id']??0)]);$p=$st->fetch();
if(!$p){flash('Produk tidak ditemukan.');header('Location: produk.php');exit;}
head($p['nama_produk']);?>
<a class="back" href="produk.php">← Kembali</a>
<div class="detail"><div class="thumb big"><?php if($p['gambar']):?><img src="uploads/<?=e($p['gambar'])?>" alt=""><?php else:?><span>🎁</span><?php endif;?></div>
<div><small><?=e($p['nama_kategori'])?></small><h1><?=e($p['nama_produk'])?></h1><p class="price big"><?=$p['harga']>0?rp($p['harga']):'Sesuai permintaan'?></p>
<p><?=nl2br(e($p['deskripsi']))?></p><p class="muted">Stok: <?=$p['stok']>0?(int)$p['stok']:'habis'?></p>
<?php if($p['stok']>0):?><form method="post" action="keranjang.php"><input type="hidden" name="aksi" value="tambah"><input type="hidden" name="id" value="<?=$p['id_produk']?>">
<label>Jumlah <input type="number" name="qty" value="1" min="1" max="<?=$p['stok']?>"></label>
<div class="row"><button class="btn ghost">Tambah ke keranjang</button><button class="btn wa" name="langsung" value="1">Pesan via WhatsApp</button></div></form><?php endif;?></div></div>
<?php foot();
