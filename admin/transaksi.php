<?php require '_layout.php';$a=$_POST['aksi']??'';
if($a=='status'){$pdo->prepare("UPDATE transaksi SET status=? WHERE id_transaksi=?")->execute([$_POST['status'],(int)$_POST['id']]);flash('Status diperbarui.');header('Location: transaksi.php');exit;}
if($a=='baru'){$qty=array_filter(array_map('intval',$_POST['qty']??[]),fn($x)=>$x>0);$nm=trim($_POST['nama']);
 if(!$qty||(!$_POST['pelanggan']&&$nm==='')){flash('Pilih pelanggan dan minimal satu produk.');header('Location: transaksi.php?baru=1');exit;}
 $pdo->beginTransaction();$pid=(int)$_POST['pelanggan'];
 if(!$pid){$pdo->prepare("INSERT INTO pelanggan(nama_pelanggan,no_telepon,alamat) VALUES(?,?,?)")->execute([$nm,trim($_POST['telp']),'']);$pid=$pdo->lastInsertId();}
 $pdo->prepare("INSERT INTO transaksi(id_admin,id_pelanggan,status,sumber) VALUES(?,?,'Selesai','Admin')")->execute([$_SESSION['admin'],$pid]);$tid=$pdo->lastInsertId();$tot=0;
 $ps=$pdo->prepare("SELECT harga FROM produk WHERE id_produk=?");$ins=$pdo->prepare("INSERT INTO detail_transaksi(id_transaksi,id_produk,jumlah,harga,subtotal) VALUES(?,?,?,?,?)");
 foreach($qty as $id=>$j){$ps->execute([$id]);$h=$ps->fetchColumn();$ins->execute([$tid,$id,$j,$h,$h*$j]);$tot+=$h*$j;$pdo->prepare("UPDATE produk SET stok=GREATEST(stok-?,0) WHERE id_produk=?")->execute([$j,$id]);}
 $pdo->prepare("UPDATE transaksi SET total_transaksi=?,kode=? WHERE id_transaksi=?")->execute([$tot,'ORD-'.date('Y').'-'.str_pad($tid,3,'0',STR_PAD_LEFT),$tid]);$pdo->commit();
 flash('Transaksi berhasil dibuat.');header('Location: transaksi.php');exit;}
ahead('Transaksi','transaksi');
if(isset($_GET['baru'])):?>
<form method="post"><input type="hidden" name="aksi" value="baru"><div class="box"><h2>Pelanggan</h2>
<label>Pelanggan lama<select name="pelanggan"><option value="0">— pelanggan baru —</option><?php foreach($pdo->query("SELECT * FROM pelanggan ORDER BY nama_pelanggan") as $p):?><option value="<?=$p['id_pelanggan']?>"><?=e($p['nama_pelanggan'].' · '.$p['no_telepon'])?></option><?php endforeach;?></select></label>
<label>Nama pelanggan baru<input name="nama"></label><label>No. telepon<input name="telp"></label></div>
<table class="tbl"><tr><th>Produk</th><th>Harga</th><th>Stok</th><th>Jumlah</th></tr><?php foreach($pdo->query("SELECT * FROM produk WHERE harga>0 ORDER BY nama_produk") as $p):?><tr><td><?=e($p['nama_produk'])?></td><td><?=rp($p['harga'])?></td><td><?=$p['stok']?></td><td><input type="number" name="qty[<?=$p['id_produk']?>]" min="0" max="<?=$p['stok']?>" value="0" style="width:80px"></td></tr><?php endforeach;?></table><br>
<button class="btn">Buat transaksi</button> <a class="btn ghost" href="transaksi.php">Batal</a></form>
<?php else:?><p><a class="btn" href="?baru=1">Transaksi baru</a></p>
<table class="tbl"><tr><th>Kode</th><th>Tanggal</th><th>Pelanggan</th><th>Item</th><th>Total</th><th>Sumber</th><th>Status</th></tr>
<?php foreach($pdo->query("SELECT t.*,p.nama_pelanggan,(SELECT GROUP_CONCAT(CONCAT(pr.nama_produk,' x',d.jumlah) SEPARATOR ', ') FROM detail_transaksi d JOIN produk pr USING(id_produk) WHERE d.id_transaksi=t.id_transaksi) item FROM transaksi t JOIN pelanggan p USING(id_pelanggan) ORDER BY id_transaksi DESC") as $t):?>
<tr><td><?=e($t['kode'])?></td><td><?=date('d/m/Y H:i',strtotime($t['tanggal_transaksi']))?></td><td><?=e($t['nama_pelanggan'])?></td><td><?=e($t['item'])?></td><td><?=rp($t['total_transaksi'])?></td><td><?=e($t['sumber'])?></td>
<td><form method="post"><input type="hidden" name="aksi" value="status"><input type="hidden" name="id" value="<?=$t['id_transaksi']?>"><select name="status" onchange="this.form.submit()"><?php foreach(['Menunggu','Diproses','Siap Diambil','Selesai','Batal'] as $s):?><option <?=$t['status']==$s?'selected':''?>><?=$s?></option><?php endforeach;?></select></form></td></tr><?php endforeach;?></table><?php endif;?>
<?php afoot();
