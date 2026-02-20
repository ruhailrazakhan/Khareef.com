<?php
require_once __DIR__ . '/../app/auth.php'; require_admin();
require_once __DIR__ . '/../app/helpers.php';

$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  set_setting('google_api_key', trim($_POST['google_api_key'] ?? ''));
  set_setting('default_lat', trim($_POST['default_lat'] ?? '23.5880'));
  set_setting('default_lng', trim($_POST['default_lng'] ?? '58.3829'));
  set_setting('default_zoom', trim($_POST['default_zoom'] ?? '11'));
  set_setting('always_on_type', trim($_POST['always_on_type'] ?? 'Location'));
  $saved = true;
}

$google_api_key = get_setting('google_api_key','');
$default_lat = get_setting('default_lat','23.5880');
$default_lng = get_setting('default_lng','58.3829');
$default_zoom = get_setting('default_zoom','11');
$always_on_type = get_setting('always_on_type','Location');

$title = "Settings";
ob_start(); ?>
<div class="card">
  <h2>Settings</h2>
  <?php if($saved): ?><p style="color:green">Saved</p><?php endif; ?>
  <form method="post">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <div class="row">
      <div class="col">
        <label><strong>Google Maps API Key</strong></label>
        <input name="google_api_key" value="<?= e($google_api_key) ?>" placeholder="AIza...">
        <p class="muted">Required for map loading (public + admin mini-map picker).</p>
      </div>
    </div>

    <div class="row">
      <div class="col">
        <label><strong>Default Latitude</strong></label>
        <input name="default_lat" value="<?= e($default_lat) ?>">
      </div>
      <div class="col">
        <label><strong>Default Longitude</strong></label>
        <input name="default_lng" value="<?= e($default_lng) ?>">
      </div>
      <div class="col">
        <label><strong>Default Zoom</strong></label>
        <input name="default_zoom" value="<?= e($default_zoom) ?>">
      </div>
    </div>

    <div class="row" style="margin-top:10px;">
      <div class="col">
        <label><strong>Always-on Type Name</strong></label>
        <input name="always_on_type" value="<?= e($always_on_type) ?>" placeholder="Location">
        <p class="muted">This type is always enabled on the public filters (cannot be turned off).</p>
      </div>
    </div>

    <div style="margin-top:12px;">
      <button class="btn">Save Settings</button>
    </div>
  </form>
</div>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../app/admin_layout.php';
