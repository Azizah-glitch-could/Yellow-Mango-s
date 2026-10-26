<?php
// Membuat hash password baru untuk admin. Jalankan dari terminal:
//   php database/buat_hash.php PasswordBaruKamu
// Lalu salin hasilnya ke phpMyAdmin:
//   UPDATE admin SET password_hash = 'HASIL_DI_SINI' WHERE username = 'admin';
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }   // tidak bisa dibuka lewat browser
if (empty($argv[1]) || strlen($argv[1]) < 8) { fwrite(STDERR, "Pemakaian: php buat_hash.php PasswordBaru (minimal 8 karakter)\n"); exit(1); }
echo password_hash($argv[1], PASSWORD_DEFAULT), "\n";
