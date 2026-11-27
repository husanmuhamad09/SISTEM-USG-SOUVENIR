<?php require '_layout.php';
$a=$_POST['aksi']??'';
if($a=='hapus'){try{$pdo->prepare("DELETE FROM produk WHERE id_produk=?")->execute([(int)$_POST['id']]);flash('Produk dihapus.');}catch(PDOException $x){flash('Produk sudah ada di transaksi, tidak bisa dihapus. Ubah stok menjadi 0 sebagai gantinya.');}header('Location: produk.php');exit;}
if($a=='simpan'){$id=(int)$_POST['id'];$n=trim($_POST['nama']);$h=(float)$_POST['harga'];$s=(int)$_POST['stok'];
 if($n===''||$h<0||$s<0){flash('Data produk tidak valid.');header('Location: produk.php');exit;}
 $g=null;if(!empty($_FILES['gambar']['name'])){$ext=strtolower(pathinfo($_FILES['gambar']['name'],PATHINFO_EXTENSION));
  if(in_array($ext,['jpg','jpeg','png','webp'])&&$_FILES['gambar']['size']<=2097152&&getimagesize($_FILES['gambar']['tmp_name'])){$g=uniqid('p_').'.'.$ext;move_uploaded_file($_FILES['gambar']['tmp_name'],__DIR__.'/../uploads/'.$g);}
  else{flash('Gambar harus JPG/PNG/WEBP maksimal 2 MB.');header('Location: produk.php');exit;}}
 if($id){$pdo->prepare("UPDATE produk SET id_kategori=?,nama_produk=?,harga=?,stok=?,deskripsi=?".($g?",gambar=?":"")." WHERE id_produk=?")->execute(array_merge([$_POST['kategori'],$n,$h,$s,$_POST['deskripsi']],$g?[$g]:[],[$id]));flash('Produk diperbarui.');}
 else{$pdo->prepare("INSERT INTO produk(id_kategori,nama_produk,harga,stok,deskripsi,gambar) VALUES(?,?,?,?,?,?)")->execute([$_POST['kategori'],$n,$h,$s,$_POST['deskripsi'],$g]);flash('Produk ditambahkan.');}
 header('Location: produk.php');exit;}
$kat=$pdo->query("SELECT * FROM kategori")->fetchAll();$ed=null;
if(isset($_GET['edit'])){$st=$pdo->prepare("SELECT * FROM produk WHERE id_produk=?");$st->execute([(int)$_GET['edit']]);$ed=$st->fetch();}
$show=$ed||isset($_GET['tambah']);
ahead('Data Produk','produk');?>
<?php if($show):?><form method="post" enctype="multipart/form-data" class="box"><h2><?=$ed?'Ubah produk':'Tambah produk'?></h2><input type="hidden" name="aksi" value="simpan"><input type="hidden" name="id" value="<?=$ed['id_produk']??0?>">
<label>Nama produk<input name="nama" required value="<?=e($ed['nama_produk']??'')?>"></label>
<label>Kategori<select name="kategori"><?php foreach($kat as $k):?><option value="<?=$k['id_kategori']?>" <?=($ed['id_kategori']??0)==$k['id_kategori']?'selected':''?>><?=e($k['nama_kategori'])?></option><?php endforeach;?></select></label>
<label>Harga (Rp, isi 0 jika sesuai permintaan)<input type="number" name="harga" min="0" required value="<?=e($ed['harga']??'')?>"></label><label>Stok<input type="number" name="stok" min="0" required value="<?=e($ed['stok']??0)?>"></label>
<label>Deskripsi<textarea name="deskripsi" rows="3"><?=e($ed['deskripsi']??'')?></textarea></label><label>Gambar (JPG/PNG/WEBP, maks. 2 MB)<input type="file" name="gambar" accept="image/*"></label>
<button class="btn">Simpan</button> <a class="btn ghost" href="produk.php">Batal</a></form>
<?php else:?><p><a class="btn" href="?tambah=1">Tambah produk</a></p>
<table class="tbl"><tr><th>Gambar</th><th>Nama</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Aksi</th></tr>
<?php foreach($pdo->query("SELECT p.*,k.nama_kategori FROM produk p JOIN kategori k USING(id_kategori) ORDER BY id_produk DESC") as $p):?><tr><td><?=$p['gambar']?'<img src="../uploads/'.e($p['gambar']).'">':'🎁'?></td><td><?=e($p['nama_produk'])?></td><td><?=e($p['nama_kategori'])?></td><td><?=rp($p['harga'])?></td><td><?=$p['stok']?></td>
<td><a href="?edit=<?=$p['id_produk']?>">Edit</a> <form method="post" style="display:inline" onsubmit="return confirm('Hapus produk ini?')"><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?=$p['id_produk']?>"><button class="link">Hapus</button></form></td></tr><?php endforeach;?></table><?php endif;?>
<?php afoot();
