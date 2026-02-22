

$dest = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $filename;

function handle_upload(string $field): ?string {
  if (empty($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;

  $tmp = $_FILES[$field]['tmp_name'];
  $name = $_FILES[$field]['name'] ?? 'file';
  $size = (int)($_FILES[$field]['size'] ?? 0);

  if ($size > 5 * 1024 * 1024) { // 5MB
    throw new RuntimeException('File too large (max 5MB).');
  }

  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mime = finfo_file($finfo, $tmp);
  finfo_close($finfo);

  $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
  if (!isset($allowed[$mime])) {
    throw new RuntimeException('Only JPG/PNG/WEBP images allowed.');
  }

  $ext = $allowed[$mime];
  $safe = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($name, PATHINFO_FILENAME));
  if ($safe === '') $safe = 'img';
  $filename = $safe . '_' . bin2hex(random_bytes(6)) . '.' . $ext;

  $uploadDir = ensure_upload_dir();
  $dest = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $filename;

  if (!move_uploaded_file($tmp, $dest)) {
    throw new RuntimeException('Upload failed.');
  }

  return 'uploads/' . $filename;
}
