<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $u = $_POST['username'] ?? '';
  $p = $_POST['password'] ?? '';
  if ($u === ADMIN_USER && password_verify($p, ADMIN_PASS_HASH)) {
    $_SESSION['is_admin'] = 1;
    header('Location: index.php');
    exit;
  }
  $error = 'Invalid login';
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login</title>
<style>
body{font-family:system-ui;background:#f6f7f9;margin:0}
.box{max-width:420px;margin:70px auto;background:#fff;border-radius:14px;box-shadow:0 2px 14px rgba(0,0,0,.06);padding:18px}
input{width:100%;padding:10px;border:1px solid #ddd;border-radius:12px;margin:6px 0}
button{background:#111;color:#fff;border:none;padding:10px 14px;border-radius:12px;cursor:pointer}
</style></head><body>
<div class="box">
<h2>Admin Login</h2>
<?php if($error): ?><p style="color:#b00020"><?= e($error) ?></p><?php endif; ?>
<form method="post">
  <input name="username" placeholder="Username" required>
  <input name="password" type="password" placeholder="Password" required>
  <button>Login</button>
</form>
<p style="color:#666;font-size:13px;margin-top:10px">Tip: Use /admin/hash.php to generate password hash.</p>
</div>
</body></html>
