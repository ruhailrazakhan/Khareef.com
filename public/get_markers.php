<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

try {

  $sql = "
    SELECT 
      m.id,
      m.title,
      m.short_text,
      m.description,
      m.lat,
      m.lng,
      m.type,
      GROUP_CONCAT(mi.path ORDER BY mi.sort_order SEPARATOR '||') AS images
    FROM markers m
    LEFT JOIN marker_images mi ON mi.marker_id = m.id
    GROUP BY m.id
    ORDER BY m.id DESC
  ";

  $rows = db()->query($sql)->fetchAll();

  foreach ($rows as &$row) {
    $row['lat'] = (float)$row['lat'];
    $row['lng'] = (float)$row['lng'];
    $row['images'] = $row['images']
      ? explode('||', $row['images'])
      : [];
  }

  echo json_encode([
    'ok' => true,
    'data' => $rows
  ], JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'ok' => false,
    'error' => $e->getMessage()
  ]);
}
