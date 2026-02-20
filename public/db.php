<?php
require_once __DIR__.'/config.php';

function db(): PDO {
  static $pdo = null;
  if ($pdo) return $pdo;

  $db_host = 'localhost';                 // usually localhost on cPanel
  $db_name = 'kudkrzte_khareefv3';
  $db_user = 'kudkrzte_khareefv3';
  $db_pass = '';

  $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";

  $pdo = new PDO($dsn, $db_user, $db_pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);

  return $pdo;
}