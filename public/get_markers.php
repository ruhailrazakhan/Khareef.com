<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

function out($arr){ echo json_encode($arr, JSON_UNESCAPED_UNICODE); exit; }

try {
  // markers
  $rows = db()->query("
    SELECT id, title, short_text, description, lat, lng, type,
           place_id, use_google_photos, google_thumb_index,
           created_at
    FROM markers
    ORDER BY id DESC
  ")->fetchAll(PDO::FETCH_ASSOC);

  $ids = array_map(fn($r)=>(int)$r['id'], $rows);
  $imagesByMarker = [];

  if ($ids) {
    $in = implode(',', array_fill(0, count($ids), '?'));
    $st = db()->prepare("
      SELECT marker_id, path
      FROM marker_images
      WHERE marker_id IN ($in)
      ORDER BY marker_id ASC, sort_order ASC, id ASC
    ");
    $st->execute($ids);
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $img) {
      $mid = (int)$img['marker_id'];
      $imagesByMarker[$mid][] = $img['path'];
    }
  }

  $data = [];
  foreach ($rows as $r) {
    $id = (int)$r['id'];
    $data[] = [
      'id' => $id,
      'title' => $r['title'],
      'short_text' => $r['short_text'],
      'description' => $r['description'],
      'lat' => (float)$r['lat'],
      'lng' => (float)$r['lng'],
      'type' => $r['type'] ?: 'location',

      // google photos config (NO image urls stored)
      'place_id' => $r['place_id'],
      'use_google_photos' => (int)$r['use_google_photos'],
      'google_thumb_index' => (int)$r['google_thumb_index'],

      // custom images you uploaded
      'images' => $imagesByMarker[$id] ?? [],
    ];
  }

  out(['ok'=>true,'data'=>$data]);

} catch (Throwable $e) {
  out(['ok'=>false,'error'=>$e->getMessage()]);
}
