<?php require 'config.php'; require 'includes/layout.php';
$q=trim($_GET['q']??'');$kid=(int)($_GET['kat']??0);
$kat=$pdo->query("SELECT * FROM kategori")->fetchAll();
$sql="SELECT p.*,k.nama_kategori FROM produk p JOIN kategori k USING(id_kategori) WHERE p.nama_produk LIKE ?";$a=["%$q%"];
if($kid){$sql.=" AND p.id_kategori=?";$a[]=$kid;}
$st=$pdo->prepare($sql." ORDER BY p.id_produk DESC");$st->execute($a);$ps=$st->fetchAll();
head('Produk');?>
<h1>Produk</h1>
<div class="chips"><a href="produk.php" class="<?=!$kid?'on':''?>">Semua</a><?php foreach($kat as $k):?><a class="<?=$kid==$k['id_kategori']?'on':''?>" href="produk.php?kat=<?=$k['id_kategori']?><?=$q?'&q='.urlencode($q):''?>"><?=e($k['nama_kategori'])?></a><?php endforeach;?></div>
<?php if(!$ps):?><div class="empty">Produk tidak ditemukan. Coba kata kunci atau kategori lain.</div><?php else:?><div class="grid"><?php foreach($ps as $p) card($p);?></div><?php endif;?>
<?php foot();
