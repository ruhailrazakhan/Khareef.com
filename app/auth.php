<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function require_admin(): void {
  if (empty($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
  }
}
