<?php
require_once __DIR__ . '/../app/auth.php'; require_admin();
require_once __DIR__ . '/../app/helpers.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $action = $_POST['action'] ?? '';

  if ($action === 'create') {
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon_url'] ?? '');
    $sort = (int)($_POST['sort_order'] ?? 0);
    $active = !empty($_POST['is_active']) ? 1 : 0;

    if ($name === '') $msg = 'Name is required.';
    else {
      $st = db()->prepare("INSERT INTO marker_types (name, icon_url, is_default, sort_order, is_active) VALUES (?,?,?,?,?)");
      $st->execute([$name, $icon ?: null, 0, $sort, $active]);
      $msg = 'Type added.';
    }
  }

  if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon_url'] ?? '');
    $sort = (int)($_POST['sort_order'] ?? 0);
    $active = !empty($_POST['is_active']) ? 1 : 0;

    $is_default = (int)db()->prepare("SELECT is_default FROM marker_types WHERE id=?")->execute([$id]) or 0;
    $row = db()->prepare("SELECT is_default FROM marker_types WHERE id=?");
    $row->execute([$id]);
    $r = $row->fetch();
    $default = $r ? (int)$r['is_default'] : 0;

    if ($default === 1) {
      // Keep default active and name protected
      $st = db()->prepare("UPDATE marker_types SET icon_url=?, sort_order=?, is_active=1 WHERE id=?");
      $st->execute([$icon ?: null, $sort, $id]);
      $msg = 'Default type updated (always active).';
    } else {
      $st = db()->prepare("UPDATE marker_types SET name=?, icon_url=?, sort_order=?, is_active=? WHERE id=?");
      $st->execute([$name, $icon ?: null, $sort, $active, $id]);
      $msg = 'Type updated.';
    }
  }

  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $row = db()->prepare("SELECT is_default FROM marker_types WHERE id=?");
    $row->execute([$id]);
    $r = $row->fetch();
    if ($r && (int)$r['is_default'] === 1) {
      $msg = 'Cannot delete default type.';
    } else {
      // prevent delete if locations exist
      $cst = db()->prepare("SELECT COUNT(*) c FROM locations WHERE type_id=?");
      $cst->execute([$id]);
      $c = (int)$cst->fetch()['c'];
      if ($c > 0) {
        $msg = 'Cannot delete type because locations use it.';
      } else {
        $st = db()->prepare("DELETE FROM marker_types WHERE id=?");
        $st->execute([$id]);
        $msg = 'Type deleted.';
      }
    }
  }
}

$types = db()->query("SELECT * FROM marker_types ORDER BY sort_order, name")->fetchAll();

$title = "Marker Types";
ob_start(); ?>
<div class="card">
  <h2>Marker Types</h2>
  <p class="muted">Create types like Hotel, Petrol Station, Restaurant. You can set icon URL (optional). Default type cannot be disabled.</p>
  <?php if($msg): ?><p><?= e($msg) ?></p><?php endif; ?>
</div>

<div class="card">
  <h3>Add Type</h3>
  <form method="post">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="create">
    <div class="row">
      <div class="col">
        <label><strong>Name</strong></label>
        <input name="name" placeholder="Hotel" required>
      </div>
      <div class="col">
        <label><strong>Icon URL (optional)</strong></label>
        <input name="icon_url" placeholder="https://.../marker.png">
        <p class="muted">If empty, Google default marker will be used. You can upload icons yourself and paste URL.</p>
      </div>
      <div class="col">
        <label><strong>Sort</strong></label>
        <input name="sort_order" type="number" value="0">
      </div>
      <div class="col">
        <label><strong>Active</strong></label><br>
        <input name="is_active" type="checkbox" checked style="width:auto;transform:scale(1.1);margin-top:12px;">
      </div>
    </div>
    <div style="margin-top:12px;">
      <button class="btn">Add</button>
    </div>
  </form>
</div>

<div class="card">
  <h3>Existing Types</h3>
  <table>
    <thead><tr><th>Name</th><th>Icon</th><th>Sort</th><th>Active</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach($types as $t): ?>
        <tr>
          <form method="post">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
            <td>
              <?php if((int)$t['is_default']===1): ?>
                <strong><?= e($t['name']) ?></strong> <span class="pill">default</span>
                <input type="hidden" name="name" value="<?= e($t['name']) ?>">
              <?php else: ?>
                <input name="name" value="<?= e($t['name']) ?>">
              <?php endif; ?>
            </td>
            <td><input name="icon_url" value="<?= e($t['icon_url'] ?? '') ?>" placeholder="https://..."></td>
            <td style="width:90px;"><input name="sort_order" type="number" value="<?= (int)$t['sort_order'] ?>"></td>
            <td style="width:90px;">
              <?php if((int)$t['is_default']===1): ?>
                <span class="pill">always on</span>
                <input type="hidden" name="is_active" value="1">
              <?php else: ?>
                <input name="is_active" type="checkbox" <?= (int)$t['is_active']===1?'checked':'' ?> style="width:auto;transform:scale(1.1);">
              <?php endif; ?>
            </td>
            <td style="white-space:nowrap">
              <button class="btn">Save</button>
          </form>
          <?php if((int)$t['is_default']!==1): ?>
            <form method="post" style="display:inline" onsubmit="return confirm('Delete this type?');">
              <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
              <button class="btn secondary">Delete</button>
            </form>
          <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../app/admin_layout.php';
