<?php
// Ubah profil atau ganti password (harus sudah masuk).
require __DIR__ . '/lib.php';
require_post();
$u = require_user();
$in = body();
$db = db();
$act = $in['action'] ?? '';

if ($act === 'profil') {
  $nama = trim((string)($in['nama'] ?? ''));
  $hp = trim((string)($in['hp'] ?? ''));
  $alamat = mb_substr(trim((string)($in['alamat'] ?? '')), 0, 300);
  if (mb_strlen($nama) < 2 || mb_strlen($nama) > 80) fail('Isi nama lengkap (2–80 karakter).', 422, 'nama');
  if (!preg_match('/^(\+?62|0)8\d{7,12}$/', preg_replace('/[\s-]/', '', $hp))) fail('Nomor WhatsApp tidak valid. Contoh: 08123456789.', 422, 'hp');
  $db->prepare('UPDATE pengguna SET nama=?, no_hp=?, alamat=? WHERE id=?')->execute([$nama, norm_hp($hp), $alamat ?: null, $u['id']]);
} elseif ($act === 'sandi') {
  $lama = (string)($in['lama'] ?? ''); $baru = (string)($in['baru'] ?? ''); $baru2 = (string)($in['baru2'] ?? '');
  $s = $db->prepare('SELECT password_hash FROM pengguna WHERE id=?'); $s->execute([$u['id']]);
  if (!password_verify($lama, (string)$s->fetchColumn())) { usleep(600000); fail('Password lama salah.', 422, 'lama'); }
  valid_password($baru, 'baru');
  if ($baru !== $baru2) fail('Konfirmasi password baru tidak sama.', 422, 'baru2');
  $db->prepare('UPDATE pengguna SET password_hash=? WHERE id=?')->execute([password_hash($baru, PASSWORD_DEFAULT), $u['id']]);
  session_regenerate_id(true);
} else fail('Aksi tidak dikenal.');

out(['ok' => true, 'user' => user_out(current_user()), 'orders' => user_orders((int)$u['id'])]);
