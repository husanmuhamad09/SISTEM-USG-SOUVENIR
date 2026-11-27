<?php require '_layout.php';$q=trim($_GET['q']??'');
$st=$pdo->prepare("SELECT p.*,COUNT(t.id_transaksi) n FROM pelanggan p LEFT JOIN transaksi t USING(id_pelanggan) WHERE nama_pelanggan LIKE ? OR no_telepon LIKE ? GROUP BY p.id_pelanggan ORDER BY id_pelanggan DESC");$st->execute(["%$q%","%$q%"]);
ahead('Daftar Pelanggan','pelanggan');?>
<form class="inl"><label>Cari nama atau nomor<input name="q" value="<?=e($q)?>"></label><button class="btn">Cari</button></form><br>
<table class="tbl"><tr><th>Nama</th><th>No. telepon</th><th>Alamat/catatan</th><th>Transaksi</th></tr>
<?php foreach($st as $p):?><tr><td><?=e($p['nama_pelanggan'])?></td><td><a href="https://wa.me/<?=preg_replace('/^0/','62',e($p['no_telepon']))?>"><?=e($p['no_telepon'])?></a></td><td><?=e($p['alamat'])?></td><td><?=$p['n']?></td></tr><?php endforeach;?></table>
<?php afoot();
