<?php require '_layout.php';
$v=fn($s)=>$pdo->query($s)->fetchColumn();$ok="status<>'Batal'";
ahead('Dashboard','index');?>
<div class="stats">
<div class="stat">Total produk<b><?=$v("SELECT COUNT(*) FROM produk")?></b></div>
<div class="stat">Total transaksi<b><?=$v("SELECT COUNT(*) FROM transaksi")?></b></div>
<div class="stat">Pesanan menunggu<b><?=$v("SELECT COUNT(*) FROM transaksi WHERE status='Menunggu'")?></b></div>
<div class="stat">Pendapatan hari ini<b><?=rp($v("SELECT COALESCE(SUM(total_transaksi),0) FROM transaksi WHERE $ok AND DATE(tanggal_transaksi)=CURDATE()"))?></b></div>
<div class="stat">Pendapatan bulan ini<b><?=rp($v("SELECT COALESCE(SUM(total_transaksi),0) FROM transaksi WHERE $ok AND DATE_FORMAT(tanggal_transaksi,'%Y%m')=DATE_FORMAT(NOW(),'%Y%m')"))?></b></div>
<div class="stat">Stok menipis (≤5)<b><?=$v("SELECT COUNT(*) FROM produk WHERE stok<=5")?></b></div></div>
<h2>Produk terlaris</h2><table class="tbl"><tr><th>Produk</th><th>Terjual</th></tr>
<?php foreach($pdo->query("SELECT p.nama_produk,SUM(d.jumlah) n FROM detail_transaksi d JOIN produk p USING(id_produk) JOIN transaksi t USING(id_transaksi) WHERE t.status<>'Batal' GROUP BY p.id_produk ORDER BY n DESC LIMIT 5") as $r):?><tr><td><?=e($r['nama_produk'])?></td><td><?=$r['n']?></td></tr><?php endforeach;?></table>
<?php afoot();
