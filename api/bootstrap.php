<?php
// Tahap 4a: mengambil data produk dari MySQL + pengaturan toko untuk ditampilkan di frontend.
require __DIR__ . '/lib.php';
out([
  'products' => all_products(),
  'combo' => COMBO,
  'config' => [
    'wa' => WA_NUMBER, 'mdr' => MDR, 'minStock' => MIN_STOCK, 'payMs' => PAY_SECONDS * 1000,
    'promo' => ['code' => PROMO_CODE, 'pct' => PROMO_PCT], 'promoDays' => PROMO_DAYS,
    'promoEnd' => strtotime(PROMO_END) * 1000, 'hours' => HOURS, 'ship' => SHIP, 'branches' => BRANCHES,
    'simulate' => SIMULATE_PAYMENT,
  ],
]);
