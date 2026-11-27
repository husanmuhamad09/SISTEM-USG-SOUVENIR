<?php require __DIR__.'/../config.php'; need_admin();
function ahead($t,$on){$f=flash();$m=['index'=>'Dashboard','transaksi'=>'Transaksi','produk'=>'Data Produk','pelanggan'=>'Pelanggan','laporan'=>'Laporan'];?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($t)?> · Admin USGallery</title><link rel="stylesheet" href="../assets/style.css"></head><body><div class="adm">
<aside class="side"><a class="brand" href="index.php">US<span>Gallery</span></a><?php foreach($m as $k=>$v):?><a class="<?=$on==$k?'on':''?>" href="<?=$k?>.php"><?=$v?></a><?php endforeach;?><a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin logout?')">Logout</a></aside>
<div class="cont"><h1><?=e($t)?></h1><?php if($f):?><div class="alert"><?=e($f)?></div><?php endif;}
function afoot(){?></div></div></body></html><?php }
