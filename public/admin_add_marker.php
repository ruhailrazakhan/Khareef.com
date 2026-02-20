<?php
require_once __DIR__ . '/config.php'; // must define GOOGLE_API_KEY
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Add Marker</title>

  <style>
    :root{
      --bg:#f2f4f7;
      --panel:#fff;
      --text:#0b1320;
      --muted:#5b6573;
      --line:#e6ebf0;
      --shadow:0 6px 22px rgba(16,24,40,.08);
      --shadow2:0 2px 10px rgba(16,24,40,.06);
      --r:16px;
      --b:#003580;
      --b2:#0057d9;
    }
    *{box-sizing:border-box}
    html,body{height:100%}
    body{margin:0;font-family:ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;background:var(--bg);color:var(--text)}
    .wrap{display:grid;grid-template-columns:440px 1fr;height:100vh;min-height:0}

    /* Left panel */
    .panel{overflow:auto;border-right:1px solid var(--line);background:var(--bg);padding:14px;min-height:0}
    .card{background:var(--panel);border:1px solid var(--line);border-radius:18px;box-shadow:var(--shadow2);padding:14px;margin-bottom:12px}
    .title{display:flex;align-items:center;justify-content:space-between;gap:10px}
    .title h2{margin:0;font-size:18px}
    .hint{margin:6px 0 0;color:var(--muted);font-size:12px;line-height:1.4}

    label{display:block;font-size:12px;color:#2a3442;font-weight:900;margin:10px 0 6px}
    input,textarea,select{
      width:100%;
      padding:11px 12px;
      border:1px solid var(--line);
      border-radius:14px;
      background:#fff;
      outline:none;
      font-size:14px;
    }
    textarea{min-height:120px;resize:vertical}
    input:focus,textarea:focus,select:focus{
      border-color:rgba(0,87,217,.55);
      box-shadow:0 0 0 4px rgba(0,87,217,.12);
    }

    .row{display:flex;gap:10px}
    .row .col{flex:1}

    .btn{
      width:100%;
      background:var(--b);
      color:#fff;
      border:none;
      border-radius:14px;
      padding:12px 14px;
      font-weight:1000;
      cursor:pointer;
      box-shadow:0 8px 18px rgba(0,53,128,.16);
    }
    .btn:active{transform:translateY(1px)}
    .btn:disabled{opacity:.6;cursor:not-allowed}

    .status{margin-top:10px;font-size:13px}
    .ok{color:#0a7a2f}
    .err{color:#b00020}

    /* Image previews */
    .thumbs{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}
    .thumbs img{
      width:92px;height:72px;
      object-fit:cover;
      border-radius:12px;
      border:1px solid var(--line);
      background:#e9eef5;
    }

    /* Map */
    .mapWrap{position:relative;background:#fff;min-height:0}
    #map{width:100%;height:100%}
    .mapTop{
      position:absolute;left:12px;top:12px;z-index:10;
      width:min(520px, calc(100% - 24px));
      background:#fff;
      border:1px solid var(--line);
      border-radius:14px;
      box-shadow:var(--shadow);
      padding:10px 12px;
      display:flex;align-items:center;gap:10px;
    }
    .mapTop .dot{width:10px;height:10px;border-radius:4px;background:var(--b)}
    .mapTop b{font-size:13px}
    .mapTop span{font-size:12px;color:var(--muted)}

    @media(max-width:980px){
      .wrap{grid-template-columns:1fr}
      .mapWrap{height:420px;order:-1}
    }
  </style>
  
    <!--- Menu CSS----->
  <style>
  /* ===== Responsive Menu ===== */

.siteHeader{
  position:sticky;
  top:0;
  z-index:999;
  background:#fff;
  border-bottom:1px solid #e6ebf0;
}

.navContainer{
  display:flex;
  align-items:center;
  justify-content:space-between;
  padding:0 18px;
  height:60px;
}

.logo{
  font-weight:1000;
  font-size:18px;
  color:#003580;
  text-decoration:none;
}

.navLinks{
  display:flex;
  gap:10px;
}

.navLinks a{
  text-decoration:none;
  color:#1b2b3d;
  font-weight:800;
  font-size:14px;
  padding:10px 14px;
  border-radius:999px;
  transition:all .2s ease;
}

.navLinks a:hover{
  background:#f3f6fb;
}

.navLinks a.active{
  background:#eef3ff;
  color:#003580;
}

.menuToggle{
  display:none;
  border:1px solid #e6ebf0;
  background:#fff;
  border-radius:10px;
  padding:6px 10px;
  font-size:18px;
  cursor:pointer;
}

.mobileMenu{
  display:none;
  flex-direction:column;
  border-top:1px solid #e6ebf0;
  background:#fff;
}

.mobileMenu a{
  padding:14px 18px;
  text-decoration:none;
  font-weight:800;
  color:#1b2b3d;
  border-bottom:1px solid #f0f0f0;
}

.mobileMenu a:hover{
  background:#f3f6fb;
}

/* Mobile */
@media(max-width:900px){
  .navLinks{display:none}
  .menuToggle{display:block}
}
</style>
  <link rel="stylesheet" href="header.css">
</head>

<body>
    <?php
$current = basename($_SERVER['PHP_SELF']);
?>
<?php include __DIR__ . '/header.php'; ?>
<header class="siteHeader">
  <div class="navContainer">

    <a href="index.php" class="logo">
      TravelGuide
    </a>

    <nav class="navLinks">
      <a href="index.php" class="<?= $current=='index.php'?'active':'' ?>">Home</a>
      <a href="admin_add_marker.php" class="<?= $current=='admin_add_marker.php'?'active':'' ?>">Add Marker</a>
      <!----<a href="contact.php" class="<?= $current=='contact.php'?'active':'' ?>">Contact</a>-------------->
    </nav>

    <button class="menuToggle" id="menuToggle">☰</button>

  </div>

  <div class="mobileMenu" id="mobileMenu">
    <a href="index.php">Home</a>
    <a href="admin_add_marker.php">Add Marker</a>
    <!--------<a href="contact.php">Contact</a>---------->
  </div>
</header>
<div class="wrap">
  <!-- Left panel -->
  <aside class="panel">
    <div class="card">
      <div class="title">
        <h2>Add Marker</h2>
        <span style="font-size:12px;color:var(--muted)">Admin</span>
      </div>
      <p class="hint">Search a place or click the map to drop a pin. Drag the marker to adjust position, then save.</p>

      <label>Type</label>
      <select id="type">
        <option value="location">Location</option>
        <option value="hotel">Hotel</option>
        <option value="petrol">Petrol Station</option>
        <option value="restaurant">Restaurant</option>
        <option value="cafe">Cafe</option>
      </select>

      <label>Search place (Google)</label>
      <input id="placeSearch" placeholder="Type a place name..." autocomplete="off">

      <div class="row">
        <div class="col">
          <label>Latitude</label>
          <input id="lat" readonly>
        </div>
        <div class="col">
          <label>Longitude</label>
          <input id="lng" readonly>
        </div>
      </div>

      <label>Title</label>
      <input id="title" placeholder="Marker title (required)">

      <label>Tile text (for cards)</label>
      <input id="short_text" maxlength="255" placeholder="Short text shown in tiles/cards">

      <label>Description</label>
      <textarea id="description" placeholder="Full description..."></textarea>
        <label>Hero Video URL (mp4)</label>
        <input id="hero_video_url" placeholder="https://.../video.mp4 OR uploads/hero/video.mp4">
        
        <label>Slider style</label>
        <select id="slider_style">
          <option value="cards">Cards (hotel carousel)</option>
          <option value="strip">Strip (image strip)</option>
        </select>
        
        <label>Page Content (HTML allowed)</label>
        <textarea id="content_html" placeholder="Write HTML here (headings, paragraphs, lists)..."></textarea>
      <label>Images</label>
      <input id="images" type="file" multiple accept="image/png,image/jpeg,image/webp">
      <div class="thumbs" id="thumbs"></div>

      <div style="margin-top:12px">
        <button class="btn" id="saveBtn">Save to DB</button>
      </div>

      <div id="status" class="status"></div>
    </div>

    <div class="card">
      <b style="font-size:13px">Tips</b>
      <ul style="margin:10px 0 0;padding-left:18px;color:var(--muted);font-size:12px;line-height:1.6">
        <li>Click map = set pin</li>
        <li>Drag marker = update lat/lng</li>
        <li>If save fails: open DevTools Console to see server response</li>
      </ul>
    </div>
  </aside>

  <!-- Right map -->
  <main class="mapWrap">
    <div class="mapTop">
      <div class="dot"></div>
      <div>
        <b>Pick point</b><br>
        <span>Click map or use search</span>
      </div>
    </div>
    <div id="map"></div>
  </main>
</div>

<script>
let map, marker, autocomplete;

function setLatLng(lat, lng){
  document.getElementById('lat').value = Number(lat).toFixed(7);
  document.getElementById('lng').value = Number(lng).toFixed(7);
}

function showStatus(text, isError=false){
  const el = document.getElementById('status');
  el.className = 'status ' + (isError ? 'err' : 'ok');
  el.textContent = text;
}

function previewFiles(files){
  const thumbs = document.getElementById('thumbs');
  thumbs.innerHTML = '';
  [...files].forEach(f=>{
    const url = URL.createObjectURL(f);
    const img = document.createElement('img');
    img.src = url;
    thumbs.appendChild(img);
  });
}

document.getElementById('images').addEventListener('change', (e)=>{
  previewFiles(e.target.files);
});

async function saveMarker(){
  const btn = document.getElementById('saveBtn');
  btn.disabled = true;

  const type = document.getElementById('type').value;
  const lat = document.getElementById('lat').value;
  const lng = document.getElementById('lng').value;
  const title = document.getElementById('title').value.trim();
  const short_text = document.getElementById('short_text').value.trim();
  const description = document.getElementById('description').value.trim();
  const hero_video_url = document.getElementById('hero_video_url').value.trim();
  const slider_style = document.getElementById('slider_style').value;
  const content_html = document.getElementById('content_html').value;

  if(!lat || !lng){
    btn.disabled = false;
    return showStatus("Pick a point on map or search a place.", true);
  }
  if(!title){
    btn.disabled = false;
    return showStatus("Title is required.", true);
  }

  const fd = new FormData();
  fd.append('type', type);
  fd.append('title', title);
  fd.append('short_text', short_text);
  fd.append('description', description);
  fd.append('lat', lat);
  fd.append('lng', lng);
  fd.append('hero_video_url', hero_video_url);
  fd.append('slider_style', slider_style);
  fd.append('content_html', content_html);
  const files = document.getElementById('images').files;
  for(const f of files) fd.append('images[]', f);

  showStatus("Saving...");

const res = await fetch('save_marker.php', { method:'POST', body: fd });

const text = await res.text();

console.log("=== RAW SERVER RESPONSE ===");
console.log(text);
console.log("=== END RESPONSE ===");

let data;
try {
  data = JSON.parse(text);
} catch (e) {
  btn.disabled = false;
  return showStatus("Server returned non-JSON. Check console.", true);
}

  if(!data.ok){
    btn.disabled = false;
    return showStatus(data.error || "Save failed.", true);
  }

  showStatus("Saved ✅ ID: " + data.id);
  btn.disabled = false;
}

document.getElementById('saveBtn').addEventListener('click', saveMarker);

window.initMap = function(){
  const start = {lat: 23.5880, lng: 58.3829};

  map = new google.maps.Map(document.getElementById('map'), {
    center: start,
    zoom: 11,
    mapTypeControl:false,
    streetViewControl:false,
    fullscreenControl:true
  });

  marker = new google.maps.Marker({
    position: start,
    map,
    draggable: true
  });

  setLatLng(start.lat, start.lng);

  map.addListener('click', (e)=>{
    marker.setPosition(e.latLng);
    setLatLng(e.latLng.lat(), e.latLng.lng());
  });

  marker.addListener('dragend', (e)=>{
    setLatLng(e.latLng.lat(), e.latLng.lng());
  });

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

    if(!document.getElementById('title').value) {
      document.getElementById('title').value = place.name || '';
    }
  });
};
</script>
<!---Menu JS----->
<script>
document.getElementById('menuToggle')?.addEventListener('click', function(){
  const menu = document.getElementById('mobileMenu');
  menu.style.display = menu.style.display === 'flex' ? 'none' : 'flex';
});
</script>
<script async
  src="https://maps.googleapis.com/maps/api/js?key=<?= htmlspecialchars(GOOGLE_API_KEY) ?>&libraries=places&callback=initMap">
</script>
<script src="deader.js"></script>
</body>
</html>
