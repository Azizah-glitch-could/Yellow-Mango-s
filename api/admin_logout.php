<?php
require __DIR__ . '/lib.php';
require_post();
admin_session();
$_SESSION = [];
session_destroy();
out(['ok' => true]);
