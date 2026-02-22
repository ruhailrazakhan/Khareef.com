<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors','1');
ini_set('display_startup_errors','1');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_admin();

header('Content-Type: application/json; charset=utf-8');

function ok($data=[]){ echo json_encode(['ok'=>true]+$data, JSON_UNESCAPED_SLASHES); exit; }
function fail($msg,$code=400){ http_response_code($code); echo json_encode(['ok'=>false,'error'=>$msg], JSON_UNESCAPED_SLASHES); exit; }

try{
  $raw = file_get_contents('php://input');
  $j = json_decode($raw, true);
  if(!is_array($j)) fail("Invalid JSON");

  $marker_id = (int)($j['marker_id'] ?? 0);
  $order = $j['order'] ?? null;

  if($marker_id<=0) fail("Missing marker_id");
  if(!is_array($order) || count($order)===0) fail("Missing order array");

  db()->beginTransaction();
  $st = db()->prepare("UPDATE marker_images SET sort_order=? WHERE marker_id=? AND id=?");

  $i=0;
  foreach($order as $imgIdRaw){
    $imgId = (int)$imgIdRaw;
    if($imgId<=0) continue;
    $st->execute([$i, $marker_id, $imgId]);
    $i++;
  }

  db()->commit();
  ok(['marker_id'=>$marker_id]);

}catch(Throwable $e){
  if(db()->inTransaction()) db()->rollBack();
  fail("Server error: ".$e->getMessage(), 500);
}