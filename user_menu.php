<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

$nama_user = $_SESSION['nama'] ?? 'Pelanggan Setia';

// Proses saat tombol Pesan Sekarang diklik
if (isset($_POST['pesan_menu'])) {
    $nama_menu = mysqli_real_escape_string($conn, $_POST['nama_menu']);
    $harga     = mysqli_real_escape_string($conn, $_POST['harga']);
    
    $query = "INSERT INTO pesanan (username, nama_menu, harga, status) VALUES ('$nama_user', '$nama_menu', '$harga', 'Menunggu Konfirmasi Admin')";
    mysqli_query($conn, $query);
    
    header("Location: user_pesanan.php");
    exit;
}

// Logika Pencarian Menu
$keyword = '';
if (isset($_GET['cari'])) {
    $keyword = mysqli_real_escape_string($conn, $_GET['cari']);
    $query_menu = "SELECT * FROM menu WHERE nama_menu LIKE '%$keyword%'";
} else {
    $query_menu = "SELECT * FROM menu";
}
$result = mysqli_query($conn, $query_menu);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Favicon website -->
    <link rel="icon" type="image/png" href="uploads/logo_mangga_bersih.png">
    <title>Katalog Menu - Yellow Mango's</title>
    <!-- Panggil Bootstrap CSS agar footer dan grid selaras -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; background-color: #1B3022; color: #ffffff; margin: 0; padding: 0; scroll-behavior: smooth; }
        
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
        h1 { color: #FFC72C; margin-bottom: 10px; font-weight: bold; }

        /* Kotak Pencarian Interaktif */
        .search-box { margin: 25px 0 35px; display: flex; gap: 10px; max-width: 500px; }
        .search-box input { flex: 1; padding: 12px 15px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.05); color: white; font-size: 15px; }
        .search-box input:focus { outline: none; border-color: #FFC72C; }
        .search-box button { background: #FFC72C; color: #1B3022; border: none; padding: 0 20px; border-radius: 8px; font-weight: bold; cursor: pointer; transition: background 0.2s; }
        .search-box button:hover { background: #e5b225; }

        /* Grid & Kartu Produk dengan Background Putih & Teks Kontras Tinggi */
        .menu-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 25px; }
        .menu-card { 
            background: #ffffff !important; 
            border: 2px solid #FFC72C !important; 
            padding: 20px; 
            border-radius: 12px; 
            text-align: center; 
            display: flex; 
            flex-direction: column; 
            justify-content: space-between; 
            transition: transform 0.3s ease, box-shadow 0.3s ease; 
        }
        .menu-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
        }
        .menu-card img { width: 100%; height: 180px; object-fit: cover; border-radius: 8px; margin-bottom: 12px; }
        .menu-card h3 { color: #1B3022 !important; margin: 10px 0; font-size: 18px; font-weight: bold; }
        .price { font-size: 16px; font-weight: bold; color: #166534 !important; margin-bottom: 15px; }

        /* Badge Stok Dinamis */
        .badge-stok { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; margin-bottom: 12px; }
        .stok-ada { background: rgba(22, 101, 52, 0.1); color: #166534; border: 1px solid rgba(22, 101, 52, 0.3); }
        .stok-tipis { background: rgba(185, 28, 28, 0.1); color: #b91c1c; border: 1px solid rgba(185, 28, 28, 0.3); }

        .btn-order { background: #FFC72C; color: #1B3022; border: none; padding: 10px; border-radius: 8px; font-weight: bold; width: 100%; cursor: pointer; transition: background 0.2s; }
        .btn-order:hover { background: #e5b225; }

        /* Tombol Scroll to Top */
        #scrollTopBtn { position: fixed; bottom: 30px; right: 30px; background: #FFC72C; color: #1B3022; border: none; width: 45px; height: 45px; border-radius: 50%; font-size: 18px; font-weight: bold; cursor: pointer; display: none; box-shadow: 0 4px 10px rgba(0,0,0,0.3); z-index: 99; transition: background 0.2s; }
        #scrollTopBtn:hover { background: #e5b225; }
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
            <li><a href="user_menu.php" class="active" style="color: #FFC72C; font-weight: bold;">Menu</a></li>
            <li><a href="user_articles.php">Artikel & Promo</a></li>
            <li><a href="user_pesanan.php">Pesanan Saya</a></li>
            <li><a href="login.php" class="btn-keluar">Keluar</a></li>
        </ul>
    </header>

    <div class="container-custom">
        <h1>Katalog Menu Yellow Mango's</h1>
        <p style="color: #f1f5f9; font-size: 15px;">Pilih smoothies segar favoritmu dan pesan langsung dari sini!</p>

        <!-- Fitur Pencarian Real-Time -->
        <form action="" method="GET" class="search-box">
            <input type="text" name="cari" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="Cari nama smoothies atau menu...">
            <button type="submit">Cari</button>
        </form>

        <div class="menu-grid">
            <?php
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
            ?>
                <div class="menu-card">
                    <div>
                        <?php if (!empty($row['foto']) && file_exists('uploads/' . $row['foto'])) { ?>
                            <img src="uploads/<?php echo $row['foto']; ?>" alt="Foto">
                        <?php } else { ?>
                            <div style="width: 100%; height: 180px; background: #e2e8f0; display: flex; align-items: center; justify-content: center; border-radius: 8px; margin-bottom: 12px; color: #64748b; font-size: 14px;">
                                📷 Tanpa Foto
                            </div>
                        <?php } ?>

                        <h3><?php echo htmlspecialchars($row['nama_menu']); ?></h3>
                        <div class="price">Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?></div>
                        
                        <!-- Badge Stok Dinamis -->
                        <?php if ($row['stok'] > 5) { ?>
                            <span class="badge-stok stok-ada">Stok Tersedia (<?php echo $row['stok']; ?>)</span>
                        <?php } else if ($row['stok'] > 0) { ?>
                            <span class="badge-stok stok-tipis">Stok Menipis (Sisa <?php echo $row['stok']; ?>)</span>
                        <?php } else { ?>
                            <span class="badge-stok" style="background: #cbd5e1; color: #475569;">Habis</span>
                        <?php } ?>
                    </div>

                    <!-- Form Tombol Pesan -->
                    <form action="" method="POST">
                        <input type="hidden" name="nama_menu" value="<?php echo htmlspecialchars($row['nama_menu']); ?>">
                        <input type="hidden" name="harga" value="<?php echo $row['harga']; ?>">
                        <button type="submit" name="pesan_menu" class="btn-order" <?php echo ($row['stok'] <= 0) ? 'disabled style="background: #cbd5e1; color: #94a3b8; cursor: not-allowed;"' : ''; ?>>
                            <?php echo ($row['stok'] > 0) ? 'Pesan Sekarang' : 'Stok Kosong'; ?>
                        </button>
                    </form>
                </div>
            <?php 
                }
            } else {
                echo "<p style='color: #f1f5f9; grid-column: 1/-1; text-align: center; padding: 40px;'>Menu yang kamu cari tidak ditemukan.</p>";
            }
            ?>
        </div>
    </div>

    <!-- Tombol Scroll to Top -->
    <button onclick="scrollToTop()" id="scrollTopBtn" title="Kembali ke atas">↑</button>

    <script>
        window.onscroll = function() {
            let btn = document.getElementById("scrollTopBtn");
            if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
                btn.style.display = "block";
            } else {
                btn.style.display = "none";
            }
        };

        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    </script>

    <!-- Footer Dinamis -->
    <?php include 'footer.php'; ?>

</body>
</html>