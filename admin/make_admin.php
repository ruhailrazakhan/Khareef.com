<?php


require_once __DIR__ . '/../public/db.php';

$email = 'ruhail@khareef.com';
$pass  = 'admin123';
$name  = 'Ruahil Super Admin';

$hash = password_hash($pass, PASSWORD_DEFAULT);

db()->prepare("INSERT INTO admins(name,email,password_hash,role,is_active) VALUES(?,?,?,?,1)")
  ->execute([$name,$email,$hash,'super_admin']);

echo "Created super admin: $email";