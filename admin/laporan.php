<?php require '_layout.php';
$d1=$_GET['dari']??date('Y-m-01');$d2=$_GET['sampai']??date('Y-m-d');
$st=$pdo->prepare("SELECT t.*,p.nama_pelanggan FROM transaksi t JOIN pelanggan p USING(id_pelanggan) WHERE status<>'Batal' AND DATE(tanggal_transaksi) BETWEEN ? AND ? ORDER BY tanggal_transaksi DESC");$st->execute([$d1,$d2]);$rows=$st->fetchAll();
ahead('Laporan Penjualan','laporan');?>
<form class="inl no-print"><label>Dari tanggal<input type="date" name="dari" value="<?=e($d1)?>"></label><label>Sampai tanggal<input type="date" name="sampai" value="<?=e($d2)?>"></label><button class="btn">Tampilkan</button><button type="button" class="btn ghost" onclick="print()">Cetak</button></form>
<div class="stats"><div class="stat">Jumlah transaksi<b><?=count($rows)?></b></div><div class="stat">Total penjualan<b><?=rp(array_sum(array_column($rows,'total_transaksi')))?></b></div></div>
<table class="tbl"><tr><th>Kode</th><th>Tanggal</th><th>Pelanggan</th><th>Total</th><th>Status</th></tr>
<?php foreach($rows as $r):?><tr><td><?=e($r['kode'])?></td><td><?=date('d/m/Y H:i',strtotime($r['tanggal_transaksi']))?></td><td><?=e($r['nama_pelanggan'])?></td><td><?=rp($r['total_transaksi'])?></td><td><span class="pill"><?=e($r['status'])?></span></td></tr><?php endforeach;?>
<?php if(!$rows):?><tr><td colspan="5">Tidak ada transaksi pada periode ini.</td></tr><?php endif;?></table>
<?php afoot();
