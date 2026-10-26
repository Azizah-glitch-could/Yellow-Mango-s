<?php
// Tahap 4b: menyimpan pesanan pembeli ke database saat checkout.
// Harga dan total selalu dihitung ulang di server, tidak dipercaya dari browser.
require __DIR__ . '/lib.php';
require_post();
$in = body();
$db = db();

$c = $in['cust'] ?? [];
$nama = trim((string)($c['nama'] ?? ''));
$hp = trim((string)($c['wa'] ?? ''));
$email = trim((string)($c['email'] ?? ''));
$alamat = trim((string)($c['alamat'] ?? ''));
$catatan = mb_substr(trim((string)($c['catatan'] ?? '')), 0, 300);
if (mb_strlen($nama) < 2 || mb_strlen($nama) > 80) fail('Isi nama lengkap.');
if (!preg_match('/^(\+?62|0)8\d{7,12}$/', preg_replace('/[\s-]/', '', $hp))) fail('Nomor WhatsApp tidak valid. Contoh: 08123456789.');
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Format email tidak valid.');
$del = $in['del'] ?? '';
if (!isset(STAGES[$del])) fail('Pilih cara menerima pesanan.');
$courier = null; $branch = 0;
if ($del === 'kirim') {
  $courier = $in['courier'] ?? '';
  if (!isset(SHIP[$courier])) fail('Pilih kurir.');
  if (mb_strlen($alamat) < 10) fail('Isi alamat pengantaran dengan lengkap (min. 10 karakter).');
} else {
  $branch = (int)($in['branch'] ?? -1);
  if (!isset(BRANCHES[$branch])) fail('Pilih outlet.');
}
$pay = $in['pay'] ?? '';
if (!in_array($pay, PAY_METHODS, true)) fail('Pilih metode pembayaran.');

$prod = [];
foreach ($db->query('SELECT * FROM produk')->fetchAll() as $r) $prod[$r['id']] = $r;
$items = []; $sub = 0;
$raw = $in['items'] ?? [];
if (!is_array($raw) || !$raw || count($raw) > 10) fail('Keranjang kosong.');
foreach ($raw as $r) {
  $id = (string)($r['id'] ?? ''); $q = (int)($r['qty'] ?? 0);
  if ($q < 1 || $q > 20) fail('Jumlah item tidak valid.');
  if ($id === 'combo') { $name = COMBO['name']; $unit = COMBO['price']; }
  elseif (isset($prod[$id]) && !$prod[$id]['segera_hadir']) { $p = $prod[$id]; $name = $p['nama_singkat']; $unit = (int)($p['harga_promo'] ?? $p['harga']); }
  else fail('Produk tidak tersedia.');
  $items[] = ['id' => $id, 'name' => $name, 'qty' => $q, 'unit' => $unit];
  $sub += $unit * $q;
}
foreach (demand($items) as $pid => $q) {
  if ((int)$prod[$pid]['stok'] < $q) fail($prod[$pid]['nama_singkat'] . ' tidak cukup stok. Sisa ' . (int)$prod[$pid]['stok'] . '.', 409);
}
$promo = strtoupper(trim((string)($in['promo'] ?? '')));
$disc = 0;
if ($promo !== '') {
  if ($promo !== PROMO_CODE) fail('Kode promo tidak dikenali.');
  $disc = (int)round($sub * PROMO_PCT / 100);
}
$ship = $del === 'kirim' ? SHIP[$courier]['fee'] : 0;
$total = $sub - $disc + $ship;

$uid = user_id();   // terhubung ke akun jika pembeli sedang masuk
$db->beginTransaction();
try {
  // pelanggan: pakai ulang jika nomor HP sama
  $hpn = norm_hp($hp);
  $s = $db->prepare('SELECT id FROM pelanggan WHERE no_hp = ?'); $s->execute([$hpn]);
  $cid = $s->fetchColumn();
  if ($cid) {
    $db->prepare('UPDATE pelanggan SET nama=?, email=?, alamat=? WHERE id=?')->execute([$nama, $email ?: null, $alamat ?: null, $cid]);
  } else {
    $db->prepare('INSERT INTO pelanggan (nama,email,no_hp,alamat) VALUES (?,?,?,?)')->execute([$nama, $email ?: null, $hpn, $alamat ?: null]);
    $cid = $db->lastInsertId();
  }
  $db->prepare('INSERT INTO pesanan (nomor_order,id_pelanggan,id_pengguna,alamat_kirim,catatan,pengiriman,kurir,cabang,metode_bayar,subtotal,diskon,ongkir,total_harga,status_pembayaran,tahap,batas_bayar,dibuat) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
     ->execute(['TMP' . bin2hex(random_bytes(6)), $cid, $uid ?: null, $alamat ?: null, $catatan ?: null, $del, $courier, $branch, $pay, $sub, $disc, $ship, $total, 'menunggu', -1, now_ms() + PAY_SECONDS * 1000, now_ms()]);
  $oid = (int)$db->lastInsertId();
  $no = 'YM-' . str_pad((string)$oid, 3, '0', STR_PAD_LEFT);
  $db->prepare('UPDATE pesanan SET nomor_order=? WHERE id_order=?')->execute([$no, $oid]);
  $ins = $db->prepare('INSERT INTO detail_pesanan (id_order,id_produk,nama_item,jumlah,harga_satuan) VALUES (?,?,?,?,?)');
  foreach ($items as $i) $ins->execute([$oid, $i['id'], $i['name'], $i['qty'], $i['unit']]);
  $db->commit();
} catch (Throwable $e) { $db->rollBack(); throw $e; }
out(['order' => order_view(find_order($no))], 201);
