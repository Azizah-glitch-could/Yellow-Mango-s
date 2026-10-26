<?php
require __DIR__ . '/lib.php';
require_post();
$o = auth_order($_POST);
$db = db();
$final = count(STAGES[$o['pengiriman']]) - 1;
if ($o['status_pembayaran'] !== 'berhasil' || (int)$o['tahap'] !== $final || $o['selesai_pada'] === null) fail('Retur hanya bisa diajukan setelah pesanan diterima.', 409);
if (now_ms() > (int)$o['selesai_pada'] + 3600 * 1000) fail('Batas pengajuan retur 1 jam sudah lewat.', 409);
$s = $db->prepare('SELECT 1 FROM retur_pesanan WHERE id_order=?'); $s->execute([$o['id_order']]);
if ($s->fetch()) fail('Retur untuk pesanan ini sudah diajukan.', 409);

$reasons = ['Barang rusak atau tumpah', 'Salah barang atau varian', 'Tidak sesuai deskripsi'];
$reason = (string)($_POST['reason'] ?? '');
$sol = (string)($_POST['sol'] ?? '');
if (!in_array($reason, $reasons, true)) fail('Alasan retur tidak valid.');
if (!in_array($sol, ['refund', 'replacement'], true)) fail('Pilih solusi yang diminta.');
$f = $_FILES['file'] ?? null;
if (!$f || $f['error'] !== UPLOAD_ERR_OK) fail('Unggah foto atau video bukti (maks. 8 MB).');
if ($f['size'] > 8 * 1024 * 1024) fail('Ukuran bukti maksimal 8 MB.');
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
$ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'video/mp4' => 'mp4', 'video/webm' => 'webm'][$mime] ?? null;
if (!$ext) fail('Format bukti harus foto (JPG/PNG/WebP) atau video (MP4/WebM).');
$dir = dirname(__DIR__) . '/uploads';
if (!is_dir($dir)) mkdir($dir, 0755, true);
$name = 'retur_' . $o['id_order'] . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) fail('Gagal menyimpan bukti. Coba lagi.', 500);
$db->prepare('INSERT INTO retur_pesanan (id_order,alasan,solusi,catatan,nama_file,status,dibuat) VALUES (?,?,?,?,?,?,?)')
   ->execute([$o['id_order'], $reason, $sol, mb_substr(trim((string)($_POST['note'] ?? '')), 0, 500), $name, 'Menunggu verifikasi', now_ms()]);
out(['order' => order_view(find_order($o['nomor_order']))], 201);
