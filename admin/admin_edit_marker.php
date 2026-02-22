<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../public/db.php';
require_once __DIR__ . '/../public/auth.php';
require_once __DIR__ . '/../public/config.php'; // must define GOOGLE_API_KEY

if (!admin_current()) {
  header("Location: /admin/login.php");
  exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); echo "Invalid id"; exit; }

$st = db()->prepare("SELECT * FROM markers WHERE id=?");
$st->execute([$id]);
$m = $st->fetch();
if (!$m) { http_response_code(404); echo "Marker not found"; exit; }

$imgs = db()->prepare("SELECT id, path, sort_order FROM marker_images WHERE marker_id=? ORDER BY sort_order ASC, id ASC");
$imgs->execute([$id]);
$images = $imgs->fetchAll();

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Edit Marker</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    :root{ --brand:#0A4AA6; --bg:#f5f7fb; --line:#e7edf5; --shadow:0 10px 26px rgba(16,24,40,.10); --r:16px; }
    body{ background:var(--bg); }
    .brand-bg{ background:var(--brand); }
    .card-soft{ border:1px solid var(--line); border-radius:var(--r); box-shadow:0 6px 18px rgba(16,24,40,.08); }
    .form-control,.form-select,.btn{ border-radius:14px; }
    #map{ width:100%; height:420px; border-radius:var(--r); border:1px solid var(--line); box-shadow:var(--shadow); overflow:hidden; }
    .thumb{ width:96px;height:76px;object-fit:cover;border-radius:12px;border:1px solid var(--line);background:#e9eef5; }
    .hint{ color:#64748b; font-size:12px; }
  </style>
</head>

<body>
<nav class="navbar navbar-expand-lg navbar-dark brand-bg sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="./index.php">Khareef</a>
    <div class="d-flex gap-2">
      <a class="btn btn-sm btn-outline-light" href="/admin/index.php">Admin</a>
      <a class="btn btn-sm btn-outline-light" href="/admin/logout.php">Logout</a>
    </div>
  </div>
</nav>

<div class="container py-3">
  <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-2">
    <h4 class="fw-bold m-0">Edit Marker</h4>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="./index.php">Back</a>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-12 col-lg-6">
      <div class="card card-soft">
        <div class="card-body">

          <div class="row g-2">
            <div class="col-12">
              <label class="form-label fw-bold">Type</label>
              <select id="type" class="form-select">
                <?php
                  $types = ['location'=>'Location','hotel'=>'Hotel','petrol'=>'Petrol','restaurant'=>'Restaurant','cafe'=>'Cafe'];
                  foreach($types as $k=>$label){
                    $sel = ($m['type'] === $k) ? 'selected' : '';
                    echo "<option value='".e($k)."' $sel>".e($label)."</option>";
                  }
                ?>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label fw-bold">Search place (Google)</label>
              <input id="placeSearch" class="form-control" placeholder="Type a place name…" autocomplete="off">
              <div class="hint mt-1">Selecting a place can update Place ID + Lat/Lng.</div>
            </div>

            <!-- NEW -->
            <div class="col-12">
              <label class="form-label fw-bold">Place ID (optional)</label>
              <input id="place_id" class="form-control" value="<?= e($m['place_id'] ?? '') ?>" placeholder="ChIJ...">
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="use_google_photos" value="1" <?= ((int)($m['use_google_photos'] ?? 0) === 1) ? 'checked' : '' ?>>
                <label class="form-check-label fw-bold" for="use_google_photos">
                  Use Google photos (load live)
                </label>
              </div>
              <div class="hint mt-1">If Place ID is empty, Google photos cannot be used.</div>
            </div>

            <div class="col-6">
              <label class="form-label fw-bold">Latitude</label>
              <input id="lat" class="form-control" value="<?= e($m['lat']) ?>" readonly>
            </div>
            <div class="col-6">
              <label class="form-label fw-bold">Longitude</label>
              <input id="lng" class="form-control" value="<?= e($m['lng']) ?>" readonly>
            </div>

            <div class="col-12">
              <label class="form-label fw-bold">Title *</label>
              <input id="title" class="form-control" value="<?= e($m['title']) ?>">
            </div>

            <div class="col-12">
              <label class="form-label fw-bold">Tile text</label>
              <input id="short_text" class="form-control" maxlength="255" value="<?= e($m['short_text']) ?>">
            </div>

            <div class="col-12">
              <label class="form-label fw-bold">Description</label>
              <textarea id="description" class="form-control" rows="4"><?= e($m['description']) ?></textarea>
            </div>

            <div class="col-12">
              <label class="form-label fw-bold">Hero Video URL</label>
              <input id="hero_video_url" class="form-control" value="<?= e($m['hero_video_url'] ?? '') ?>">
            </div>

            <div class="col-12">
              <label class="form-label fw-bold">Slider style</label>
              <select id="slider_style" class="form-select">
                <option value="cards" <?= (($m['slider_style'] ?? '')==='cards')?'selected':'' ?>>Cards</option>
                <option value="strip" <?= (($m['slider_style'] ?? '')==='strip')?'selected':'' ?>>Strip</option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label fw-bold">Content HTML</label>
              <textarea id="content_html" class="form-control" rows="4"><?= e($m['content_html'] ?? '') ?></textarea>
            </div>

            <div class="col-12">
              <label class="form-label fw-bold">Add new custom images</label>
              <input id="images" class="form-control" type="file" multiple accept="image/png,image/jpeg,image/webp">
              <div class="hint mt-1">Existing images are below. You can delete/reorder on manage page later.</div>
            </div>

            <div class="col-12 mt-2">
              <button class="btn btn-primary w-100 fw-bold" id="saveBtn">Update</button>
              <div id="status" class="mt-2 small"></div>
            </div>
          </div>

        </div>
      </div>

      <div class="card card-soft mt-3">
        <div class="card-body">
          <div class="fw-bold mb-2">Current images</div>
          <div class="d-flex flex-wrap gap-2">
            <?php foreach($images as $im): ?>
              <img class="thumb" src="../<?= e($im['path']) ?>" alt="">
            <?php endforeach; ?>
          </div>
        </div>
      </div>

    </div>

    <div class="col-12 col-lg-6">
      <div id="map"></div>
      <div class="hint mt-2">Click map to move marker. Drag marker to adjust.</div>
    </div>
  </div>
</div>

<script>
let map, marker, autocomplete;
const MARKER_ID = <?= (int)$id ?>;

function setLatLng(lat, lng){
  document.getElementById('lat').value = Number(lat).toFixed(7);
  document.getElementById('lng').value = Number(lng).toFixed(7);
}
function showStatus(msg, type='info'){
  const el = document.getElementById('status');
  el.className = (type==='err') ? 'text-danger fw-bold' : (type==='ok' ? 'text-success fw-bold' : 'text-secondary');
  el.textContent = msg;
}

async function updateMarker(){
  const btn = document.getElementById('saveBtn');
  btn.disabled = true;

  const fd = new FormData();
  fd.append('id', String(MARKER_ID));
  fd.append('type', document.getElementById('type').value);
  fd.append('title', document.getElementById('title').value.trim());
  fd.append('short_text', document.getElementById('short_text').value.trim());
  fd.append('description', document.getElementById('description').value.trim());
  fd.append('content_html', document.getElementById('content_html').value);
  fd.append('hero_video_url', document.getElementById('hero_video_url').value.trim());
  fd.append('slider_style', document.getElementById('slider_style').value);
  fd.append('lat', document.getElementById('lat').value);
  fd.append('lng', document.getElementById('lng').value);

  // NEW
  fd.append('place_id', document.getElementById('place_id').value.trim());
  fd.append('use_google_photos', document.getElementById('use_google_photos').checked ? '1' : '0');

  const files = document.getElementById('images').files;
  for(const f of files) fd.append('images[]', f);

  showStatus("Updating…");

  const res = await fetch('./update_marker.php', { method:'POST', body: fd });
  const text = await res.text();
  console.log("Update response:", text);

  let json;
  try { json = JSON.parse(text); } catch { btn.disabled=false; return showStatus("Invalid JSON (see Console).", 'err'); }

  if(!json.ok){ btn.disabled=false; return showStatus(json.error || "Update failed.", 'err'); }

  showStatus("Updated ✅", 'ok');
  btn.disabled = false;
}

document.getElementById('saveBtn').addEventListener('click', updateMarker);

window.initMap = function(){
  const start = {lat: Number(document.getElementById('lat').value), lng: Number(document.getElementById('lng').value)};
  map = new google.maps.Map(document.getElementById('map'), {
    center: start, zoom: 12, mapTypeControl:false, streetViewControl:false, fullscreenControl:true
  });

  marker = new google.maps.Marker({ position: start, map, draggable:true });

  map.addListener('click', (e)=>{
    marker.setPosition(e.latLng);
    setLatLng(e.latLng.lat(), e.latLng.lng());
  });

  marker.addListener('dragend', (e)=> setLatLng(e.latLng.lat(), e.latLng.lng()));

  const input = document.getElementById('placeSearch');
  autocomplete = new google.maps.places.Autocomplete(input);
  autocomplete.addListener('place_changed', ()=>{
    const place = autocomplete.getPlace();
    if(!place.geometry) return;

    const p = place.geometry.location;
    map.panTo(p);
    map.setZoom(14);
    marker.setPosition(p);
    setLatLng(p.lat(), p.lng());

    if (!document.getElementById('title').value.trim()) {
      document.getElementById('title').value = place.name || '';
    }

    // NEW: fill place_id
    if (place.place_id) {
      document.getElementById('place_id').value = place.place_id;
      document.getElementById('use_google_photos').checked = true;
    }
  });

  showStatus("Ready.", 'info');
};
</script>

<script async
  src="https://maps.googleapis.com/maps/api/js?key=<?= htmlspecialchars(GOOGLE_API_KEY) ?>&libraries=places&callback=initMap">
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>