<?php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm_logout'] ?? '') === '1') {
    unset($_SESSION['admin']);
    flash('Anda telah keluar dari panel admin.');
    header('Location: login.php');
    exit;
}

header('Location: index.php');
exit;