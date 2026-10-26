<?php
// Data akun + pesanan milik akun yang sedang masuk. Jika belum masuk: {"user": null} (bukan error).
require __DIR__ . '/lib.php';
$u = current_user();
if (!$u) out(['user' => null]);
out(['user' => user_out($u), 'orders' => user_orders((int)$u['id'])]);
