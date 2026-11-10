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
    <title>Tentang Kami - Yellow Mango's</title>
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
        p { color: #d1d5db; font-size: 15px; }
        ul { color: #d1d5db; font-size: 15px; padding-left: 20px; }
        li { margin-bottom: 8px; }
        .highlight { color: #4ade80; font-weight: bold; }
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
        <h1>Tentang Yellow Mango's</h1>
        
        <div class="content-box">
            <p><strong>Yellow Mango's</strong> hadir sebagai destinasi utama bagi para pencinta kuliner dan minuman segar di Makassar. Berawal dari semangat untuk menyajikan olahan buah mangga lokal kualitas terbaik, kami berkomitmen menghadirkan kelezatan alami di setiap tegukan dan gigitan.</p>

            <h2>Misi Kami</h2>
            <p>Menyajikan olahan smoothies mangga serta aneka varian menu lezat yang higienis, bergizi, dan terjangkau bagi seluruh lapisan masyarakat, mulai dari mahasiswa hingga keluarga.</p>

            <h2>Nilai Utama Kami</h2>
            <ul>
                <li><span class="highlight">100% Kualitas Buah Segar:</span> Kami hanya mengolah mangga pilihan yang dipetik dalam kondisi matang sempurna.</li>
                <li><span class="highlight">Standar Kebersihan Tinggi:</span> Setiap sajian dibuat dengan proses pemuatan dan pengemasan yang higienis.</li>
                <li><span class="highlight">Pelayanan Cepat & Ramah:</span> Kepuasan serta kenyamanan pelanggan dalam memesan adalah prioritas utama kami.</li>
            </ul>

            <h2>Lokasi Operasional</h2>
            <p>Saat ini kami melayani pemesanan langsung serta pengantaran wilayah Makassar, Sulawesi Selatan. Nikmati kemudahan pesan menu favoritmu langsung melalui aplikasi website resmi kami!</p>
        </div>
    </div>

    <?php include 'footer.php'; ?>

</body>
</html>