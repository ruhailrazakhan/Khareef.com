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
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Add Marker</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    :root{
      --brand:#0A4AA6;
      --bg:#f5f7fb;
      --line:#e7edf5;
      --shadow:0 10px 26px rgba(16,24,40,.10);
      --r:16px;
    }
    body{ background:var(--bg); }
    .brand-bg{ background:var(--brand); }
    .card-soft{
      border:1px solid var(--line);
      border-radius:var(--r);
      box-shadow:0 6px 18px rgba(16,24,40,.08);
    }
    .form-control,.form-select,.btn{ border-radius:14px; }
    #map{
      width:100%;
      height: calc(100vh - 64px);
      min-height: 420px;
      border-radius:var(--r);
      border:1px solid var(--line);
      box-shadow:var(--shadow);
      overflow:hidden;
    }
    @media (max-width: 991px){ #map{ height: 420px; } }
    .thumb{ width:86px;height:68px;object-fit:cover;border-radius:12px;border:1px solid var(--line);background:#e9eef5; }
    .hint{ color:#64748b; font-size:12px; }
    .map-top{
      position:absolute; left:14px; top:14px; z-index:5;
      width:min(560px, calc(100% - 28px));
      background:#fff; border:1px solid var(--line);
      border-radius:14px; box-shadow:var(--shadow);
      padding:10px 12px;
      display:flex; align-items:center; gap:10px;
    }
    .map-top input{ border:none; outline:none; width:100%; }
  </style>
</head>

<body>
    
    
<nav class="navbar navbar-expand-lg navbar-dark brand-bg sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="./index.php">Khareef</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navTop">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navTop">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link active" href="./admin_add_marker.php">Add Marker</a></li>
      </ul>
       <a class="btn btn-sm btn-outline-light" href="/../public/index.php">Back to Website</a>
      <a class="btn btn-sm btn-outline-light" href="/admin/logout.php">Logout</a>
    </div>
  </div>
</nav>

<div class="container-fluid py-3">
  <div class="row g-3">

    <!-- FORM -->
    <div class="col-12 col-lg-5 col-xl-4">
      <div class="card card-soft">
        <div class="card-body">
          <h5 class="fw-bold mb-1">Add Marker</h5>
          <div class="hint mb-3">Click map or search a place. Drag marker to adjust. Then Save.</div>

          <div class="row g-2">
            <div class="col-12">
              <label class="form-label fw-bold">Type</label>
              <select id="type" class="form-select">
                <option value="location">Location</option>
                <option value="hotel">Hotel</option>
                <option value="petrol">Petrol Station</option>
                <option value="restaurant">Restaurant</option>
                <option value="cafe">Cafe</option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label fw-bold">Search place (Google)</label>
              <input id="placeSearch" class="form-control" placeholder="Type a place name…" autocomplete="off">
              <div class="hint mt-1">
                Tip: After selecting place, we auto-fill <b>Place ID</b> (used for Google photos).
              </div>
            </div>

            <!-- NEW -->
            <div class="col-12">
              <label class="form-label fw-bold">Place ID (optional)</label>
              <input id="place_id" class="form-control" placeholder="ChIJ... (auto filled from search)">
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="use_google_photos" value="1">
                <label class="form-check-label fw-bold" for="use_google_photos">
                  Use Google photos (do not store, load live)
                </label>
              </div>
              <div class="hint mt-1">
                If enabled, your marker popup/card gallery can show Google photos using Places API.
              </div>
            </div>

            <div class="col-6">
              <label class="form-label fw-bold">Latitude</label>
              <input id="lat" class="form-control" readonly>
            </div>
            <div class="col-6">
              <label class="form-label fw-bold">Longitude</label>
              <input id="lng" class="form-control" readonly>
            </div>

            <div class="col-12">
              <label class="form-label fw-bold">Title *</label>
              <input id="title" class="form-control" placeholder="Marker title (required)">
            </div>

            <div class="col-12">
              <label class="form-label fw-bold">Tile text</label>
              <input id="short_text" class="form-control" maxlength="255" placeholder="Short text shown on cards">
            </div>

            <div class="col-12">
              <label class="form-label fw-bold">Description</label>
              <textarea id="description" class="form-control" rows="4" placeholder="Full description…"></textarea>
            </div>

            <div class="col-12">
              <label class="form-label fw-bold">Hero Video URL (mp4)</label>
              <input id="hero_video_url" class="form-control" placeholder="https://…/video.mp4 (optional)">
            </div>

            <div class="col-12">
              <label class="form-label fw-bold">Slider style</label>
              <select id="slider_style" class="form-select">
                <option value="cards">Cards</option>
                <option value="strip">Strip</option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label fw-bold">Content HTML</label>
              <textarea id="content_html" class="form-control" rows="4" placeholder="<h2>…</h2> …"></textarea>
              <div class="hint mt-1">This will appear on the single marker page later.</div>
            </div>

            <div class="col-12">
              <label class="form-label fw-bold">Custom Images</label>
              <input id="images" class="form-control" type="file" multiple accept="image/png,image/jpeg,image/webp">
              <div class="d-flex flex-wrap gap-2 mt-2" id="thumbs"></div>
              <div class="hint mt-1">
                These images are stored on your server. Google photos are NOT stored.
              </div>
            </div>

            <div class="col-12 mt-2">
              <button class="btn btn-primary w-100 fw-bold" id="saveBtn">Save</button>
              <div id="status" class="mt-2 small"></div>
              <div class="hint mt-2">If save fails, open DevTools → Console to see full server response.</div>
            </div>

          </div>
        </div>
      </div>
    </div>

    <!-- MAP -->
    <div class="col-12 col-lg-7 col-xl-8">
      <div class="position-relative">
        <div class="map-top">
          <span style="opacity:.65">🔎</span>
          <input id="mapSearchMirror" placeholder="Search on map (same as left search)" />
        </div>
        <div id="map"></div>
      </div>
    </div>

  </div>
</div>

<script>
let map, marker, autocomplete;

function setLatLng(lat, lng){
  document.getElementById('lat').value = Number(lat).toFixed(7);
  document.getElementById('lng').value = Number(lng).toFixed(7);
}
function showStatus(msg, type='info'){
  const el = document.getElementById('status');
  el.className = (type==='err') ? 'text-danger fw-bold' : (type==='ok' ? 'text-success fw-bold' : 'text-secondary');
  el.textContent = msg;
}
function previewFiles(files){
  const thumbs = document.getElementById('thumbs');
  thumbs.innerHTML = '';
  [...files].forEach(f=>{
    const url = URL.createObjectURL(f);
    const img = document.createElement('img');
    img.className = 'thumb';
    img.src = url;
    thumbs.appendChild(img);
  });
}

document.getElementById('images').addEventListener('change', e => previewFiles(e.target.files));
document.getElementById('mapSearchMirror').addEventListener('input', e => { document.getElementById('placeSearch').value = e.target.value; });

async function saveMarker(){
  const btn = document.getElementById('saveBtn');
  btn.disabled = true;

  const lat = document.getElementById('lat').value;
  const lng = document.getElementById('lng').value;
  const title = document.getElementById('title').value.trim();

  if(!lat || !lng){ btn.disabled=false; return showStatus("Pick a point on map or search a place.", 'err'); }
  if(!title){ btn.disabled=false; return showStatus("Title is required.", 'err'); }

  const fd = new FormData();
  fd.append('type', document.getElementById('type').value);
  fd.append('title', title);
  fd.append('short_text', document.getElementById('short_text').value.trim());
  fd.append('description', document.getElementById('description').value.trim());
  fd.append('content_html', document.getElementById('content_html').value);
  fd.append('hero_video_url', document.getElementById('hero_video_url').value.trim());
  fd.append('slider_style', document.getElementById('slider_style').value);
  fd.append('lat', lat);
  fd.append('lng', lng);

  // NEW
  fd.append('place_id', document.getElementById('place_id').value.trim());
  fd.append('use_google_photos', document.getElementById('use_google_photos').checked ? '1' : '0');

  const files = document.getElementById('images').files;
  for(const f of files) fd.append('images[]', f);

  showStatus("Saving…");

  const res = await fetch('./save_marker.php', { method:'POST', body: fd });
  const text = await res.text();
  console.log("Server response:", text);

  let data;
  try { data = JSON.parse(text); } catch { btn.disabled=false; return showStatus("Invalid JSON (see Console).", 'err'); }

  if(!data.ok){ btn.disabled=false; return showStatus(data.error || "Save failed.", 'err'); }

  showStatus("Saved ✅ ID: " + data.id, 'ok');
  btn.disabled = false;
}

document.getElementById('saveBtn').addEventListener('click', saveMarker);

window.initMap = function(){
  const start = {lat: 17.0197, lng: 54.0897}; // Salalah default
  map = new google.maps.Map(document.getElementById('map'), {
    center: start, zoom: 11, mapTypeControl:false, streetViewControl:false, fullscreenControl:true
  });

  marker = new google.maps.Marker({ position: start, map, draggable:true });
  setLatLng(start.lat, start.lng);

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

    if(!document.getElementById('title').value) document.getElementById('title').value = place.name || '';

    // NEW: auto fill place_id
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