<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../public/db.php';
require_once __DIR__ . '/../public/auth.php';
require_once __DIR__ . '/../public/config.php'; // must define GOOGLE_API_KEY

if (!admin_current()) {
  http_response_code(403);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok'=>false,'error'=>'Admin login required']);
  exit;
}

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
  $dir = __DIR__ . '/../public/uploads/';
  if (!is_dir($dir)) {
    if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
      throw new RuntimeException('Cannot create uploads directory');
    }
  }
  return realpath($dir) ?: $dir;
}

try {
  if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') fail('Method not allowed', 405);

  $title        = trim((string)($_POST['title'] ?? ''));
  $short_text   = trim((string)($_POST['short_text'] ?? ''));
  $description  = trim((string)($_POST['description'] ?? ''));
  $content_html = (string)($_POST['content_html'] ?? '');

  $hero_video_url = trim((string)($_POST['hero_video_url'] ?? ''));
  $slider_style   = strtolower(trim((string)($_POST['slider_style'] ?? 'cards')));

  $lat  = (string)($_POST['lat'] ?? '');
  $lng  = (string)($_POST['lng'] ?? '');
  $type = strtolower(trim((string)($_POST['type'] ?? 'location')));

  // NEW: Google Places
  $place_id = trim((string)($_POST['place_id'] ?? ''));
  $use_google_photos = (int)($_POST['use_google_photos'] ?? 0) ? 1 : 0;

  $allowedTypes  = ['location','hotel','petrol','restaurant','cafe'];
  $allowedStyles = ['cards','strip'];

  if (!in_array($type, $allowedTypes, true)) $type = 'location';
  if (!in_array($slider_style, $allowedStyles, true)) $slider_style = 'cards';

  if ($title === '') fail('Title required');
  if (!is_numeric($lat) || !is_numeric($lng)) fail('Invalid lat/lng');

  if (strlen($title) > 150) $title = substr($title, 0, 150);
  if (strlen($short_text) > 255) $short_text = substr($short_text, 0, 255);

  if ($place_id !== '' && strlen($place_id) > 120) $place_id = substr($place_id, 0, 120);
  if ($place_id === '') $use_google_photos = 0; // cannot use if no place_id

  db()->beginTransaction();

  $st = db()->prepare("
    INSERT INTO markers
      (title, short_text, description, content_html, hero_video_url, slider_style, lat, lng, type, place_id, use_google_photos)
    VALUES
      (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  ");
  $st->execute([
    $title, $short_text, $description, $content_html,
    $hero_video_url, $slider_style,
    $lat, $lng, $type,
    $place_id ?: null,
    $use_google_photos
  ]);

  $marker_id = (int)db()->lastInsertId();

  // Upload custom images
  $uploadDir = ensure_upload_dir();
  if (!empty($_FILES['images']['name'][0])) {
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $count = count($_FILES['images']['name']);

    for ($i = 0; $i < $count; $i++) {
      if (($_FILES['images']['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;

      $tmp  = $_FILES['images']['tmp_name'][$i] ?? '';
      $size = (int)($_FILES['images']['size'][$i] ?? 0);

      if (!$tmp || !is_file($tmp)) continue;
      if ($size > 5 * 1024 * 1024) continue; // 5MB

      $mime = mime_content_type($tmp) ?: '';
      if (!isset($allowed[$mime])) continue;
      if (!@getimagesize($tmp)) continue;

      $ext  = $allowed[$mime];
      $name = 'm' . $marker_id . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
      $dest = $uploadDir . DIRECTORY_SEPARATOR . $name;

      if (move_uploaded_file($tmp, $dest)) {
        $path = '/public/uploads/' . $name;
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