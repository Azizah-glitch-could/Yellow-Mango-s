<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

set_exception_handler(function ($e) {
  error_log($e);
  out(['error' => 'Terjadi kesalahan di server. Pastikan MySQL menyala dan database sudah di-import.'], 500);
});

function out(array $d, int $code = 200): void {
  http_response_code($code);
  $d['_now'] = time() * 1000;
  echo json_encode($d, JSON_UNESCAPED_UNICODE);
  exit;
}
function fail(string $msg, int $code = 422, ?string $field = null): void { out(['error' => $msg] + ($field ? ['field' => $field] : []), $code); }

function db(): PDO {
  static $p = null;
  if ($p) return $p;
  if (DB_DRIVER === 'sqlite') {
    $p = new PDO('sqlite:' . DB_SQLITE);
    $p->exec('PRAGMA foreign_keys=ON');
  } else {
    $opt = [];
    if (DB_SSL) {
      $opt[PDO::MYSQL_ATTR_SSL_CA] = DB_SSL_CA ?: '/etc/ssl/certs/ca-certificates.crt';
      $opt[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = DB_SSL_CA !== '';
    }
    $p = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, $opt);    $p->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
  }
  $p->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $p->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
  return $p;
}
function body(): array { $r = json_decode(file_get_contents('php://input'), true); return is_array($r) ? $r : []; }
function require_post(): void {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Method tidak diizinkan.', 405);
  if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'fetch') fail('Permintaan tidak valid.', 403);
}
function norm_hp($s): string {
  $d = preg_replace('/\D/', '', (string)$s);
  if (strpos($d, '62') === 0) $d = '0' . substr($d, 2);
  return $d;
}
function now_ms(): int { return time() * 1000; }

/* ---- produk ---- */
function product_out(array $r): array {
  return [
    'id' => $r['id'], 'name' => $r['nama_produk'], 'short' => $r['nama_singkat'], 'cat' => $r['kategori'],
    'price' => $r['harga'] === null ? null : (int)$r['harga'],
    'promo' => $r['harga_promo'] === null ? null : (int)$r['harga_promo'],
    'stock' => (int)$r['stok'], 'cap' => (int)$r['kapasitas'], 'soon' => (bool)$r['segera_hadir'],
    'tags' => $r['label'] === '' ? [] : explode(',', $r['label']),
    'rating' => (float)$r['rating'], 'rev' => (int)$r['jumlah_ulasan'], 'sold' => (int)$r['terjual'], 'added' => (int)$r['urutan'],
    'col' => $r['warna'], 'bg' => $r['warna_bg'], 'art' => $r['ilustrasi'], 'img' => $r['foto'],
    'alt' => $r['teks_alt'], 'kw' => $r['kata_kunci'], 'brief' => $r['ringkasan'], 'long' => $r['deskripsi'],
    'power' => $r['kata_rasa'] === '' || $r['kata_rasa'] === null ? [] : explode(',', $r['kata_rasa']),
    'size' => $r['ukuran'],
  ];
}
function all_products(): array {
  return array_map('product_out', db()->query('SELECT * FROM produk ORDER BY urutan')->fetchAll());
}

