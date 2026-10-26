<?php
require __DIR__ . '/lib.php';
require_post();
require_admin();
$in = body();
$db = db();
$act = $in['action'] ?? '';

if ($act === 'restock') {
  $db->prepare('UPDATE produk SET stok = kapasitas WHERE id = ? AND segera_hadir = 0')->execute([(string)($in['id'] ?? '')]);
} elseif ($act === 'updateproduct') {
  $id = (string)($in['id'] ?? '');
  $price = (int)($in['price'] ?? 0); $stock = (int)($in['stock'] ?? 0);
  $promo = ($in['promo'] ?? '') === '' || $in['promo'] === null ? null : (int)$in['promo'];
  if ($price < 1000 || $price > 1000000) fail('Harga harus antara Rp1.000 dan Rp1.000.000.');
  if ($promo !== null && ($promo < 1 || $promo >= $price)) fail('Harga promo harus lebih kecil dari harga normal.');
  if ($stock < 0 || $stock > 10000) fail('Stok tidak valid.');
  $db->prepare('UPDATE produk SET harga=?, harga_promo=?, stok=?, kapasitas=CASE WHEN kapasitas < ? THEN ? ELSE kapasitas END WHERE id=? AND segera_hadir=0')->execute([$price, $promo, $stock, $stock, $stock, $id]);
} elseif ($act === 'advance' || $act === 'approve') {
  $o = find_order((string)($in['id'] ?? ''));
  if (!$o) fail('Pesanan tidak ditemukan.', 404);
  if ($act === 'advance') {
    $stages = STAGES[$o['pengiriman']];
    if ($o['status_pembayaran'] !== 'berhasil') fail('Pesanan belum dibayar.', 409);
    $next = (int)$o['tahap'] + 1;
    if ($next >= count($stages)) fail('Pesanan sudah di status terakhir.', 409);
    $db->beginTransaction();
    $db->prepare('UPDATE pesanan SET tahap=?, selesai_pada=? WHERE id_order=?')->execute([$next, $next === count($stages) - 1 ? now_ms() : $o['selesai_pada'], $o['id_order']]);
    add_log((int)$o['id_order'], $stages[$next]);
    $db->commit();
  } else {
    $s = $db->prepare('SELECT * FROM retur_pesanan WHERE id_order=?'); $s->execute([$o['id_order']]); $r = $s->fetch();
    if (!$r || $r['status'] !== 'Menunggu verifikasi') fail('Tidak ada retur yang menunggu verifikasi.', 409);
    $db->beginTransaction();
    $db->prepare("UPDATE retur_pesanan SET status='Disetujui' WHERE id_order=?")->execute([$o['id_order']]);
    if ($r['solusi'] === 'replacement') {  // dibuat ulang: stok berkurang lagi (tidak di bawah 0)
      $get = $db->prepare('SELECT stok FROM produk WHERE id=?'); $set = $db->prepare('UPDATE produk SET stok=? WHERE id=?');
      foreach (demand(order_items_of((int)$o['id_order'])) as $pid => $q) { $get->execute([$pid]); $cur = (int)$get->fetchColumn(); $set->execute([max(0, $cur - $q), $pid]); }
    }
    $db->commit();
  }
} else fail('Aksi tidak dikenal.');

out(['ok' => true] + admin_payload());
