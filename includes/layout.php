<?php
function head($t='USGallery'){$n=array_sum(array_column(cart(),'qty'));$f=flash();?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($t)?> · USGallery</title><link rel="stylesheet" href="assets/style.css"></head><body>
<header class="nav"><div class="wrap nav-in"><a class="brand" href="index.php">US<span>Gallery</span></a>
<form class="search" action="produk.php"><input name="q" placeholder="Cari produk..." value="<?=e($_GET['q']??'')?>"><button>Cari</button></form>
<nav><a href="index.php">Home</a><a href="produk.php">Produk</a><a class="cart" href="keranjang.php">Keranjang<?php if($n):?><b><?=$n?></b><?php endif;?></a></nav></div></header>
<main class="wrap"><?php if($f):?><div class="alert"><?=e($f)?></div><?php endif;}
function foot(){?></main><footer><div class="wrap">© <?=date('Y')?> UNUSA Souvenir Gallery · <a href="https://wa.me/<?=WA_NUMBER?>">Tanya via WhatsApp</a></div></footer></body></html><?php }
function card($p){?>
<a class="card" href="detail.php?id=<?=$p['id_produk']?>"><div class="thumb"><?php if($p['gambar']):?><img loading="lazy" src="uploads/<?=e($p['gambar'])?>" alt="<?=e($p['nama_produk'])?>"><?php else:?><span>🎁</span><?php endif;?></div><div class="info"><small><?=e($p['nama_kategori']??'')?></small><h3><?=e($p['nama_produk'])?></h3><p class="price"><?=$p['harga']>0?rp($p['harga']):'Sesuai permintaan'?></p></div></a>
<?php }
