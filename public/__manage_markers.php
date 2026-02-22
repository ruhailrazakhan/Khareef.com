<?php
require_once __DIR__ . '/db.php';
//require_once __DIR__ . '/auth.php';
//require_admin();

$q = trim((string)($_GET['q'] ?? ''));

if ($q !== '') {
  $st = db()->prepare("SELECT id,title,type,lat,lng,created_at FROM markers
    WHERE title LIKE ? OR short_text LIKE ? OR type LIKE ?
    ORDER BY id DESC");
  $like = '%'.$q.'%';
  $st->execute([$like,$like,$like]);
  $rows = $st->fetchAll();
} else {
  $rows = db()->query("SELECT id,title,type,lat,lng,created_at FROM markers ORDER BY id DESC")->fetchAll();
}

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Manage Markers</title>
  <style>
    body{margin:0;font-family:system-ui;background:#f2f4f7;color:#0b1320}
    .wrap{max-width:1150px;margin:0 auto;padding:16px}
    .top{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
    .btn{background:#003580;color:#fff;text-decoration:none;padding:10px 14px;border-radius:12px;font-weight:900;display:inline-flex;gap:8px;align-items:center}
    .btn2{background:#fff;color:#003580;border:1px solid #e6ebf0}
    .card{background:#fff;border:1px solid #e6ebf0;border-radius:16px;box-shadow:0 2px 10px rgba(16,24,40,.06);padding:14px;margin-top:12px}
    table{width:100%;border-collapse:collapse}
    th,td{padding:10px;border-bottom:1px solid #eef2f6;text-align:left;font-size:13px;vertical-align:top}
    th{color:#5b6573;font-weight:900}
    .pill{display:inline-flex;padding:6px 10px;border-radius:999px;background:#eef3ff;color:#003580;font-weight:900;font-size:12px;text-transform:capitalize}
    .actions{display:flex;gap:10px;flex-wrap:wrap}
    .actions a,.actions button{
      font-weight:900;
      color:#0057d9;
      background:transparent;
      border:none;
      cursor:pointer;
      padding:0;
      font-size:13px;
      text-decoration:none;
    }
    .danger{color:#b00020 !important}
    .searchRow{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
    .searchRow input{
      padding:10px 12px;border:1px solid #e6ebf0;border-radius:12px;min-width:260px;
    }
    .muted{color:#5b6573;font-size:12px}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="top">
      <div>
        <h2 style="margin:0">Manage Markers</h2>
        <div class="muted">Admin only</div>
      </div>

      <div class="searchRow">
        <form method="get" style="display:flex;gap:10px;align-items:center">
          <input name="q" value="<?= e($q) ?>" placeholder="Search title/type/text...">
          <button class="btn btn2" type="submit">Search</button>
          <?php if($q!==''): ?>
            <a class="btn btn2" href="manage_markers.php">Clear</a>
          <?php endif; ?>
        </form>

        <a class="btn" href="admin_add_marker.php">+ Add Marker</a>
      </div>
    </div>

    <div class="card">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Type</th>
            <th>Lat/Lng</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($rows as $r): ?>
          <tr>
            <td><?= (int)$r['id'] ?></td>
            <td><?= e($r['title']) ?></td>
            <td><span class="pill"><?= e($r['type']) ?></span></td>
            <td><?= e($r['lat']) ?>, <?= e($r['lng']) ?></td>
            <td>
              <div class="actions">
                <a href="admin_edit_marker.php?id=<?= (int)$r['id'] ?>">Edit</a>
                <a target="_blank" href="marker.php?id=<?= (int)$r['id'] ?>">View</a>
                <button class="danger" onclick="delMarker(<?= (int)$r['id'] ?>, '<?= e($r['title']) ?>')">Delete</button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php if(!$rows): ?>
        <div class="muted" style="padding:12px">No markers.</div>
      <?php endif; ?>
    </div>
  </div>

<script>
async function delMarker(id, title){
  const sure = confirm(`DELETE marker #${id}\n\n"${title}"\n\nThis will delete marker + images.\nOK?`);
  if(!sure) return;

  const res = await fetch('delete_marker.php', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({id})
  });
  const text = await res.text();
  console.log("DELETE response:", text);

  let data;
  try{ data = JSON.parse(text); }catch(e){
    alert("Server returned non-JSON. Check console.");
    return;
  }
  if(!data.ok){
    alert(data.error || "Delete failed");
    return;
  }
  location.reload();
}
</script>
</body>
</html>