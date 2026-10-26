<?php
// Mengubah status pembayaran. Di versi dengan Midtrans asli, status "berhasil"/"gagal"
// datang dari notifikasi Midtrans (webhook), bukan dari tombol simulasi ini.
require __DIR__ . '/lib.php';
require_post();
$in = body();
$o = expire_if_needed(auth_order($in));
$res = $in['result'] ?? '';
$db = db();
$st = $o['status_pembayaran'];
$id = $o['id_order'];

if (in_array($res, ['berhasil', 'gagal'], true) && !SIMULATE_PAYMENT) fail('Simulasi pembayaran dinonaktifkan.', 403);

if ($res === 'berhasil') {
  if ($st !== 'menunggu') fail('Pesanan ini tidak sedang menunggu pembayaran.', 409);
  $db->beginTransaction();
  try {
    if (!take_stock(demand(order_items_of((int)$id)))) {
      $db->rollBack();
      $db->prepare("UPDATE pesanan SET status_pembayaran='gagal', alasan_gagal=? WHERE id_order=?")->execute(['Stok berubah dan tidak cukup untuk pesananmu.', $id]);
    } else {
      $db->prepare("UPDATE pesanan SET status_pembayaran='berhasil', tahap=0, alasan_gagal=NULL WHERE id_order=?")->execute([$id]);
      add_log((int)$id, STAGES[$o['pengiriman']][0]);
      $db->commit();
    }
  } catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); throw $e; }
} elseif ($res === 'gagal') {
  if ($st !== 'menunggu') fail('Pesanan ini tidak sedang menunggu pembayaran.', 409);
  $db->prepare("UPDATE pesanan SET status_pembayaran='gagal', alasan_gagal=? WHERE id_order=?")->execute(['Pembayaran ditolak oleh penyedia pembayaran.', $id]);
} elseif ($res === 'ulang') {
  if (!in_array($st, ['gagal', 'kedaluwarsa'], true)) fail('Pesanan tidak bisa dibayar ulang.', 409);
  $db->prepare("UPDATE pesanan SET status_pembayaran='menunggu', alasan_gagal=NULL, batas_bayar=? WHERE id_order=?")->execute([now_ms() + PAY_SECONDS * 1000, $id]);
} elseif ($res === 'batal') {
  if (!in_array($st, ['menunggu', 'gagal', 'kedaluwarsa'], true)) fail('Pesanan yang sudah dibayar tidak bisa dibatalkan.', 409);
  $db->prepare("UPDATE pesanan SET status_pembayaran='dibatalkan' WHERE id_order=?")->execute([$id]);
} elseif ($res !== 'cek') fail('Aksi tidak dikenal.');

out(['order' => order_view(find_order($o['nomor_order'])), 'products' => all_products()]);
