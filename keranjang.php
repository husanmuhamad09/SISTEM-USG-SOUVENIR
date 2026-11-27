<?php require 'config.php'; require 'includes/layout.php';
$c=$_SESSION['cart']??[];$a=$_POST['aksi']??'';
if($a=='tambah'){$id=(int)$_POST['id'];$q=max(1,(int)$_POST['qty']);$c[$id]['qty']=min(999,($c[$id]['qty']??0)+$q);$_SESSION['cart']=$c;
 if(isset($_POST['langsung'])){header('Location: keranjang.php#pesan');exit;}flash('Produk ditambahkan ke keranjang.');header('Location: keranjang.php');exit;}
if($a=='ubah'){foreach($_POST['qty']??[] as $id=>$q){if((int)$q>0)$c[(int)$id]['qty']=(int)$q;else unset($c[(int)$id]);}$_SESSION['cart']=$c;header('Location: keranjang.php');exit;}
if($a=='hapus'){unset($c[(int)$_POST['id']]);$_SESSION['cart']=$c;header('Location: keranjang.php');exit;}
$items=[];$total=0;
if($c){$in=implode(',',array_map('intval',array_keys($c)));foreach($pdo->query("SELECT * FROM produk WHERE id_produk IN($in)") as $p){$p['qty']=$c[$p['id_produk']]['qty'];$p['sub']=$p['qty']*$p['harga'];$total+=$p['sub'];$items[]=$p;}}
if($a=='pesan'&&$items){
 $nm=trim($_POST['nama']);$tl=preg_replace('/[^0-9+]/','',$_POST['telp']);$al=trim($_POST['alamat']);
 if($nm===''||strlen($tl)<8){flash('Nama dan nomor HP wajib diisi dengan benar.');header('Location: keranjang.php#pesan');exit;}
 $pdo->beginTransaction();
 $pdo->prepare("INSERT INTO pelanggan(nama_pelanggan,no_telepon,alamat) VALUES(?,?,?)")->execute([$nm,$tl,$al]);$pid=$pdo->lastInsertId();
 $pdo->prepare("INSERT INTO transaksi(id_pelanggan,total_transaksi,status,sumber) VALUES(?,?, 'Menunggu','WhatsApp')")->execute([$pid,$total]);$tid=$pdo->lastInsertId();
 $kode='ORD-'.date('Y').'-'.str_pad($tid,3,'0',STR_PAD_LEFT);$pdo->prepare("UPDATE transaksi SET kode=? WHERE id_transaksi=?")->execute([$kode,$tid]);
 $ins=$pdo->prepare("INSERT INTO detail_transaksi(id_transaksi,id_produk,jumlah,harga,subtotal) VALUES(?,?,?,?,?)");
 $msg="Halo USGallery, saya ingin memesan:\n";$i=0;
 foreach($items as $p){$ins->execute([$tid,$p['id_produk'],$p['qty'],$p['harga'],$p['sub']]);$msg.=(++$i).". {$p['nama_produk']} x{$p['qty']} - ".($p['harga']>0?rp($p['sub']):'harga menyusul')."\n";}
 $pdo->commit();
 $msg.="\nTotal: ".rp($total)."\nNama: $nm\nNo. HP: $tl\nAlamat/catatan: ".($al?:'-')."\nKode pesanan: $kode";
 unset($_SESSION['cart']);header('Location: https://wa.me/'.WA_NUMBER.'?text='.rawurlencode($msg));exit;}
head('Keranjang');?>
<h1>Keranjang</h1>
<?php if(!$items):?><div class="empty">Keranjang masih kosong. <a href="produk.php">Lihat produk</a></div><?php else:?>
<form method="post"><input type="hidden" name="aksi" value="ubah"><table class="tbl"><tr><th>Produk</th><th>Harga</th><th>Jumlah</th><th>Subtotal</th><th></th></tr>
<?php foreach($items as $p):?><tr><td><?=e($p['nama_produk'])?></td><td><?=rp($p['harga'])?></td><td><input type="number" name="qty[<?=$p['id_produk']?>]" value="<?=$p['qty']?>" min="0" style="width:70px"></td><td><?=rp($p['sub'])?></td>
<td><button class="link" form="h<?=$p['id_produk']?>">Hapus</button></td></tr><?php endforeach;?>
<tr><td colspan="3"><button class="btn ghost sm">Perbarui jumlah</button></td><td colspan="2"><b>Total <?=rp($total)?></b></td></tr></table></form>
<?php foreach($items as $p):?><form id="h<?=$p['id_produk']?>" method="post"><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?=$p['id_produk']?>"></form><?php endforeach;?>
<form method="post" id="pesan" class="box"><input type="hidden" name="aksi" value="pesan"><h2>Data pemesan</h2>
<label>Nama<input name="nama" required></label><label>Nomor HP/WhatsApp<input name="telp" required inputmode="tel" placeholder="08xxxxxxxxxx"></label><label>Alamat atau catatan<textarea name="alamat" rows="2"></textarea></label>
<button class="btn wa">Pesan via WhatsApp</button><p class="muted">Pesan terisi otomatis dengan daftar produk, tinggal kirim.</p></form><?php endif;?>
<?php foot();
