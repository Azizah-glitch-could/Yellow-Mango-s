<?php
// Masuk dengan email + password. Dibatasi 5 kali salah per 10 menit per sesi.
require __DIR__ . '/lib.php';
require_post();
$in = body();
user_session(true);
$now = time();
$_SESSION['gagal'] = array_values(array_filter($_SESSION['gagal'] ?? [], fn($t) => $t > $now - 600));
if (count($_SESSION['gagal']) >= 5) fail('Terlalu banyak percobaan salah. Coba lagi dalam 10 menit.', 429);

$email = strtolower(trim((string)($in['email'] ?? '')));
$s = db()->prepare('SELECT id, password_hash FROM pengguna WHERE email=?');
$s->execute([$email]);
$a = $s->fetch();
$ok = password_verify((string)($in['password'] ?? ''), $a['password_hash'] ?? DUMMY_HASH);
if (!$a || !$ok) {
  $_SESSION['gagal'][] = $now;
  usleep(600000);
  fail('Email atau password salah.', 401);
}
session_regenerate_id(true);
$_SESSION['uid'] = (int)$a['id'];
unset($_SESSION['gagal']);
$u = current_user();
out(['user' => user_out($u), 'orders' => user_orders((int)$u['id'])]);
