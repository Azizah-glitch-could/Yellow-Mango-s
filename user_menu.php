<?php
session_start();
include 'koneksi.php';

// Pastikan hanya user yang bisa akses
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
    <title>Menu Pelanggan - Yellow Mango's</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background-color: #1B3022; 
            color: #ffffff; 
            margin: 0; 
            padding: 0; 
        }
        .navbar { 
            background-color: #1B3022; 
            padding: 20px 50px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            border-bottom: 1px solid rgba(255,255,255,0.1); 
        }
        .logo { 
            font-size: 22px; 
            font-weight: bold; 
            color: #FFC72C; 
            text-decoration: none; 
        }
        .nav-links { 
            list-style: none; 
            display: flex; 
            gap: 25px; 
            margin: 0; 
            padding: 0; 
            align-items: center; 
        }
        .nav-links a { 
            color: white; 
            text-decoration: none; 
            font-size: 15px; 
        }
        .nav-links a:hover, .nav-links a.active { 
            color: #FFC72C; 
        }
        .btn-keluar { 
            background-color: #d9534f; 
            color: white; 
            padding: 6px 14px; 
            border-radius: 8px; 
            text-decoration: none; 
            font-weight: bold; 
            font-size: 14px; 
        }
        .btn-keluar:hover { 
            background-color: #c9302c; 
        }
        
        .container { 
            padding: 40px 50px; 
            max-width: 1200px; 
            margin: 0 auto; 
        }
        h1 { 
            color: #FFC72C; 
            margin-bottom: 10px; 
        }
        
        .menu-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); 
            gap: 25px; 
            margin-top: 30px; 
        }
        .menu-card { 
            background: rgba(255, 255, 255, 0.05); 
            border: 1px solid rgba(255, 255, 255, 0.1); 
            padding: 20px; 
            border-radius: 12px; 
            text-align: center; 
            display: flex; 
            flex-direction: column; 
            justify-content: space-between; 
        }
        .menu-card img { 
            width: 100%; 
            height: 180px; 
            object-fit: cover; 
            border-radius: 8px; 
            margin-bottom: 12px; 
        }
        .menu-card h3 { 
            color: #FFC72C; 
            margin: 10px 0; 
            font-size: 18px; 
        }
        .price { 
            font-size: 16px; 
            font-weight: bold; 
            color: #4ade80; 
            margin-bottom: 8px; 
        }
        .btn-order { 
            background: #FFC72C; 
            color: #1B3022; 
            border: none; 
            padding: 10px; 
            border-radius: 8px; 
            font-weight: bold; 
            width: 100%; 
            cursor: pointer; 
        }
        .btn-order:hover { 
            background: #e5b225; 
        }
    </style>
</head>
<body>

    <!-- Navbar Pelanggan -->
    <header class="navbar">
        <a href="user_dashboard.php" class="logo">Yellow Mango's</a>
        <ul class="nav-links">
            <li><a href="user_dashboard.php">Beranda</a></li>
            <li><a href="user_menu.php" class="active">Menu</a></li>
            <li><a href="user_menu.php">Pesanan Saya</a></li>
            <li><a href="login.php" class="btn-keluar">Keluar (<?php echo htmlspecialchars($nama_user); ?>)</a></li>
        </ul>
    </header>

    <!-- Konten Katalog Menu -->
    <div class="container">
        <h1>Katalog Menu Yellow Mango's</h1>
        <p style="color: #d1d5db;">Pilih smoothies segar favoritmu langsung dari sini!</p>

        <div class="menu-grid">
            <?php
            $result = mysqli_query($conn, "SELECT * FROM menu");
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
            ?>
                <div class="menu-card">
                    <div>
                        <?php if (!empty($row['foto']) && file_exists('uploads/' . $row['foto'])) { ?>
                            <img src="uploads/<?php echo $row['foto']; ?>" alt="<?php echo htmlspecialchars($row['nama_menu']); ?>">
                        <?php } else { ?>
                            <div style="width: 100%; height: 180px; background: rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; border-radius: 8px; margin-bottom: 12px; color: #aaa; font-size: 14px;">
                                📷 Foto Tidak Ditemukan
                            </div>
                        <?php } ?>

                        <h3><?php echo htmlspecialchars($row['nama_menu']); ?></h3>
                        <div class="price">Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?></div>
                        <p style="font-size: 13px; color: #ccc; margin-bottom: 15px;">Stok: <?php echo $row['stok']; ?> Porsi Tersedia</p>
                    </div>

                    <button class="btn-order" onclick="alert('Pesanan untuk menu <?php echo htmlspecialchars($row['nama_menu']); ?> berhasil dibuat!')">Pesan Sekarang</button>
                </div>
            <?php 
                }
            } else {
                echo "<p style='color: #aaa;'>Belum ada menu yang tersedia saat ini. Silakan hubungi admin.</p>";
            }
            ?>
        </div>
    </div>

</body>
</html>