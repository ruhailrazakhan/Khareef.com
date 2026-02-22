<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

function out(array $payload, int $code = 200): void {
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  exit;
}

function has_column(string $table, string $column): bool {
  $sql = "
    SELECT 1
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = ?
      AND COLUMN_NAME = ?
    LIMIT 1
  ";
  $st = db()->prepare($sql);
  $st->execute([$table, $column]);
  return (bool)$st->fetchColumn();
}

try {
  $hasPlaceId = has_column('markers', 'place_id');
  $hasUseGoogle = has_column('markers', 'use_google_photos');

  // Build SELECT safely (no SQL error if columns missing)
  $selectPlaceId   = $hasPlaceId   ? "m.place_id" : "'' AS place_id";
  $selectUseGoogle = $hasUseGoogle ? "m.use_google_photos" : "0 AS use_google_photos";

  $sql = "
    SELECT
      m.id,
      m.title,
      m.short_text,
      m.description,
      m.lat,
      m.lng,
      m.type,
      $selectPlaceId,
      $selectUseGoogle,
      GROUP_CONCAT(mi.path ORDER BY mi.sort_order SEPARATOR '||') AS images
    FROM markers m
    LEFT JOIN marker_images mi ON mi.marker_id = m.id
    GROUP BY m.id
    ORDER BY m.id DESC
  ";

  $rows = db()->query($sql)->fetchAll();

  foreach ($rows as &$row) {
    $row['id'] = (int)$row['id'];
    $row['lat'] = (float)$row['lat'];
    $row['lng'] = (float)$row['lng'];
    $row['type'] = $row['type'] ?: 'location';

    // Normalize google fields even if missing
    $row['place_id'] = (string)($row['place_id'] ?? '');
    $row['use_google_photos'] = (int)($row['use_google_photos'] ?? 0);

    // images array
    $row['images'] = !empty($row['images']) ? explode('||', $row['images']) : [];
  }

  out([
    'ok' => true,
    'data' => $rows,
    'meta' => [
      'has_place_id' => $hasPlaceId ? 1 : 0,
      'has_use_google_photos' => $hasUseGoogle ? 1 : 0,
    ]
  ]);

} catch (Throwable $e) {
  out([
    'ok' => false,
    'error' => $e->getMessage(),
    'hint' => 'If you want Google photos, add columns: place_id (VARCHAR) + use_google_photos (TINYINT).'
  ], 500);
}