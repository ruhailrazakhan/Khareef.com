<?php
// auth.php
// Minimal admin gate. You can upgrade later to real login.
// Usage: open any page with ?admin=1 once -> sets cookie for 30 days.

function is_admin(): bool { 
  if (isset($_GET['admin']) && $_GET['admin'] === '1') {
    setcookie('tg_admin', '1', time() + 86400*30, '/');
    $_COOKIE['tg_admin'] = '1';
  }
  return isset($_COOKIE['tg_admin']) && $_COOKIE['tg_admin'] === '1';
}

function require_admin(): void {
  if (!is_admin()) {
    http_response_code(403);
    echo "Forbidden (admin only). Add ?admin=1 once to enable admin cookie.";
    exit;
  }
}