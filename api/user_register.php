<?php
// Daftar akun baru.
require __DIR__ . '/lib.php';
require_post();
$in = body();
$nama = trim((string)($in['nama'] ?? ''));
$email = strtolower(trim((string)($in['email'] ?? '')));
$hp = trim((string)($in['hp'] ?? ''));
$pw = (string)($in['password'] ?? '');
$pw2 = (string)($in['password2'] ?? '');
if (mb_strlen($nama) < 2 || mb_strlen($nama) > 80) fail('Isi nama lengkap (2–80 karakter).', 422, 'nama');
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 120) fail('Format email tidak valid.', 422, 'email');
if (!preg_match('/^(\+?62|0)8\d{7,12}$/', preg_replace('/[\s-]/', '', $hp))) fail('Nomor WhatsApp tidak valid. Contoh: 08123456789.', 422, 'hp');
valid_password($pw);
if ($pw !== $pw2) fail('Konfirmasi password tidak sama.', 422, 'password2');

$db = db();
$s = $db->prepare('SELECT 1 FROM pengguna WHERE email=?'); $s->execute([$email]);
if ($s->fetch()) fail('Email sudah terdaftar. Silakan masuk.', 409, 'email');
try {
  $db->prepare('INSERT INTO pengguna (nama,email,no_hp,password_hash,dibuat) VALUES (?,?,?,?,?)')
     ->execute([$nama, $email, norm_hp($hp), password_hash($pw, PASSWORD_DEFAULT), now_ms()]);
} catch (PDOException $e) {
  if ($e->getCode() === '23000') fail('Email sudah terdaftar. Silakan masuk.', 409, 'email');   // dua pendaftaran bersamaan
  throw $e;
}
$id = (int)$db->lastInsertId();
user_session(true);
session_regenerate_id(true);
$_SESSION['uid'] = $id;
out(['user' => user_out(current_user()), 'orders' => []], 201);
