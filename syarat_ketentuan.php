<?php
session_start();
include 'koneksi.php';

$nama_user = $_SESSION['nama'] ?? 'Pelanggan Setia';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Syarat & Ketentuan - Yellow Mango's</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #1B3022; color: #ffffff; margin: 0; padding: 0; line-height: 1.6; }
        .navbar { position: sticky; top: 0; z-index: 1000; background-color: #1B3022; padding: 20px 50px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.1); box-sizing: border-box; }
        .logo { font-size: 22px; font-weight: bold; color: #FFC72C; text-decoration: none; }
        .nav-links { list-style: none; display: flex; gap: 25px; margin: 0; padding: 0; align-items: center; }
        .nav-links a { color: white; text-decoration: none; font-size: 15px; }
        .nav-links a:hover, .nav-links a.active { color: #FFC72C; }
        .btn-keluar { background-color: #d9534f; color: white; padding: 6px 14px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 14px; }
        
        .container { padding: 50px; max-width: 900px; margin: 0 auto; }
        h1 { color: #FFC72C; margin-bottom: 20px; text-align: center; font-size: 32px; }
        .content-box { background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.1); padding: 35px; border-radius: 12px; margin-top: 20px; }
        h2 { color: #FFC72C; font-size: 20px; margin-top: 25px; border-bottom: 1px solid rgba(255,199,44,0.3); padding-bottom: 8px; }
        p, li { color: #d1d5db; font-size: 15px; }
        ul { padding-left: 20px; }
        li { margin-bottom: 8px; }
    </style>
</head>
<body>

    <header class="navbar">
        <a href="user_dashboard.php" class="logo">Yellow Mango's</a>
        <ul class="nav-links">
            <li><a href="user_dashboard.php">Beranda</a></li>
            <li><a href="user_menu.php">Menu</a></li>
            <li><a href="user_pesanan.php">Pesanan Saya</a></li>
            <?php if (isset($_SESSION['role'])) { ?>
                <li><a href="login.php" class="btn-keluar">Keluar (<?php echo htmlspecialchars($nama_user); ?>)</a></li>
            <?php } else { ?>
                <li><a href="login.php" style="color: #FFC72C; font-weight: bold;">Masuk</a></li>
            <?php } ?>
        </ul>
    </header>

    <div class="container">
        <h1>Syarat & Ketentuan</h1>
        
        <div class="content-box">
            <p>Selamat datang di <strong>Yellow Mango's</strong>. Dengan mengakses dan melakukan transaksi melalui website ini, kamu dianggap telah membaca, memahami, dan menyetujui Syarat dan Ketentuan di bawah ini.</p>

            <h2>1. Akun Pengguna</h2>
            <ul>
                <li>Pengguna bertanggung jawab untuk menjaga kerahasiaan password dan informasi akun pribadi masing-masing.</li>
                <li>Setiap transaksi yang dilakukan menggunakan akun terdaftar dianggap sah dilakukan oleh pemilik akun tersebut.</li>
            </ul>

            <h2>2. Pemesanan dan Pembayaran</h2>
            <ul>
                <li>Pesanan yang telah dibuat akan diproses setelah mendapatkan konfirmasi dari pihak admin Yellow Mango's.</li>
                <li>Harga produk yang tertera pada katalog adalah harga final pada saat pemesanan dibuat.</li>
                <li>Stok produk bersifat terbatas dan dapat berubah sewaktu-waktu sesuai dengan ketersediaan bahan baku harian.</li>
            </ul>

            <h2>3. Pembatalan dan Pengembalian</h2>
            <p>Pesanan yang telah diproses dan diproduksi tidak dapat dibatalkan secara sepihak oleh pembeli. Jika terjadi kesalahan menu atau kendala stok dari pihak toko, admin akan menghubungi pembeli untuk penyelesaian terbaik.</p>

            <h2>4. Perubahan Ketentuan</h2>
            <p>Yellow Mango's berhak mengubah atau memperbarui Syarat & Ketentuan ini sewaktu-waktu demi peningkatan mutu layanan toko.</p>
        </div>
    </div>

    <?php include 'footer.php'; ?>

</body>
</html>