/* ---- pesanan ---- */
const ORDER_SQL = 'SELECT o.*, c.nama AS c_nama, c.email AS c_email, c.no_hp AS c_hp FROM pesanan o JOIN pelanggan c ON c.id = o.id_pelanggan';
function find_order(string $no): ?array {
  $s = db()->prepare(ORDER_SQL . ' WHERE o.nomor_order = ?'); $s->execute([$no]);
  return $s->fetch() ?: null;
}
function expire_if_needed(array $o): array {
  if ($o['status_pembayaran'] === 'menunggu' && (int)$o['batas_bayar'] < now_ms()) {
    db()->prepare("UPDATE pesanan SET status_pembayaran='kedaluwarsa', alasan_gagal='Batas waktu pembayaran habis.' WHERE id_order=? AND status_pembayaran='menunggu'")->execute([$o['id_order']]);
    $o['status_pembayaran'] = 'kedaluwarsa'; $o['alasan_gagal'] = 'Batas waktu pembayaran habis.';
  }
  return $o;
}
function order_view(array $o): array {
  $o = expire_if_needed($o);
  $db = db();
  $s = $db->prepare('SELECT id_produk,nama_item,jumlah,harga_satuan FROM detail_pesanan WHERE id_order=? ORDER BY id'); $s->execute([$o['id_order']]);
  $items = array_map(fn($r) => ['id' => $r['id_produk'], 'name' => $r['nama_item'], 'qty' => (int)$r['jumlah'], 'unit' => (int)$r['harga_satuan']], $s->fetchAll());
  $s = $db->prepare('SELECT label,waktu FROM log_pesanan WHERE id_order=? ORDER BY id'); $s->execute([$o['id_order']]);
  $log = array_map(fn($r) => [(int)$r['waktu'], $r['label']], $s->fetchAll());
  $s = $db->prepare('SELECT * FROM retur_pesanan WHERE id_order=?'); $s->execute([$o['id_order']]);
  $r = $s->fetch();
  return [
    'id' => $o['nomor_order'], 'items' => $items,
    'cust' => ['nama' => $o['c_nama'], 'wa' => $o['c_hp'], 'email' => $o['c_email'], 'alamat' => $o['alamat_kirim'], 'catatan' => $o['catatan']],
    'del' => $o['pengiriman'], 'courier' => $o['kurir'], 'branch' => (int)$o['cabang'], 'pay' => $o['metode_bayar'],
    'sub' => (int)$o['subtotal'], 'disc' => (int)$o['diskon'], 'ship' => (int)$o['ongkir'], 'total' => (int)$o['total_harga'],
    'payStatus' => $o['status_pembayaran'], 'st' => (int)$o['tahap'], 'reason' => $o['alasan_gagal'],
    'exp' => (int)$o['batas_bayar'], 'created' => (int)$o['dibuat'], 'doneAt' => $o['selesai_pada'] === null ? null : (int)$o['selesai_pada'],
    'log' => $log,
    'ret' => $r ? ['reason' => $r['alasan'], 'sol' => $r['solusi'], 'note' => $r['catatan'], 'file' => $r['nama_file'], 'st' => $r['status'], 'at' => (int)$r['dibuat']] : null,
  ];
}
/** Pesanan bisa dibuka oleh: (a) pemilik akun yang sedang masuk, atau (b) siapa pun yang tahu nomor order DAN nomor WhatsApp-nya. */
function auth_order(array $in): array {
  $o = find_order(trim((string)($in['id'] ?? '')));
  if ($o) {
    $uid = user_id();
    if ($uid && $o['id_pengguna'] !== null && (int)$o['id_pengguna'] === $uid) return $o;
    if (hash_equals(norm_hp($o['c_hp']), norm_hp($in['wa'] ?? ''))) return $o;
  }
  fail('Pesanan tidak ditemukan. Periksa nomor order dan nomor WhatsApp.', 404);
}
/** Kebutuhan stok per produk (paket combo dipecah jadi isinya). */
function demand(array $items): array {
  $d = [];
  foreach ($items as $i) {
    if ($i['id'] === 'combo') { foreach (COMBO['items'] as $pid) $d[$pid] = ($d[$pid] ?? 0) + $i['qty']; }
    else $d[$i['id']] = ($d[$i['id']] ?? 0) + $i['qty'];
  }
  return $d;
}
/** Kurangi stok secara atomik. Panggil di dalam transaksi. */
function take_stock(array $demand): bool {
  $st = db()->prepare('UPDATE produk SET stok = stok - ?, terjual = terjual + ? WHERE id = ? AND stok >= ?');
  foreach ($demand as $pid => $q) { $st->execute([$q, $q, $pid, $q]); if ($st->rowCount() < 1) return false; }
  return true;
}
function add_log(int $idOrder, string $label): void {
  db()->prepare('INSERT INTO log_pesanan (id_order,label,waktu) VALUES (?,?,?)')->execute([$idOrder, $label, now_ms()]);
}
function order_items_of(int $idOrder): array {
  $s = db()->prepare('SELECT id_produk AS id, jumlah AS qty FROM detail_pesanan WHERE id_order=?'); $s->execute([$idOrder]);
  return array_map(fn($r) => ['id' => $r['id'], 'qty' => (int)$r['qty']], $s->fetchAll());
}

/* ---- admin ---- */
function admin_session(): void {
  if (session_status() === PHP_SESSION_ACTIVE) return;
  session_name('ymadmin');
  session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']), 'path' => '/']);
  session_start();
}
function require_admin(): void { admin_session(); if (empty($_SESSION['admin'])) fail('Harus login sebagai admin.', 401); }
function admin_payload(): array {
  $rows = db()->query(ORDER_SQL . ' ORDER BY o.id_order DESC LIMIT 200')->fetchAll();
  return ['products' => all_products(), 'orders' => array_map('order_view', $rows)];
}

/* ---- akun pengguna (register / login / dashboard) ---- */
// Sesi pengguna memakai nama cookie berbeda dari admin ("ymuser" vs "ymadmin") supaya tidak tercampur.
function user_session(bool $create = false): void {
  if (session_status() === PHP_SESSION_ACTIVE) return;
  if (!$create && empty($_COOKIE['ymuser'])) return;   // tamu tidak diberi sesi
  session_name('ymuser');
  session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']), 'path' => '/']);
  session_start();
}
function user_id(): ?int { user_session(); return isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : null; }
function current_user(): ?array {
  $id = user_id(); if (!$id) return null;
  $s = db()->prepare('SELECT id,nama,email,no_hp,alamat,dibuat FROM pengguna WHERE id=?'); $s->execute([$id]);
  return $s->fetch() ?: null;
}
function require_user(): array { $u = current_user(); if (!$u) fail('Silakan masuk dulu.', 401); return $u; }
function user_out(array $u): array {
  return ['id' => (int)$u['id'], 'nama' => $u['nama'], 'email' => $u['email'], 'hp' => $u['no_hp'], 'alamat' => $u['alamat'] ?? '', 'dibuat' => (int)$u['dibuat']];
}
function user_orders(int $uid): array {
  $s = db()->prepare(ORDER_SQL . ' WHERE o.id_pengguna = ? ORDER BY o.id_order DESC LIMIT 100'); $s->execute([$uid]);
  return array_map('order_view', $s->fetchAll());
}
function valid_password(string $p, string $field = 'password'): void {
  if (strlen($p) < 8) fail('Password minimal 8 karakter.', 422, $field);
  if (strlen($p) > 72) fail('Password maksimal 72 karakter.', 422, $field);
  if (!preg_match('/[A-Za-z]/', $p) || !preg_match('/\d/', $p)) fail('Password harus berisi huruf dan angka.', 422, $field);
}
// hash tiruan: menyamakan waktu respons saat email tidak ditemukan
define('DUMMY_HASH', '$2y$10$Y0JvUQ5FK1PNRqedJfK5cuPScKjP/wiK4vPlw.upZu7bzZQha3uoK');
