<?php
/* ====== KONFIGURASI YELLOW MANGO'S (ubah di sini) ====== */
date_default_timezone_set('Asia/Makassar');

// Database. Untuk XAMPP/Laragon bawaan: user root, password kosong.
define('DB_DRIVER', getenv('YM_DRIVER') ?: 'mysql');   // 'mysql' (normal) atau 'sqlite' (hanya untuk pengujian)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'db_yellow_mangos');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_SSL', getenv('DB_SSL') === '1');
define('DB_SSL_CA', getenv('DB_SSL_CA') ?: '');
define('DB_SQLITE', getenv('YM_SQLITE') ?: __DIR__ . '/test.sqlite');

// Toko
define('WA_NUMBER', '628000000000');        // GANTI: nomor WhatsApp admin (format 62...)
define('HOURS', '10.00–21.00 (contoh)');    // GANTI: jam buka asli
define('MIN_STOCK', 5);                     // LK 5/6: batas minimum 5 cup
define('MDR', 0.007);                       // LK 4: asumsi MDR 0,7%
define('PAY_SECONDS', 15 * 60);             // batas waktu bayar
define('PROMO_CODE', 'MANGO10');            // kode promo demo
define('PROMO_PCT', 10);
define('PROMO_DAYS', 3);                    // LK 5: promo diskon langsung 3 hari
define('PROMO_END', '2026-12-31 23:59:59'); // GANTI: kapan promo berakhir

// true = tombol "simulasi pembayaran" aktif (untuk presentasi/tugas).
// Saat dipakai sungguhan dengan Midtrans, ubah ke false.
define('SIMULATE_PAYMENT', true);

const SHIP = [
  'gosend' => ['name' => 'GoSend (Instant)',       'fee' => 10000, 'eta' => '30–45 menit'],
  'grab'   => ['name' => 'GrabExpress (cadangan)', 'fee' => 11000, 'eta' => '30–50 menit'],
];
const BRANCHES = [
  ['n' => "Yellow Mango's Pettarani",  'a' => 'Area Jl. A.P. Pettarani, Makassar'],
  ['n' => "Yellow Mango's Boulevard",  'a' => 'Area Jl. Boulevard, Makassar'],
  ['n' => "Yellow Mango's Hertasning", 'a' => 'Area Jl. Hertasning, Makassar'],
];
const PAY_METHODS = ['qris', 'gopay', 'shopeepay', 'dana', 'transfer', 'va'];
// LK 5: Mango Fresh Combo = 1 Smoothies Mangga + 1 Salad Buah
const COMBO = ['id' => 'combo', 'name' => 'Mango Fresh Combo', 'price' => 35000, 'normal' => 38000, 'items' => ['mangga', 'salad']];
// Urutan status pesanan (LK 6)
const STAGES = [
  'kirim' => ['Order diterima', 'Dipacking', 'Diserahkan ke kurir', 'Dalam perjalanan', 'Delivered'],
  'ambil' => ['Order diterima', 'Sedang diproses', 'Siap diambil', 'Selesai'],
];
