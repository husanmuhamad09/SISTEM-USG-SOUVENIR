<?php require __DIR__.'/../config.php';$err='';
if($_SERVER['REQUEST_METHOD']=='POST'){$st=$pdo->prepare("SELECT * FROM admin WHERE username=?");$st->execute([trim($_POST['username'])]);$a=$st->fetch();
 if($a&&password_verify($_POST['password'],$a['password'])){session_regenerate_id(true);$_SESSION['admin']=$a['id_admin'];header('Location: index.php');exit;}
 $err='Username atau password salah.';}
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login Admin</title><link rel="stylesheet" href="../assets/style.css"></head><body>
<form method="post" class="box login"><h2>Login Admin USGallery</h2><?php if($err):?><div class="alert"><?=e($err)?></div><?php endif;?>
<label>Username<input name="username" required autofocus></label><label>Password<input type="password" name="password" required></label><button class="btn">Masuk</button></form></body></html>
