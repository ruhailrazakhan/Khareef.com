<?php
require_once __DIR__ . '/../app/auth.php'; require_admin();
require_once __DIR__ . '/../app/helpers.php';

$counts = [
  'types' => (int)db()->query("SELECT COUNT(*) c FROM marker_types")->fetch()['c'],
  'locations' => (int)db()->query("SELECT COUNT(*) c FROM locations")->fetch()['c'],
];

$title = "Dashboard";
ob_start(); ?>
<div class="card">
  <h2>Dashboard</h2>
  <p class="muted">Use the admin pages to update settings, marker types, and add pins.</p>
  <div class="row">
    <div class="col card">
      <div class="pill">Marker Types</div>
      <h3><?= (int)$counts['types'] ?></h3>
      <a class="btn" href="types.php">Manage Types</a>
    </div>
    <div class="col card">
      <div class="pill">Locations</div>
      <h3><?= (int)$counts['locations'] ?></h3>
      <a class="btn" href="locations.php">Manage Locations</a>
    </div>
  </div>
</div>

<div class="card">
  <h3>Public Page</h3>
  <p class="muted">Open <code>/public/index.php</code> to view your travel guide map.</p>
</div>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../app/admin_layout.php';
