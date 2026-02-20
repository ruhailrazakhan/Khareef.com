<?php
require_once __DIR__ . '/../app/auth.php'; require_admin();
require_once __DIR__ . '/../app/helpers.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $action = $_POST['action'] ?? '';
  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    // delete image file (optional)
    $st = db()->prepare("SELECT image_path FROM locations WHERE id=?");
    $st->execute([$id]);
    $row = $st->fetch();
    if ($row && !empty($row['image_path'])) {
      $file = __DIR__ . '/../public/' . $row['image_path'];
      if (is_file($file)) @unlink($file);
    }
    $del = db()->prepare("DELETE FROM locations WHERE id=?");
    $del->execute([$id]);
    $msg = 'Location deleted.';
  }
}

$locs = db()->query("SELECT l.*, t.name AS type_name FROM locations l JOIN marker_types t ON t.id=l.type_id ORDER BY l.id DESC")->fetchAll();

$title = "Locations";
ob_start(); ?>
<div class="card">
  <h2>Locations</h2>
  <p class="muted">Add pins with mini-map picker + image upload.</p>
  <a class="btn" href="location_form.php">+ Add Location</a>
  <?php if($msg): ?><p><?= e($msg) ?></p><?php endif; ?>
</div>

<div class="card">
  <table>
    <thead><tr><th>ID</th><th>Title</th><th>Type</th><th>Lat/Lng</th><th>Active</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach($locs as $l): ?>
        <tr>
          <td><?= (int)$l['id'] ?></td>
          <td><?= e($l['title']) ?></td>
          <td><span class="pill"><?= e($l['type_name']) ?></span></td>
          <td class="muted"><?= e($l['lat']) ?>, <?= e($l['lng']) ?></td>
          <td><?= (int)$l['is_active']===1 ? 'Yes' : '<span class="danger">No</span>' ?></td>
          <td style="white-space:nowrap">
            <a class="btn secondary" href="location_form.php?id=<?= (int)$l['id'] ?>">Edit</a>
            <form method="post" style="display:inline" onsubmit="return confirm('Delete this location?');">
              <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
              <button class="btn secondary">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../app/admin_layout.php';
