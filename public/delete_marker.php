<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors','1');
ini_set('display_startup_errors','1');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_admin();



function ok($data=[]){ echo json_encode(['ok'=>true]+$data, JSON_UNESCAPED_SLASHES); exit; }
function fail($msg,$code=400){ http_response_code($code); echo json_encode(['ok'=>false,'error'=>$msg], JSON_UNESCAPED_SLASHES); exit; }

try{
  $raw = file_get_contents('php://input');
  $j = json_decode($raw, true);
  $id = (int)($j['id'] ?? 0);
  if($id<=0) fail("Missing id");

  db()->beginTransaction();

  // collect image files
  $st = db()->prepare("SELECT path FROM marker_images WHERE marker_id=?");
  $st->execute([$id]);
  $paths = $st->fetchAll();

  // delete marker (cascade should delete marker_images if FK exists)
  $del = db()->prepare("DELETE FROM markers WHERE id=?");
  $del->execute([$id]);

  // if no FK cascade, also delete images rows (safe)
  db()->prepare("DELETE FROM marker_images WHERE marker_id=?")->execute([$id]);

  db()->commit();

  // delete files on disk
  foreach($paths as $p){
    $path = (string)($p['path'] ?? '');
    if(!$path) continue;
    $full = __DIR__ . '/' . ltrim($path,'/');
    if(is_file($full)) @unlink($full);
  }

  ok(['id'=>$id]);

}catch(Throwable $e){
  if(db()->inTransaction()) db()->rollBack();
  fail("Server error: ".$e->getMessage(), 500);
}