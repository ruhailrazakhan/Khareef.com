<?php
// ==== DATABASE CONFIG ====
define('DB_HOST', 'localhost');
define('DB_NAME', 'YOUR_DB_NAME');
define('DB_USER', 'YOUR_DB_USER');
define('DB_PASS', 'YOUR_DB_PASS');
define('DB_CHARSET', 'utf8mb4');

// ==== ADMIN LOGIN ====
// Generate password hash once using /admin/hash.php then paste here.
define('ADMIN_USER', 'admin');
define('ADMIN_PASS_HASH', '$2y$10$REPLACE_WITH_HASH');
