<?php

//echo "Ok here here";exit;
require_once __DIR__ . '/../public/auth.php';
$me = require_super_admin();

$err = $ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim((string)($_POST['name'] ?? ''));
  $email = trim((string)($_POST['email'] ?? ''));
  $pass = (string)($_POST['password'] ?? '');
  $role = ($_POST['role'] ?? 'admin') === 'super_admin' ? 'super_admin' : 'admin';

  if ($name === '' || $email === '' || $pass === '') {
    $err = "All fields required.";
  } else {
    try {
      $hash = password_hash($pass, PASSWORD_DEFAULT);
      db()->prepare("INSERT INTO admins(name,email,password_hash,role,is_active) VALUES(?,?,?,?,1)")
        ->execute([$name,$email,$hash,$role]);
      $ok = "Admin created.";
    } catch (Throwable $e) {
      $err = "Error: " . $e->getMessage();
    }
  }
}

$admins = db()->query("SELECT id,name,email,role,is_active,created_at FROM admins ORDER BY id DESC")->fetchAll();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Manage Admins</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar bg-white border-bottom sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="/admin/index.php">← Admin</a>
    <a class="btn btn-outline-danger btn-sm" href="/admin/logout.php">Logout</a>
  </div>
</nav>

<div class="container py-4">
  <div class="row g-3">
    <div class="col-12 col-lg-5">
      <div class="card p-3">
        <div class="fw-bold mb-2">Add Admin / Super Admin</div>

        <?php if($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>
        <?php if($ok): ?><div class="alert alert-success"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

        <form method="post">
          <div class="mb-2">
            <label class="form-label fw-bold">Name</label>
            <input class="form-control" name="name" required>
          </div>
          <div class="mb-2">
            <label class="form-label fw-bold">Email</label>
            <input class="form-control" name="email" required>
          </div>
          <div class="mb-2">
            <label class="form-label fw-bold">Password</label>
            <input class="form-control" type="password" name="password" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold">Role</label>
            <select class="form-select" name="role">
              <option value="admin">Admin</option>
              <option value="super_admin">Super Admin</option>
            </select>
          </div>
          <button class="btn btn-primary w-100 fw-bold">Create</button>
        </form>
      </div>
    </div>

    <div class="col-12 col-lg-7">
      <div class="card p-3">
        <div class="fw-bold mb-2">Existing Admins</div>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr class="text-secondary">
                <th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Active</th><th>Created</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($admins as $a): ?>
              <tr>
                <td><?= (int)$a['id'] ?></td>
                <td><?= htmlspecialchars($a['name']) ?></td>
                <td><?= htmlspecialchars($a['email']) ?></td>
                <td><span class="badge text-bg-info"><?= htmlspecialchars($a['role']) ?></span></td>
                <td><?= (int)$a['is_active'] ? 'Yes' : 'No' ?></td>
                <td class="text-secondary small"><?= htmlspecialchars($a['created_at']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="text-secondary small">
          Next: add “disable admin / reset password” buttons (I can add).
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>