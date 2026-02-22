<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json; charset=utf-8');

function fail(string $msg, int $code=400): void {
  http_response_code($code);
  echo json_encode(['ok'=>false,'error'=>$msg], JSON_UNESCAPED_SLASHES);
  exit;
}
function ok(array $data=[]): void {
  echo json_encode(['ok'=>true] + $data, JSON_UNESCAPED_SLASHES);
  exit;
}

if (!admin_current()) fail('Admin login required', 403);
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') fail('Method not allowed', 405);

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) fail('Invalid id');

try {
  $pdo = db();
  $pdo->beginTransaction();

  // Get image paths
  $st = $pdo->prepare("SELECT path FROM marker_images WHERE marker_id=?");
  $st->execute([$id]);
  $paths = $st->fetchAll(PDO::FETCH_COLUMN);

  // Delete DB rows
  $pdo->prepare("DELETE FROM marker_images WHERE marker_id=?")->execute([$id]);
  $pdo->prepare("DELETE FROM markers WHERE id=?")->execute([$id]);

  $pdo->commit();

  // Delete files after commit (safe)
  foreach ($paths as $p) {
    $full = __DIR__ . '/' . ltrim((string)$p, '/');
    if (is_file($full)) @unlink($full);
  }

  ok(['id'=>$id]);

} catch (Throwable $e) {
  if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
  fail('Server error: '.$e->getMessage(), 500);
}