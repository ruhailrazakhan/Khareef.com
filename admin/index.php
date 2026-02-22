<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once __DIR__ . '/../public/auth.php';
//require_once __DIR__ . '/../public/db.php';

$me = require_admin();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    
<nav class="navbar bg-white border-bottom sticky-top">
  <div class="container-fluid">
    <span class="fw-bold">Admin Panel</span>
    <div class="d-flex gap-2 align-items-center">
         <a href="/../public/index.php" class="btn btn-outline-primery btn-sm">Back Webiste</a>
      <span class="small text-secondary"><?= htmlspecialchars($me['email']) ?></span>
      <a class="btn btn-outline-danger btn-sm" href="/admin/logout.php">Logout</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="row g-3">
    <div class="col-12 col-lg-6">
      <a class="card p-3 text-decoration-none" href="/admin/markers.php">
        <div class="fw-bold">Manage Markers</div>
        <div class="text-secondary small">Add / Edit / Delete / Images</div>
      </a>
    </div>

    <?php if (($me['role'] ?? '') === 'super_admin'): ?>
    <div class="col-12 col-lg-6">
      <a class="card p-3 text-decoration-none" href="/admin/admins.php">
        <div class="fw-bold">Manage Admins</div>
        <div class="text-secondary small">Add other super admins</div>
      </a>
    </div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>