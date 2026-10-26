<?php
// Daftar "Pesanan Saya": browser mengirim pasangan nomor order + nomor WhatsApp yang pernah dibuat di perangkat ini.
require __DIR__ . '/lib.php';
require_post();
$in = body(); $res = [];
foreach (array_slice((array)($in['orders'] ?? []), 0, 30) as $r) {
  $o = find_order(trim((string)($r['id'] ?? '')));
  if ($o && hash_equals(norm_hp($o['c_hp']), norm_hp($r['wa'] ?? ''))) $res[] = order_view($o);
}
out(['orders' => $res]);
