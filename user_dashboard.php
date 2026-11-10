<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

$nama_user = $_SESSION['nama'] ?? 'Pelanggan Setia';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Favicon website -->
    <link rel="icon" type="image/png" href="uploads/logo_mangga_bersih.png">
    <title>Beranda - Yellow Mango's</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #1B3022; color: #ffffff; margin: 0; padding: 0; }
        
        /* Navbar Sticky */
        .navbar { 
            position: sticky;
            top: 0;
            z-index: 1000;
            background-color: #1B3022; 
            padding: 20px 50px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            border-bottom: 1px solid rgba(255,255,255,0.1); 
            box-sizing: border-box;
        }
        
        /* Styling Logo di Navbar agar berdampingan rapi */
        .logo { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            text-decoration: none; 
        }
        .logo img { 
            height: 40px; 
            width: 40px; 
            object-fit: cover; 
            border-radius: 50%; 
            border: 2px solid #FFC72C;
        }
        .logo span { 
            font-size: 22px; 
            font-weight: bold; 
            color: #FFC72C; 
        }

        .nav-links { list-style: none; display: flex; gap: 25px; margin: 0; padding: 0; align-items: center; }
        .nav-links a { color: white; text-decoration: none; font-size: 15px; }
        .nav-links a:hover, .nav-links a.active { color: #FFC72C; }
        .btn-keluar { background-color: #d9534f; color: white; padding: 6px 14px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 14px; }

        /* Hero Banner */
        .hero { background: linear-gradient(rgba(27, 48, 34, 0.85), rgba(27, 48, 34, 0.95)), url('assets/hero-bg.jpg') center/cover; padding: 80px 50px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .hero h1 { font-size: 40px; color: #FFC72C; margin-bottom: 15px; }
        .hero p { font-size: 18px; color: #f1f5f9; max-width: 700px; margin: 0 auto 30px; line-height: 1.6; }
        .btn-cta { background: #FFC72C; color: #1B3022; padding: 12px 30px; border-radius: 8px; font-weight: bold; text-decoration: none; font-size: 16px; display: inline-block; transition: background 0.2s; }
        .btn-cta:hover { background: #e5b225; }

        /* Container Utama */
        .container { padding: 50px; max-width: 1200px; margin: 0 auto; }
        .section-title { color: #FFC72C; font-size: 26px; margin-bottom: 10px; text-align: center; font-weight: bold; }
        .section-desc { text-align: center; color: #f1f5f9; margin-bottom: 40px; font-size: 15px; }

        /* Grid Produk & Artikel */
        .menu-grid, .article-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 25px; }
        .menu-card { 
            background: rgba(27, 48, 34, 0.03); 
            border: 2px solid #FFC72C; 
            padding: 20px; 
            border-radius: 12px; 
            text-align: center; 
            transition: transform 0.2s;
        }
        .menu-card:hover { transform: translateY(-5px); }
        .menu-card img { width: 100%; height: 160px; object-fit: cover; border-radius: 8px; margin-bottom: 12px; }
        .menu-card h3 { color: #1B3022; margin: 10px 0; font-size: 18px; font-weight: bold; }
        .price { font-size: 16px; font-weight: bold; color: #166534; margin-bottom: 15px; }

        /* Card Artikel di Beranda */
        .article-card { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between; }
        .article-card img { width: 100%; height: 150px; object-fit: cover; }
        .article-body { padding: 15px; }
        .article-title { color: #FFC72C; font-size: 16px; font-weight: bold; margin-bottom: 8px; }
        .article-text { color: #f1f5f9; font-size: 13px; line-height: 1.5; }

        /* Testimoni */
        .testimonial-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; margin-top: 20px; }
        .testimonial-card { 
            background: #1B3022; 
            border-left: 5px solid #FFC72C; 
            border: 1px solid rgba(255,255,255,0.1);
            padding: 25px; 
            border-radius: 8px; 
            font-style: italic; 
            color: #f8fafc; 
        }
        .testimonial-card span { display: block; margin-top: 15px; font-weight: bold; color: #FFC72C; font-style: normal; font-size: 14px; }
    </style>
</head>
<body>

    <!-- Header / Navbar Sticky -->
    <header class="navbar">
        <a href="user_dashboard.php" class="logo">
            <img src="uploads/logo_mangga_bersih.png" alt="Logo">
            <span>Yellow Mango's</span>
        </a>
        <ul class="nav-links">
            <li><a href="user_dashboard.php" class="active" style="color: #FFC72C; font-weight: bold;">Beranda</a></li>
            <li><a href="user_menu.php">Menu</a></li>
            <li><a href="user_articles.php">Artikel & Promo</a></li>
            <li><a href="user_pesanan.php">Pesanan Saya</a></li>
            <li><a href="login.php" class="btn-keluar">Keluar </a></li>
        </ul>
    </header>

    <!-- Hero Banner -->
    <section class="hero">
        <h1>Selamat Datang, <?php echo htmlspecialchars($nama_user); ?>! 👋</h1>
        <p>Nikmati sensasi kesegaran smoothies mangga pilihan dan aneka menu lezat kualitas premium langsung dari dapur Yellow Mango's Makassar.</p>
        <a href="user_menu.php" class="btn-cta">Pesan Menu Favoritmu</a>
    </section>

    <div class="container">
        
        <!-- Keunggulan -->
        <div style="background-color: #1B3022; padding: 40px; border-radius: 12px; text-align: center; margin-bottom: 60px; border: 1px solid rgba(255,255,255,0.1);">
            <h2 class="section-title">Kenapa Memilih Yellow Mango's?</h2>
            <p class="section-desc">Kami berkomitmen menyajikan sajian terbaik dengan bahan-bahan segar setiap harinya.</p>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; text-align: left;">
                <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.08);">
                    <h4 style="color: #FFC72C; margin-top: 0;">🥭 100% Buah Segar Pilihan</h4>
                    <p style="font-size: 14px; color: #f1f5f9; margin-bottom: 0; line-height: 1.5;">Dipetik langsung dari petani lokal berkualitas tinggi untuk menjaga cita rasa alami.</p>
                </div>
                <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.08);">
                    <h4 style="color: #FFC72C; margin-top: 0;">⚡ Proses Cepat & Higienis</h4>
                    <p style="font-size: 14px; color: #f1f5f9; margin-bottom: 0; line-height: 1.5;">Disiapkan dengan standar kebersihan tinggi agar aman dan sehat dikonsumsi kapan saja.</p>
                </div>
                <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.08);">
                    <h4 style="color: #FFC72C; margin-top: 0;">💰 Harga Terjangkau</h4>
                    <p style="font-size: 14px; color: #f1f5f9; margin-bottom: 0; line-height: 1.5;">Kenikmatan premium dengan harga yang ramah di kantong mahasiswa dan pelajar.</p>
                </div>
            </div>
        </div>

        <!-- Produk Terpopuler -->
        <div style="background-color: #ffffff; padding: 40px; border-radius: 12px; margin-bottom: 60px; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
            <div style="text-align: center; margin-bottom: 30px;">
                <h2 class="section-title" style="color: #1B3022;">Produk Terpopuler Kami</h2>
                <p style="color: #4b5563; font-size: 15px; margin: 0;">Beberapa menu andalan yang paling sering dipesan oleh pelanggan.</p>
            </div>
            
            <div class="menu-grid">
                <?php
                $result_menu = mysqli_query($conn, "SELECT * FROM menu LIMIT 4");
                if ($result_menu && mysqli_num_rows($result_menu) > 0) {
                    while ($row_m = mysqli_fetch_assoc($result_menu)) {
                ?>
                    <div class="menu-card">
                        <?php if (!empty($row_m['foto']) && file_exists('uploads/' . $row_m['foto'])) { ?>
                            <img src="uploads/<?php echo $row_m['foto']; ?>" alt="Menu">
                        <?php } else { ?>
                            <div style="width: 100%; height: 160px; background: #e2e8f0; display: flex; align-items: center; justify-content: center; border-radius: 8px; margin-bottom: 12px; color: #64748b; font-size: 13px;">
                                📷 Tanpa Foto
                            </div>
                        <?php } ?>
                        <h3><?php echo htmlspecialchars($row_m['nama_menu']); ?></h3>
                        <div class="price">Rp <?php echo number_format($row_m['harga'], 0, ',', '.'); ?></div>
                        <a href="user_menu.php" style="background: #1B3022; color: #FFC72C; padding: 8px 15px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 13px; display: inline-block;">Lihat Menu</a>
                    </div>
                <?php 
                    }
                } else {
                    echo "<p style='color: #4b5563; text-align: center; grid-column: 1/-1;'>Belum ada menu produk.</p>";
                }
                ?>
            </div>
        </div>

        <!-- Artikel & Promo Terbaru di Beranda -->
        <div style="background-color: #1B3022; padding: 40px; border-radius: 12px; margin-bottom: 60px; border: 1px solid rgba(255,255,255,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h2 class="section-title" style="margin: 0; text-align: left;">📰 Artikel & Promo Terbaru</h2>
                <a href="user_articles.php" style="color: #FFC72C; text-decoration: none; font-weight: bold; font-size: 14px;">Lihat Semua →</a>
            </div>

            <div class="article-grid">
                <?php
                $result_art = mysqli_query($conn, "SELECT * FROM articles ORDER BY id DESC LIMIT 3");
                if ($result_art && mysqli_num_rows($result_art) > 0) {
                    while ($row_a = mysqli_fetch_assoc($result_art)) {
                ?>
                    <div class="article-card">
                        <div>
                            <?php if (!empty($row_a['foto']) && file_exists('uploads/' . $row_a['foto'])) { ?>
                                <img src="uploads/<?php echo $row_a['foto']; ?>" alt="Artikel">
                            <?php } else { ?>
                                <div style="width: 100%; height: 150px; background: rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; color: #aaa; font-size: 13px;">
                                    📷 Yellow Mango's
                                </div>
                            <?php } ?>
                            <div class="article-body">
                                <span class="badge bg-warning text-dark mb-2" style="font-size: 11px;"><?= htmlspecialchars($row_a['kategori']); ?></span>
                                <div class="article-title"><?= htmlspecialchars($row_a['judul']); ?></div>
                                <p class="article-text"><?= nl2br(htmlspecialchars(substr($row_a['konten'], 0, 90))); ?>...</p>
                            </div>
                        </div>
                        <div class="p-3 pt-0">
                            <small style="color: #cbd5e1; font-size: 12px;"><?= date('d M Y', strtotime($row_a['tanggal'])); ?></small>
                        </div>
                    </div>
                <?php 
                    }
                } else {
                    echo "<p style='color: #f1f5f9; text-align: center; grid-column: 1/-1;'>Belum ada artikel atau promo yang dipublikasikan.</p>";
                }
                ?>
            </div>
        </div>

        <!-- Testimoni -->
        <div style="margin-bottom: 20px; text-align: center;">
            <h2 class="section-title">Apa Kata Mereka?</h2>
            <p class="section-desc">Testimoni nyata dari pelanggan setia Yellow Mango's.</p>
            
            <div class="testimonial-grid">
                <div class="testimonial-card">
                    "Smoothies mangganya bener-bener segar dan kejunya berasa banget! Sangat pas buat nemenin waktu santai di siang hari."
                    <span>— Rina A., Mahasiswi</span>
                </div>
                <div class="testimonial-card">
                    "Pelayanannya cepat dan rasanya konsisten enak. Wajib banget beli minimal seminggu sekali!"
                    <span>— Ahmad F., Warga Makassar</span>
                </div>
            </div>
        </div>

    </div>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

</body>
</html>