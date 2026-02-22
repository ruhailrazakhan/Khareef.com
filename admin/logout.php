<?php
require_once __DIR__ . '/../public/auth.php';
admin_logout();
header("Location: /admin/login.php");
exit;