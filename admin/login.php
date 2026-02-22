<?php

error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/../public/db.php';
require_once __DIR__ . '/../public/auth.php';

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim((string)($_POST['email'] ?? ''));
  $pass  = (string)($_POST['password'] ?? '');

  if (admin_login($email, $pass)) {
    header("Location: /admin/index.php");
    exit;
  }
  $err = "Invalid login.";
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:520px">
  <div class="card shadow-sm border-0 rounded-4">
    <div class="card-body p-4">
      <h4 class="fw-bold mb-1">Admin Login</h4>
      <div class="text-secondary small mb-3">Khareef Control Panel</div>

      <?php if ($err): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
      <?php endif; ?>

      <form method="post" autocomplete="off">
        <div class="mb-3">
          <label class="form-label fw-bold">Email</label>
          <input class="form-control form-control-lg" name="email" required>
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold">Password</label>
          <input class="form-control form-control-lg" type="password" name="password" required>
        </div>
        <button class="btn btn-primary btn-lg w-100 fw-bold">Login</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>