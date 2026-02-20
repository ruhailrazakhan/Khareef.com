<?php
// Helper page: generate password hash then paste into app/config.php (ADMIN_PASS_HASH)
$pass = $_GET['p'] ?? '';
?>
<!doctype html><html><head><meta charset="utf-8"><title>Password Hash</title>
<style>body{font-family:system-ui;margin:30px}code{display:block;padding:12px;background:#f6f7f9;border-radius:12px}</style>
</head><body>
<h2>Password Hash Generator</h2>
<p>Use like: <code>/admin/hash.php?p=YourStrongPassword</code></p>
<?php if($pass): ?>
<p>Hash:</p>
<code><?= htmlspecialchars(password_hash($pass, PASSWORD_DEFAULT), ENT_QUOTES, 'UTF-8') ?></code>
<?php endif; ?>
</body></html>
