<?php
require_once __DIR__ . '/../app/helpers.php';
header('Content-Type: application/json; charset=utf-8');
$dest = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $filename;

$settings = [
  'google_api_key' => get_setting('google_api_key','asssss'),
  'default_lat' => (float)get_setting('default_lat','0'),
  'default_lng' => (float)get_setting('default_lng','0'),
  'default_zoom' => (int)get_setting('default_zoom','11'),
  'always_on_type' => get_setting('always_on_type','Location'),
];

$types = db()->query("SELECT id,name,icon_url,is_default,is_active FROM marker_types ORDER BY sort_order,name")->fetchAll();

$locs = db()->query("
  SELECT l.id, l.title, l.short_text, l.description, l.address, l.lat, l.lng, l.image_path, l.is_active,
         t.name AS type_name, t.icon_url AS type_icon, t.is_default, t.is_active AS type_active
  FROM locations l
  JOIN marker_types t ON t.id=l.type_id
  WHERE l.is_active=1 AND t.is_active=1
  ORDER BY l.id DESC
")->fetchAll();

echo json_encode(['settings'=>$settings,'types'=>$types,'locations'=>$locs], JSON_UNESCAPED_SLASHES);
