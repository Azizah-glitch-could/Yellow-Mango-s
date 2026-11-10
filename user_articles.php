<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

$nama_user = $_SESSION['nama'] ?? 'Pelanggan Setia';
$query_artikel = mysqli_query($conn, "SELECT * FROM articles ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Favicon website -->
    <link rel="icon" type="image/png" href="uploads/logo_mangga_bersih.png">
    <title>Artikel & Promo - Yellow Mango's</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; background-color: #1B3022; color: #ffffff; margin: 0; padding: 0; }
        
        /* Navbar Sticky */
        .navbar-custom { position: sticky; top: 0; z-index: 1000; background-color: #1B3022; padding: 20px 50px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.1); box-sizing: border-box; }
        
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

        .container-custom { padding: 40px 50px; max-width: 1200px; margin: 0 auto; }
        
        /* Judul Halaman */
        .article-header-title { color: #FFC72C !important; font-weight: bold; font-size: 32px; margin-bottom: 10px; }
        .article-header-desc { color: #f1f5f9 !important; font-size: 15px; margin-bottom: 35px; }
        
        /* Grid & Kartu Artikel (Disamakan persis dengan halaman Menu) */
        .article-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; }
        .article-card { 
            background: #ffffff !important; 
            border: 2px solid #FFC72C !important; 
            padding: 20px; 
            border-radius: 12px; 
            display: flex; 
            flex-direction: column; 
            justify-content: space-between; 
            transition: transform 0.3s ease, box-shadow 0.3s ease; 
        }
        .article-card:hover { 
            transform: translateY(-8px); 
            box-shadow: 0 10px 20px rgba(0,0,0,0.3); 
        }
        .article-card img { width: 100%; height: 180px; object-fit: cover; border-radius: 8px; margin-bottom: 12px; }
        
        /* Teks dalam kartu dengan kontras tinggi (Warna gelap di atas putih) */
        .article-title { color: #1B3022 !important; font-size: 18px; font-weight: bold; margin: 10px 0 8px 0; }
        .article-text { color: #334155 !important; font-size: 14px; line-height: 1.6; margin-bottom: 15px; }
    </style>
</head>
<body>

    <!-- Header / Navbar -->
    <header class="navbar-custom">
        <a href="user_dashboard.php" class="logo">
            <img src="uploads/logo_mangga_bersih.png" alt="Logo">
            <span>Yellow Mango's</span>
        </a>
        <ul class="nav-links">
            <li><a href="user_dashboard.php">Beranda</a></li>
            <li><a href="user_menu.php">Menu</a></li>
            <li><a href="user_articles.php" class="active" style="color: #FFC72C; font-weight: bold;">Artikel & Promo</a></li>
            <li><a href="user_pesanan.php">Pesanan Saya</a></li>
            <li><a href="login.php" class="btn-keluar">Keluar (<?php echo htmlspecialchars($nama_user); ?>)</a></li>
        </ul>
    </header>

    <div class="container-custom">
        <h1 class="article-header-title">Artikel & Promo Menarik</h1>
        <p class="article-header-desc">Dapatkan tips kesehatan, informasi buah mangga pilihan, serta promo spesial dari Yellow Mango's.</p>

        <div class="article-grid">
            <?php
            if ($query_artikel && mysqli_num_rows($query_artikel) > 0) {
                while ($row = mysqli_fetch_assoc($query_artikel)) {
            ?>
                <div class="article-card">
                    <div>
                        <?php if (!empty($row['foto']) && file_exists('uploads/' . $row['foto'])) { ?>
                            <img src="uploads/<?php echo $row['foto']; ?>" alt="Artikel">
                        <?php } else { ?>
                            <div style="width: 100%; height: 180px; background: #e2e8f0; display: flex; align-items: center; justify-content: center; border-radius: 8px; margin-bottom: 12px; color: #64748b; font-size: 14px;">
                                📷 Yellow Mango's Info
                            </div>
                        <?php } ?>

                        <span class="badge bg-warning text-dark mb-1"><?= htmlspecialchars($row['kategori']); ?></span>
                        <div class="article-title"><?= htmlspecialchars($row['judul']); ?></div>
                        <p class="article-text"><?= nl2br(htmlspecialchars(substr($row['konten'], 0, 110))); ?>...</p>
                    </div>

                    <div>
                        <!-- Tombol Baca Selengkapnya / Link Luar -->
                        <?php if (!empty($row['link_sumber'])): ?>
                            <div class="mb-3">
                                <a href="<?= htmlspecialchars($row['link_sumber']); ?>" target="_blank" class="btn btn-sm btn-dark w-100 fw-bold" style="background-color: #1B3022; border-color: #1B3022; color: #FFC72C;">
                                    Baca Selengkapnya ↗
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div style="border-top: 1px solid #e2e8f0; pt-2; text-align: right;">
                            <small style="color: #64748b; font-size: 12px;">Dipublikasikan: <?= date('d M Y', strtotime($row['tanggal'])); ?></small>
                        </div>
                    </div>
                </div>
            <?php 
                }
            } else {
                echo "<div style='grid-column: 1/-1; background: #ffffff; border: 2px solid #FFC72C; padding: 40px; border-radius: 12px; text-align: center; color: #1B3022;'>
                        <h4 style='color: #1B3022; font-weight: bold; margin-bottom: 10px;'>Belum Ada Artikel</h4>
                        <p style='color: #475569; margin-bottom: 0;'>Belum ada artikel atau promo yang dipublikasikan. Silakan tambahkan lewat Panel Admin.</p>
                      </div>";
            }
            ?>
        </div>
    </div>

    <!-- Footer Dinamis -->
    <?php include 'footer.php'; ?>

</body>
</html>