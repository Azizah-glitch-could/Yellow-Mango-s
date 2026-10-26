<?php
require __DIR__ . '/lib.php';
require_admin();
out(['name' => $_SESSION['name'] ?? 'admin'] + admin_payload());
