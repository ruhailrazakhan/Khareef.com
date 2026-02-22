<?php
require_once __DIR__ . '/../app/auth.php'; require_admin();
require_once __DIR__ . '/../app/helpers.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$editing = $id > 0;

$types = db()->query("SELECT id,name,is_default,is_active FROM marker_types WHERE is_active=1 ORDER BY sort_order,name")->fetchAll();

$loc = [
  'type_id' => $types ? (int)$types[0]['id'] : 0,
  'title' => '',
  'short_text' => '',
  'description' => '',
  'address' => '',
  'lat' => get_setting('default_lat','23.5880'),
  'lng' => get_setting('default_lng','58.3829'),
  'image_path' => '',
  'is_active' => 1,
];

if ($editing) {
  $st = db()->prepare("SELECT * FROM locations WHERE id=?");
  $st->execute([$id]);
  $row = $st->fetch();
  if ($row) $loc = $row;
}

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  try {
    $type_id = (int)($_POST['type_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $short_text = trim($_POST['short_text'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $lat = trim($_POST['lat'] ?? '');
    $lng = trim($_POST['lng'] ?? '');
    $is_active = !empty($_POST['is_active']) ? 1 : 0;

    if ($title === '') throw new RuntimeException('Title is required.');
    if (!is_numeric($lat) || !is_numeric($lng)) throw new RuntimeException('Lat/Lng must be numbers.');

    $image_path = $loc['image_path'] ?? '';
    $newUpload = handle_upload('image');
    if ($newUpload) {
      // delete old file
      if (!empty($image_path)) {
        $oldFile = __DIR__ . '/../public/' . $image_path;
        if (is_file($oldFile)) @unlink($oldFile);
      }
      $image_path = $newUpload;
    }

    if ($editing) {
      $st = db()->prepare("UPDATE locations SET type_id=?, title=?, short_text=?, description=?, address=?, lat=?, lng=?, image_path=?, is_active=? WHERE id=?");
      $st->execute([$type_id, $title, $short_text, $description, $address, $lat, $lng, $image_path, $is_active, $id]);
      $msg = 'Updated.';
    } else {
      $st = db()->prepare("INSERT INTO locations (type_id,title,short_text,description,address,lat,lng,image_path,is_active) VALUES (?,?,?,?,?,?,?,?,?)");
      $st->execute([$type_id, $title, $short_text, $description, $address, $lat, $lng, $image_path, $is_active]);
      $msg = 'Created.';
      $editing = true;
      $id = (int)db()->lastInsertId();
    }

    // reload
    $st = db()->prepare("SELECT * FROM locations WHERE id=?");
    $st->execute([$id]);
    $loc = $st->fetch() ?: $loc;

  } catch (Throwable $e) {
    $err = $e->getMessage();
  }
}

$api_key = get_setting('google_api_key','');
$default_zoom = (int)get_setting('default_zoom','11');

$title = $editing ? "Edit Location" : "Add Location";
ob_start(); ?>
<div class="card">
  <h2><?= e($title) ?></h2>
  <p><a href="locations.php">← Back to Locations</a></p>
  <?php if($msg): ?><p style="color:green"><?= e($msg) ?></p><?php endif; ?>
  <?php if($err): ?><p class="danger"><?= e($err) ?></p><?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

    <div class="row">
      <div class="col">
        <label><strong>Type</strong></label>
        <select name="type_id">
          <?php foreach($types as $t): ?>
            <option value="<?= (int)$t['id'] ?>" <?= (int)$loc['type_id']===(int)$t['id']?'selected':'' ?>>
              <?= e($t['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col">
        <label><strong>Active</strong></label><br>
        <input type="checkbox" name="is_active" <?= (int)$loc['is_active']===1?'checked':'' ?> style="width:auto;transform:scale(1.1);margin-top:12px;">
      </div>
    </div>

    <div class="row" style="margin-top:10px;">
      <div class="col">
        <label><strong>Title</strong></label>
        <input name="title" value="<?= e($loc['title']) ?>" required>
      </div>
      <div class="col">
        <label><strong>Short text (card)</strong></label>
        <input name="short_text" value="<?= e($loc['short_text'] ?? '') ?>" placeholder="Short description for list card">
      </div>
    </div>

    <div class="row" style="margin-top:10px;">
      <div class="col">
        <label><strong>Address</strong></label>
        <input name="address" value="<?= e($loc['address'] ?? '') ?>" placeholder="Muscat, Oman">
      </div>
    </div>

    <div class="row" style="margin-top:10px;">
      <div class="col">
        <label><strong>Description</strong></label>
        <textarea name="description"><?= e($loc['description'] ?? '') ?></textarea>
      </div>
    </div>

    <div class="row" style="margin-top:10px;">
      <div class="col">
        <label><strong>Image upload (optional)</strong></label>
        <input type="file" name="image" accept="image/png,image/jpeg,image/webp">
        <?php if(!empty($loc['image_path'])): ?>
          <p class="muted">Current: <a href="../public/<?= e($loc['image_path']) ?>" target="_blank"><?= e($loc['image_path']) ?></a></p>
          <img src="../public/<?= e($loc['image_path']) ?>" style="max-width:220px;border-radius:12px">
        <?php endif; ?>
      </div>
    </div>

    <div class="card" style="margin-top:14px;">
      <h3>Pick location on map</h3>
      <?php if(!$api_key): ?>
        <p class="danger">Google API key is missing. Go to Settings and add it.</p>
      <?php endif; ?>
      <div class="row">
        <div class="col">
          <label><strong>Latitude</strong></label>
          <input id="lat" name="lat" value="<?= e((string)$loc['lat']) ?>">
        </div>
        <div class="col">
          <label><strong>Longitude</strong></label>
          <input id="lng" name="lng" value="<?= e((string)$loc['lng']) ?>">
        </div>
      </div>
      <p class="muted">Click on the mini-map to set Lat/Lng. You can also drag the marker.</p>
      <div id="miniMap" style="width:100%;height:360px;border-radius:14px;overflow:hidden;background:#eee"></div>
    </div>

    <div style="margin-top:12px;">
      <button class="btn"><?= $editing ? 'Save Changes' : 'Create Location' ?></button>
      <?php if($editing): ?>
        <a class="btn secondary" href="location_form.php">+ Add Another</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<script>
const API_KEY = <?= json_encode($api_key) ?>;
const START = {
  lat: parseFloat(<?= json_encode((string)$loc['lat']) ?>) || parseFloat(<?= json_encode(get_setting('default_lat','23.5880')) ?>),
  lng: parseFloat(<?= json_encode((string)$loc['lng']) ?>) || parseFloat(<?= json_encode(get_setting('default_lng','58.3829')) ?>),
  zoom: parseInt(<?= json_encode($default_zoom) ?>) || 11
};

function loadGoogle(cb){
  if(!API_KEY) return;
  const s = document.createElement('script');
  s.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(API_KEY);
  s.onload = cb;
  document.head.appendChild(s);
}

function initMiniMap(){
  const map = new google.maps.Map(document.getElementById('miniMap'), {
    center: {lat: START.lat, lng: START.lng},
    zoom: START.zoom,
    mapTypeControl: false,
    streetViewControl: false,
    fullscreenControl: true,
  });

  const marker = new google.maps.Marker({
    position: {lat: START.lat, lng: START.lng},
    map,
    draggable: true
  });

  function setInputs(pos){
    document.getElementById('lat').value = pos.lat().toFixed(7);
    document.getElementById('lng').value = pos.lng().toFixed(7);
  }

  map.addListener('click', (e) => {
    marker.setPosition(e.latLng);
    setInputs(e.latLng);
  });

  marker.addListener('dragend', (e) => setInputs(e.latLng));
}

if(API_KEY){
  loadGoogle(initMiniMap);
}
</script>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../app/admin_layout.php';
