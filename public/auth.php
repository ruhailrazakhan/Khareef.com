<?php
// public/auth.php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function admin_session_start(): void {
  if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    // Optional: if your site is HTTPS, enable secure cookies
    // ini_set('session.cookie_secure', '1');
   // session_start();
  }
}

function admin_current(): ?array {
  admin_session_start();
  $sid = $_SESSION['admin_sid'] ?? '';
  if (!$sid) return null;

  $st = db()->prepare("
    SELECT s.id sid, s.expires_at,
           a.id, a.name, a.email, a.role, a.is_active
    FROM admin_sessions s
    JOIN admins a ON a.id = s.admin_id
    WHERE s.id = ?
    LIMIT 1
  ");
  $st->execute([$sid]);
  $row = $st->fetch();
  if (!$row) return null;

  if ((int)$row['is_active'] !== 1) return null;

  if (strtotime((string)$row['expires_at']) <= time()) {
    db()->prepare("DELETE FROM admin_sessions WHERE id=?")->execute([$sid]);
    unset($_SESSION['admin_sid']);
    return null;
  }

  return $row;
}

function require_admin(): array {
  $a = admin_current();
  if (!$a) {
    header("Location: /admin/login.php");
    exit;
  }
  return $a;
}

function require_super_admin(): array {
  $a = require_admin();
  if (($a['role'] ?? '') !== 'super_admin') {
    http_response_code(403);
    echo "Forbidden (super admin only).";
    exit;
  }
  return $a;
}

function admin_login(string $email, string $password): bool {
  admin_session_start();

  $email = trim($email);
  if ($email === '' || $password === '') return false;

  $st = db()->prepare("SELECT * FROM admins WHERE email=? LIMIT 1");
  $st->execute([$email]);
  $a = $st->fetch();
  if (!$a) return false;
  if ((int)$a['is_active'] !== 1) return false;

  if (!password_verify($password, (string)$a['password_hash'])) return false;

  $sid = bin2hex(random_bytes(32));
  $expires = date('Y-m-d H:i:s', time() + 86400 * 14); // 14 days
  $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
  $ip = substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64);

  db()->prepare("INSERT INTO admin_sessions(id, admin_id, expires_at, user_agent, ip) VALUES(?,?,?,?,?)")
    ->execute([$sid, (int)$a['id'], $expires, $ua, $ip]);

  $_SESSION['admin_sid'] = $sid;
  return true;
}

function admin_logout(): void {
  admin_session_start();
  $sid = $_SESSION['admin_sid'] ?? '';
  if ($sid) db()->prepare("DELETE FROM admin_sessions WHERE id=?")->execute([$sid]);

  $_SESSION = [];
  session_destroy();
}