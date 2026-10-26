<?php
require __DIR__ . '/lib.php';
require_post();
user_session();
if (session_status() === PHP_SESSION_ACTIVE) { $_SESSION = []; session_destroy(); }
setcookie('ymuser', '', time() - 3600, '/');
out(['ok' => true]);
