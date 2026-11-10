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
    <title>Kebijakan Privasi - Yellow Mango's</title>
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
        <h1>Kebijakan Privasi</h1>
        
        <div class="content-box">
            <p>Di <strong>Yellow Mango's</strong>, privasi pengunjung dan pelanggan kami adalah prioritas utama. Dokumen Kebijakan Privasi ini menjelaskan jenis informasi yang kami kumpulkan dan bagaimana kami menggunakannya.</p>

            <h2>1. Informasi yang Kami Kumpulkan</h2>
            <p>Saat kamu mendaftar atau melakukan pemesanan di website kami, kami dapat mengumpulkan data pribadi seperti:</p>
            <ul>
                <li>Nama lengkap</li>
                <li>Alamat email aktif</li>
                <li>Riwayat transaksi dan pemesanan produk</li>
            </ul>

            <h2>2. Penggunaan Informasi</h2>
            <p>Informasi yang kami kumpulkan digunakan untuk keperluan:</p>
            <ul>
                <li>Memproses dan mengonfirmasi pesanan produk yang kamu beli.</li>
                <li>Memperbaiki kualitas layanan dan performa sistem platform kami.</li>
                <li>Menghubungi pelanggan jika terdapat kendala terkait ketersediaan stok atau pemesanan.</li>
            </ul>

            <h2>3. Perlindungan Data</h2>
            <p>Kami menerapkan langkah-langkah keamanan teknis untuk menjaga privasi akun kamu. Kami <strong>tidak akan pernah menjual, menyewakan, atau membagikan</strong> informasi pribadi pelanggan kepada pihak ketiga mana pun tanpa izin resmi.</p>

            <h2>4. Persetujuan</h2>
            <p>Dengan menggunakan website Yellow Mango's, kamu dengan ini menyetujui Kebijakan Privasi kami dan menyepakati seluruh ketentuannya.</p>
        </div>
    </div>

    <?php include 'footer.php'; ?>

</body>
</html>