<?php
require_once __DIR__ . '/helpers.php';
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title><?= e($title ?? 'Admin') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family:system-ui;margin:0;background:#f6f7f9}
    .topbar{background:#111;color:#fff;padding:12px 16px;display:flex;justify-content:space-between;align-items:center}
    .topbar a{color:#fff;text-decoration:none;opacity:.9}
    .wrap{max-width:1100px;margin:18px auto;padding:0 14px}
    .card{background:#fff;border-radius:14px;box-shadow:0 2px 14px rgba(0,0,0,.06);padding:14px;margin-bottom:14px}
    .nav a{margin-right:12px}
    input,select,textarea{width:100%;padding:10px;border:1px solid #ddd;border-radius:12px}
    textarea{min-height:120px}
    .row{display:flex;gap:12px;flex-wrap:wrap}
    .col{flex:1;min-width:220px}
    .btn{display:inline-block;background:#111;color:#fff;border:none;padding:10px 14px;border-radius:12px;text-decoration:none;cursor:pointer}
    .btn.secondary{background:#fff;color:#111;border:1px solid #ddd}
    table{width:100%;border-collapse:collapse}
    th,td{padding:10px;border-bottom:1px solid #eee;text-align:left;font-size:14px}
    .muted{color:#666;font-size:13px}
    .pill{display:inline-block;padding:4px 8px;border-radius:999px;background:#eee;font-size:12px}
    .danger{color:#b00020}
  </style>
</head>
<body>
  <div class="topbar">
    <div><strong>Travel Guide Admin</strong></div>
    <div class="nav">
      <a href="index.php">Dashboard</a>
      <a href="settings.php">Settings</a>
      <a href="types.php">Marker Types</a>
      <a href="locations.php">Locations</a>
      <a href="logout.php">Logout</a>
    </div>
  </div>
  <div class="wrap">
    <?= $content ?? '' ?>
  </div>
</body>
</html>
