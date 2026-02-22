<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';

if (function_exists('require_admin')) {
  require_admin();
} else {
  if (!function_exists('is_admin') || !is_admin()) { http_response_code(403); exit; }
}

header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: [];

$image_id  = (int)($data['image_id'] ?? 0);
$marker_id = (int)($data['marker_id'] ?? 0);

if ($image_id<=0 || $marker_id<=0) { echo json_encode(['ok'=>false,'error'=>'Missing ids']); exit; }

try {
  $st = db()->prepare("SELECT path FROM marker_images WHERE id=? AND marker_id=?");
  $st->execute([$image_id, $marker_id]);
  $row = $st->fetch();
  if(!$row) { echo json_encode(['ok'=>false,'error'=>'Not found']); exit; }

  $p = (string)$row['path'];
  $full = __DIR__ . '/../' . ltrim($p, '/');
  if (is_file($full)) @unlink($full);

  db()->prepare("DELETE FROM marker_images WHERE id=? AND marker_id=?")->execute([$image_id, $marker_id]);
  echo json_encode(['ok'=>true]);
} catch(Throwable $e){
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}