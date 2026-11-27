<?php
session_start();
const DB_HOST='localhost', DB_NAME='usgallery', DB_USER='root', DB_PASS='';
const WA_NUMBER='6281234567890'; // GANTI: nomor WhatsApp USG, format 62xxxxxxxxxx
$pdo=new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
function e($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function rp($n){return 'Rp '.number_format((float)$n,0,',','.');}
function cart(){return $_SESSION['cart']??[];}
function flash($m=null){if($m!==null){$_SESSION['flash']=$m;return;}$f=$_SESSION['flash']??'';unset($_SESSION['flash']);return $f;}
function need_admin(){if(empty($_SESSION['admin'])){header('Location: login.php');exit;}}
