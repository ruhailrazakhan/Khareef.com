<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
// config.php is optional for GOOGLE_API_KEY; ignore if missing
if (is_file(__DIR__ . '/config.php')) require_once __DIR__ . '/config.php';

require_admin();

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(404); echo "Missing id"; exit; }

$pdo = db();

$st = $pdo->prepare("SELECT * FROM markers WHERE id=?");
$st->execute([$id]);
$m = $st->fetch(PDO::FETCH_ASSOC);
if (!$m) { http_response_code(404); echo "Not found"; exit; }

$st2 = $pdo->prepare("SELECT id, path, sort_order FROM marker_images WHERE marker_id=? ORDER BY sort_order ASC, id ASC");
$st2->execute([$id]);
$imgs = $st2->fetchAll(PDO::FETCH_ASSOC);

$apiKey = defined('GOOGLE_API_KEY') ? (string)GOOGLE_API_KEY : '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Edit Marker #<?= (int)$id ?></title>

  <style>
    :root{
      --bg:#f2f4f7;--panel:#fff;--text:#0b1320;--muted:#5b6573;
      --line:#e6ebf0;--shadow:0 6px 22px rgba(16,24,40,.08);
      --shadow2:0 2px 10px rgba(16,24,40,.06);--r:16px;
      --b:#003580;--b2:#0057d9;
    }
    *{box-sizing:border-box}
    body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial;background:var(--bg);color:var(--text)}
    a{color:var(--b2);text-decoration:none}
    .topbar{
      position:sticky;top:0;z-index:10;
      background:rgba(242,244,247,.9);backdrop-filter: blur(8px);
      border-bottom:1px solid var(--line);
    }
    .topbar .in{
      max-width:1200px;margin:0 auto;padding:12px 16px;
      display:flex;align-items:center;justify-content:space-between;gap:10px;
    }
    .title{font-weight:900}
    .pill{
      padding:8px 12px;border:1px solid var(--line);border-radius:999px;
      background:#fff;box-shadow:var(--shadow2);font-weight:800;font-size:13px;
    }
    .wrap{max-width:1200px;margin:14px auto;padding:0 16px 24px}
    .grid{display:grid;grid-template-columns: 420px 1fr;gap:14px}
    @media(max-width:980px){ .grid{grid-template-columns: 1fr} }

    .card{
      background:var(--panel);border:1px solid var(--line);border-radius:var(--r);
      box-shadow:var(--shadow);overflow:hidden;
    }
    .card .hd{padding:14px 14px 10px;border-bottom:1px solid var(--line);font-weight:900}
    .card .bd{padding:14px}
    label{display:block;font-weight:800;font-size:13px;margin:10px 0 6px}
    input[type="text"], input[type="url"], textarea, select{
      width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:14px;
      outline:none;background:#fff;font-size:14px
    }
    textarea{min-height:110px;resize:vertical}
    .row2{display:grid;grid-template-columns:1fr 1fr;gap:10px}
    .btns{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px}
    button{
      border:0;border-radius:14px;padding:10px 12px;font-weight:900;cursor:pointer;
      box-shadow:var(--shadow2);
    }
    .btn-primary{background:var(--b2);color:#fff}
    .btn-ghost{background:#fff;color:var(--text);border:1px solid var(--line)}
    .status{margin-top:10px;font-weight:800;font-size:13px}
    .status.ok{color:#0a7a2f}
    .status.err{color:#b42318}

    /* Images */
    .img-list{display:flex;flex-direction:column;gap:10px;margin-top:8px}
    .img-row{
      display:flex;align-items:center;gap:12px;
      border:1px solid var(--line);border-radius:14px;padding:10px;background:#fff;
    }
    .drag-handle{
      cursor:grab; user-select:none;font-weight:900;font-size:18px;line-height:1;
      padding:6px 8px;border-radius:10px;border:1px solid #eef2f6;
    }
    .thumb{width:92px;height:60px;object-fit:cover;border-radius:12px;border:1px solid var(--line)}
    .delbox{margin-left:auto;font-weight:900;display:flex;align-items:center;gap:8px}
    .img-row.dragging{opacity:.55}

    /* Map */
    #map{width:100%;height:calc(100vh - 140px);min-height:480px}
    @media(max-width:980px){ #map{height:420px} }
    .hint{font-size:12px;color:var(--muted);margin-top:6px}
  </style>
</head>
<body>

<div class="topbar">
  <div class="in">
    <div class="title">Edit Marker #<?= (int)$id ?></div>
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
      <a class="pill" href="index.php">← Back to map</a>
      <span class="pill">Admin mode: ON</span>
    </div>
  </div>
</div>

<div class="wrap">
  <div class="grid">

    <!-- LEFT: FORM -->
    <div class="card">
      <div class="hd">Marker details</div>
      <div class="bd">

        <form id="editForm">
          <input type="hidden" name="id" value="<?= (int)$id ?>">

          <label>Title</label>
          <input type="text" name="title" value="<?= e((string)($m['title'] ?? '')) ?>" required>

          <label>Short text (cards)</label>
          <input type="text" name="short_text" value="<?= e((string)($m['short_text'] ?? '')) ?>">

          <label>Description (optional)</label>
          <textarea name="description"><?= e((string)($m['description'] ?? '')) ?></textarea>

          <label>Full content (HTML allowed)</label>
          <textarea name="content_html" style="min-height:160px"><?= e((string)($m['content_html'] ?? '')) ?></textarea>

          <label>Hero video URL (optional)</label>
          <input type="url" name="hero_video_url" value="<?= e((string)($m['hero_video_url'] ?? '')) ?>" placeholder="https://...">

          <div class="row2">
            <div>
              <label>Type</label>
              <?php $type = strtolower((string)($m['type'] ?? 'location')); ?>
              <select name="type">
                <?php foreach (['location','hotel','petrol','restaurant','cafe'] as $t): ?>
                  <option value="<?= e($t) ?>" <?= $type===$t?'selected':'' ?>><?= e(ucfirst($t)) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label>Slider style</label>
              <?php $ss = strtolower((string)($m['slider_style'] ?? 'cards')); ?>
              <select name="slider_style">
                <?php foreach (['cards'=>'Cards','strip'=>'Strip'] as $k=>$v): ?>
                  <option value="<?= e($k) ?>" <?= $ss===$k?'selected':'' ?>><?= e($v) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="row2">
            <div>
              <label>Latitude</label>
              <input type="text" id="lat" name="lat" value="<?= e((string)($m['lat'] ?? '')) ?>" required>
            </div>
            <div>
              <label>Longitude</label>
              <input type="text" id="lng" name="lng" value="<?= e((string)($m['lng'] ?? '')) ?>" required>
            </div>
          </div>
          <div class="hint">Tip: drag the pin on the map to update Lat/Lng.</div>

          <hr style="border:0;border-top:1px solid var(--line);margin:14px 0;">

          <div style="font-weight:900;margin-bottom:6px;">Images</div>

          <label>Add new images</label>
          <!-- IMPORTANT: name="images[]" so FormData(form) includes files automatically -->
          <input type="file" id="images" name="images[]" multiple accept="image/*">
          <div class="hint">Select multiple images. (Avoid very large files.)</div>

          <div style="margin-top:12px;font-weight:900;">Existing images (drag to reorder • tick to delete)</div>

          <div id="imgList" class="img-list">
            <?php foreach($imgs as $img): ?>
              <div class="img-row" draggable="true" data-img-id="<?= (int)$img['id'] ?>">
                <div class="drag-handle" title="Drag">⋮⋮</div>
                <img class="thumb" src="<?= e((string)$img['path']) ?>" alt="">
                <label class="delbox">
                  <input type="checkbox" class="delCheck" value="<?= (int)$img['id'] ?>">
                  Delete
                </label>
              </div>
            <?php endforeach; ?>
            <?php if (count($imgs) === 0): ?>
              <div class="hint">No images yet. Add new images above.</div>
            <?php endif; ?>
          </div>

          <div class="btns">
            <button type="submit" id="saveBtn" class="btn-primary">Save Changes</button>
            <a class="btn-ghost" style="display:inline-flex;align-items:center;justify-content:center;padding:10px 12px;border-radius:14px;border:1px solid var(--line);background:#fff;font-weight:900"
               href="index.php">Cancel</a>
          </div>

          <div id="status" class="status"></div>
        </form>

      </div>
    </div>

    <!-- RIGHT: MAP -->
    <div class="card">
      <div class="hd">Location</div>
      <div class="bd" style="padding:0">
        <div id="map"></div>
      </div>
    </div>

  </div>
</div>

<script>
  // ---------- Drag reorder UI (NO autosave) ----------
  const list = document.getElementById('imgList');
  let dragging = null;

  list?.addEventListener('dragstart', (e) => {
    const row = e.target.closest('.img-row');
    if (!row) return;
    dragging = row;
    row.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
  });

  list?.addEventListener('dragend', () => {
    if (dragging) dragging.classList.remove('dragging');
    dragging = null;
  });

  list?.addEventListener('dragover', (e) => {
    e.preventDefault();
    if (!dragging) return;
    const after = getDragAfterElement(list, e.clientY);
    if (after == null) list.appendChild(dragging);
    else list.insertBefore(dragging, after);
  });

  function getDragAfterElement(container, y) {
    const els = [...container.querySelectorAll('.img-row:not(.dragging)')];
    let closest = { offset: Number.NEGATIVE_INFINITY, element: null };
    for (const el of els) {
      const box = el.getBoundingClientRect();
      const offset = y - (box.top + box.height / 2);
      if (offset < 0 && offset > closest.offset) closest = { offset, element: el };
    }
    return closest.element;
  }

  // ---------- Submit to update_marker.php ----------
  const form = document.getElementById('editForm');
  const btn = document.getElementById('saveBtn');
  const statusEl = document.getElementById('status');

  function showStatus(msg, isErr=false){
    statusEl.textContent = msg;
    statusEl.className = 'status ' + (isErr ? 'err' : 'ok');
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    btn.disabled = true;

    // IMPORTANT: this already includes images[] because input has name="images[]"
    const fd = new FormData(form);

    // delete selected existing images
    const checked = [...document.querySelectorAll('.delCheck:checked')];
    checked.forEach(ch => fd.append('delete_images[]', ch.value));

    // order of remaining images
    const deletedSet = new Set(checked.map(x => x.value));
    document.querySelectorAll('#imgList .img-row').forEach(row => {
      const imgId = row.dataset.imgId;
      if (!deletedSet.has(imgId)) fd.append('image_order[]', imgId);
    });

    showStatus('Updating...');

    let text = '';
    try{
      const res = await fetch('update_marker.php', { method:'POST', body: fd });
      text = await res.text();

      let data;
      try { data = JSON.parse(text); }
      catch(err){
        console.log('Non-JSON response from update_marker.php:\n', text);
        btn.disabled = false;
        return showStatus('Server returned non-JSON. Check console/network.', true);
      }

      if (!data.ok){
        btn.disabled = false;
        return showStatus(data.error || 'Update failed', true);
      }

      showStatus('Updated ✅');
      btn.disabled = false;
      setTimeout(() => location.reload(), 350);

    }catch(err){
      console.log(err);
      console.log('Response text (if any):', text);
      btn.disabled = false;
      showStatus('Request failed. Check console.', true);
    }
  });

  // ---------- Google Map (optional) ----------
  const startLat = parseFloat(document.getElementById('lat').value) || 17.019;
  const startLng = parseFloat(document.getElementById('lng').value) || 54.089;
  let map, pin;

  window.initMap = function(){
    map = new google.maps.Map(document.getElementById('map'), {
      center: {lat:startLat, lng:startLng},
      zoom: 12,
      mapTypeControl: false
    });

    pin = new google.maps.Marker({
      position: {lat:startLat, lng:startLng},
      map,
      draggable: true
    });

    pin.addListener('dragend', () => {
      const p = pin.getPosition();
      document.getElementById('lat').value = p.lat().toFixed(6);
      document.getElementById('lng').value = p.lng().toFixed(6);
    });

    map.addListener('click', (e) => {
      pin.setPosition(e.latLng);
      document.getElementById('lat').value = e.latLng.lat().toFixed(6);
      document.getElementById('lng').value = e.latLng.lng().toFixed(6);
    });
  };

  <?php if ($apiKey !== ''): ?>
  (function(){
    const s = document.createElement('script');
    s.src = "https://maps.googleapis.com/maps/api/js?key=<?= e($apiKey) ?>&callback=initMap";
    s.async = true;
    document.head.appendChild(s);
  })();
  <?php else: ?>
  document.getElementById('map').innerHTML =
    '<div style="padding:16px;color:#5b6573">GOOGLE_API_KEY not set in config.php (map disabled).</div>';
  <?php endif; ?>
</script>

</body>
</html>
