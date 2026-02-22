<?php

require_once __DIR__ . '/../public/auth.php';
require_once __DIR__ . '/../public/db.php';

if (function_exists('require_admin')) {
  require_admin();
} else {
  if (!function_exists('is_admin') || !is_admin()) { http_response_code(403); exit; }
}

header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: [];
$id = (int)($data['id'] ?? 0);

if ($id <= 0) { echo json_encode(['ok'=>false,'error'=>'Missing id']); exit; }

try {
  db()->beginTransaction();

  // delete files from disk
  $st = db()->prepare("SELECT path FROM marker_images WHERE marker_id=?");
  $st->execute([$id]);
  $imgs = $st->fetchAll();
  foreach($imgs as $r){
    $p = (string)$r['path'];
   $full = __DIR__ . '/../' . ltrim($p, '/');
    if (is_file($full)) @unlink($full);
  }

  db()->prepare("DELETE FROM marker_images WHERE marker_id=?")->execute([$id]);
  db()->prepare("DELETE FROM markers WHERE id=?")->execute([$id]);

  db()->commit();
  echo json_encode(['ok'=>true]);
} catch(Throwable $e){
  db()->rollBack();
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}