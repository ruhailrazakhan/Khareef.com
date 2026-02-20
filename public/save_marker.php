<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

require_once __DIR__ . '/db.php';
header('Content-Type: application/json; charset=utf-8');

function ok(array $data = []): void {
  echo json_encode(['ok' => true] + $data, JSON_UNESCAPED_SLASHES);
  exit;
}
function fail(string $msg, int $code = 400): void {
  http_response_code($code);
  echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_SLASHES);
  exit;
}
function ensure_upload_dir(): string {
  $dir = __DIR__ . '/uploads';
  if (!is_dir($dir)) {
    if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
      throw new RuntimeException('Cannot create uploads directory');
    }
  }
  return realpath($dir) ?: $dir;
}

try {
  if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') fail('Method not allowed', 405);

  $title       = trim((string)($_POST['title'] ?? ''));
  $short_text  = trim((string)($_POST['short_text'] ?? ''));
  $description = trim((string)($_POST['description'] ?? ''));
  $content_html = (string)($_POST['content_html'] ?? '');
  $hero_video_url = trim((string)($_POST['hero_video_url'] ?? ''));
  $slider_style = strtolower(trim((string)($_POST['slider_style'] ?? 'cards')));

  $lat         = (string)($_POST['lat'] ?? '');
  $lng         = (string)($_POST['lng'] ?? '');
  $type        = strtolower(trim((string)($_POST['type'] ?? 'location')));

  $allowedTypes = ['location','hotel','petrol','restaurant','cafe'];
  if (!in_array($type, $allowedTypes, true)) $type = 'location';

  $allowedStyles = ['cards','strip'];
  if (!in_array($slider_style, $allowedStyles, true)) $slider_style = 'cards';

  if ($title === '') fail('Title required');
  if (!is_numeric($lat) || !is_numeric($lng)) fail('Invalid lat/lng');

  if (strlen($title) > 150) $title = substr($title, 0, 150);
  if (strlen($short_text) > 255) $short_text = substr($short_text, 0, 255);

  db()->beginTransaction();

  $st = db()->prepare("
    INSERT INTO markers (title, short_text, description, content_html, hero_video_url, slider_style, lat, lng, type)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
  ");
  $st->execute([
    $title, $short_text, $description, $content_html,
    $hero_video_url, $slider_style,
    $lat, $lng, $type
  ]);

  $marker_id = (int)db()->lastInsertId();

  // Upload images
  $uploadDir = ensure_upload_dir();

  if (!empty($_FILES['images']['name'][0])) {
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    $count = count($_FILES['images']['name']);
    for ($i = 0; $i < $count; $i++) {
      if (($_FILES['images']['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;

      $tmp  = $_FILES['images']['tmp_name'][$i] ?? '';
      $size = (int)($_FILES['images']['size'][$i] ?? 0);
      if (!$tmp || !is_file($tmp)) continue;

      if ($size > 5 * 1024 * 1024) continue;

      $mime = mime_content_type($tmp) ?: '';
      if (!isset($allowed[$mime])) continue;
      if (!@getimagesize($tmp)) continue;

      $ext = $allowed[$mime];
      $name = 'm' . $marker_id . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
      $dest = $uploadDir . DIRECTORY_SEPARATOR . $name;

      if (move_uploaded_file($tmp, $dest)) {
        $path = 'uploads/' . $name;
        $st2 = db()->prepare("INSERT INTO marker_images (marker_id, path, sort_order) VALUES (?, ?, ?)");
        $st2->execute([$marker_id, $path, $i]);
      }
    }
  }

  db()->commit();
  ok(['id' => $marker_id]);

} catch (Throwable $e) {
  if (db()->inTransaction()) db()->rollBack();
  fail('Server error: ' . $e->getMessage(), 500);
}
