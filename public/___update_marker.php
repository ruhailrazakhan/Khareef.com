<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

if (!admin_current()) {
  http_response_code(403);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok'=>false,'error'=>'Admin login required']);
  exit;
}

header('Content-Type: application/json; charset=utf-8');

function json_ok(array $data = []): void {
  echo json_encode(['ok' => true] + $data, JSON_UNESCAPED_SLASHES);
  exit;
}
function json_fail(string $msg, int $code = 400): void {
  http_response_code($code);
  echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_SLASHES);
  exit;
}
function ensure_upload_dir(): string {
  $dir = __DIR__ . '/uploads';
  if (!is_dir($dir)) mkdir($dir, 0755, true);
  return realpath($dir) ?: $dir;
}

try {
  if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') json_fail('Invalid request method', 405);

  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) json_fail('Invalid marker ID');

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

  if ($title === '') json_fail('Title required');
  if (!is_numeric($lat) || !is_numeric($lng)) json_fail('Invalid latitude/longitude');

  $allowedTypes  = ['location','hotel','petrol','restaurant','cafe'];
  $allowedStyles = ['cards','strip'];

  if (!in_array($type, $allowedTypes, true)) $type = 'location';
  if (!in_array($slider_style, $allowedStyles, true)) $slider_style = 'cards';

  if ($place_id !== '' && strlen($place_id) > 120) $place_id = substr($place_id, 0, 120);
  if ($place_id === '') $use_google_photos = 0;

  $pdo = db();
  $pdo->beginTransaction();

  // UPDATE MARKER (added place_id + use_google_photos)
  $stmt = $pdo->prepare("
    UPDATE markers
    SET title=?, short_text=?, description=?, content_html=?, hero_video_url=?, slider_style=?,
        lat=?, lng=?, type=?, place_id=?, use_google_photos=?
    WHERE id=?
  ");
  $stmt->execute([
    $title, $short_text, $description, $content_html,
    $hero_video_url, $slider_style,
    $lat, $lng, $type,
    $place_id ?: null,
    $use_google_photos,
    $id
  ]);

  // DELETE SELECTED IMAGES
  $deleteImages = $_POST['delete_images'] ?? [];
  if (is_array($deleteImages) && count($deleteImages) > 0) {
    $selectImg = $pdo->prepare("SELECT id, path FROM marker_images WHERE marker_id=? AND id=?");
    $deleteImg = $pdo->prepare("DELETE FROM marker_images WHERE marker_id=? AND id=?");
    foreach ($deleteImages as $rawId) {
      $imgId = (int)$rawId;
      if ($imgId <= 0) continue;

      $selectImg->execute([$id, $imgId]);
      $row = $selectImg->fetch();
      if (!$row) continue;

      $deleteImg->execute([$id, $imgId]);

      $filePath = __DIR__ . '/' . ltrim((string)$row['path'], '/');
      if (is_file($filePath)) @unlink($filePath);
    }
  }

  // REORDER EXISTING IMAGES
  $imageOrder = $_POST['image_order'] ?? [];
  if (is_array($imageOrder) && count($imageOrder) > 0) {
    $clean = [];
    foreach ($imageOrder as $raw) {
      $iid = (int)$raw;
      if ($iid > 0) $clean[] = $iid;
    }
    if (count($clean) > 0) {
      $in = implode(',', array_fill(0, count($clean), '?'));
      $check = $pdo->prepare("SELECT id FROM marker_images WHERE marker_id=? AND id IN ($in)");
      $check->execute(array_merge([$id], $clean));
      $validIds = $check->fetchAll(PDO::FETCH_COLUMN);
      $validSet = array_flip(array_map('intval', $validIds));

      $updateSort = $pdo->prepare("UPDATE marker_images SET sort_order=? WHERE marker_id=? AND id=?");
      $sort = 0;
      foreach ($clean as $imgId) {
        if (!isset($validSet[$imgId])) continue;
        $updateSort->execute([$sort, $id, $imgId]);
        $sort++;
      }
    }
  }

  // ADD NEW UPLOADED IMAGES
  if (!empty($_FILES['images']['name'][0])) {
    $uploadDir = ensure_upload_dir();
    $allowedMime = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];

    $count = count($_FILES['images']['name']);

    $maxSortStmt = $pdo->prepare("SELECT COALESCE(MAX(sort_order), -1) AS max_sort FROM marker_images WHERE marker_id=?");
    $maxSortStmt->execute([$id]);
    $maxSort = (int)($maxSortStmt->fetch()['max_sort'] ?? -1);
    $sort = $maxSort + 1;

    for ($i=0; $i<$count; $i++) {
      if (($_FILES['images']['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;

      $tmp  = $_FILES['images']['tmp_name'][$i] ?? '';
      $size = (int)($_FILES['images']['size'][$i] ?? 0);

      if (!$tmp || !is_file($tmp)) continue;
      if ($size > 5*1024*1024) continue;

      $mime = mime_content_type($tmp) ?: '';
      if (!isset($allowedMime[$mime])) continue;
      if (!@getimagesize($tmp)) continue;

      $ext = $allowedMime[$mime];
      $fileName = 'm'.$id.'_'.bin2hex(random_bytes(6)).'.'.$ext;
      $destPath = $uploadDir . '/' . $fileName;

      if (move_uploaded_file($tmp, $destPath)) {
        $relativePath = 'uploads/' . $fileName;
        $insert = $pdo->prepare("INSERT INTO marker_images (marker_id, path, sort_order) VALUES (?, ?, ?)");
        $insert->execute([$id, $relativePath, $sort]);
        $sort++;
      }
    }
  }

  $pdo->commit();
  json_ok(['id' => $id]);

} catch (Throwable $e) {
  if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
  error_log('[update_marker] '.$e->getMessage());
  json_fail('Server error: '.$e->getMessage(), 500);
}