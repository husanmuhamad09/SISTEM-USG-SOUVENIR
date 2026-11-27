<?php
require_once __DIR__ . '/../config.php';

if (!empty($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = $pdo->prepare("SELECT id_admin, username, password FROM admin WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $admin = $stmt->fetch();

        $storedHash = (string)($admin['password'] ?? '');
        $validPass = false;

        if ($admin && $storedHash !== '') {
            if (password_verify($password, $storedHash)) {
                $validPass = true;
            } elseif (hash_equals($storedHash, $password)) {
                $validPass = true;
            }
        }

        if ($validPass) {
            session_regenerate_id(true);
            $_SESSION['admin'] = [
                'id_admin' => (int)$admin['id_admin'],
                'username' => $admin['username']
            ];
            flash('Selamat datang kembali, ' . $admin['username'] . '.');
            header('Location: index.php');
            exit;
        } else {
            $error = 'Username atau password tidak valid.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Admin — USGallery</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/style.css">
  <style>
    body {
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
    }
    .login-logo-box {
      width: 84px;
      height: 84px;
      border-radius: 14px;
      background: #e9f5ee;
      color: #146c43;
      font-weight: 800;
      font-size: 1.15rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 10px;
    }
  </style>
</head>
<body>
  <div class="container px-3">
    <div class="login card border-0 shadow-sm rounded-4 p-4">
      <div class="text-center mb-4">
        <div class="login-logo-box">USG</div>
        <div class="fw-extrabold fs-5 text-dark">Login Admin</div>
        <div class="small text-muted">USGallery — UNUSA Souvenir Gallery</div>
      </div>

      <?php if ($msg = flash()): ?>
        <div class="alert alert-success small py-2"><?= e($msg) ?></div>
      <?php endif; ?>

      <?php if ($error !== ''): ?>
        <div class="alert alert-danger small py-2"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="post" action="login.php">
        <div class="mb-3">
          <label for="username" class="form-label small fw-semibold">Username</label>
          <input type="text" id="username" name="username" value="<?= e($_POST['username'] ?? '') ?>"
                 class="form-control bg-light" placeholder="Username" required autofocus>
        </div>

        <div class="mb-4">
          <label for="password" class="form-label small fw-semibold">Password</label>
          <input type="password" id="password" name="password"
                 class="form-control bg-light" placeholder="Password" required>
        </div>

        <button type="submit" class="btn btn-success w-100 py-2 rounded-3 fw-semibold">
          Login
        </button>
      </form>

      <div class="text-center mt-4">
        <a href="../index.php" class="small text-success fw-semibold">&larr; Kembali ke Katalog Pembeli</a>
      </div>
    </div>
  </div>
</body>
</html>