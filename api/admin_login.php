<?php
require __DIR__ . '/lib.php';
require_post();
$in = body();
$s = db()->prepare('SELECT * FROM admin WHERE username=?');
$s->execute([trim((string)($in['username'] ?? ''))]);
$a = $s->fetch();
if (!$a || !password_verify((string)($in['password'] ?? ''), $a['password_hash'])) {
  usleep(700000); // memperlambat tebak-tebakan password
  fail('Username atau password salah.', 401);
}
admin_session();
session_regenerate_id(true);
$_SESSION['admin'] = (int)$a['id'];
$_SESSION['name'] = $a['username'];
out(['ok' => true, 'name' => $a['username']] + admin_payload());
