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
    <title>Pesanan Saya - Yellow Mango's</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #1B3022; color: #ffffff; margin: 0; padding: 0; }
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
        
        .container { padding: 40px 50px; max-width: 1200px; margin: 0 auto; }
        h1 { color: #FFC72C; margin-bottom: 10px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: rgba(255,255,255,0.05); border-radius: 8px; overflow: hidden; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); }
        th { background-color: rgba(255, 199, 44, 0.2); color: #FFC72C; }
        .badge { background: #f59e0b; color: #1B3022; padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: bold; }
    </style>
</head>
<body>

    <header class="navbar">
        <!-- Logo dan Teks Yellow Mango's -->
        <a href="user_dashboard.php" class="logo">
            <img src="uploads/logo_mangga_bersih.png" alt="Logo">
            <span>Yellow Mango's</span>
        </a>
        
        <ul class="nav-links">
            <li><a href="user_dashboard.php">Beranda</a></li>
            <li><a href="user_menu.php">Menu</a></li>
            <li><a href="user_articles.php">Artikel & Promo</a></li>
            <li><a href="user_pesanan.php" style="color: #FFC72C; font-weight: bold;">Pesanan Saya</a></li>
            <li><a href="login.php" class="btn-keluar">Keluar</a></li>
        </ul>
    </header>

    <div class="container">
        <h1>Riwayat Pesanan Saya</h1>
        <p style="color: #d1d5db; margin-bottom: 25px;">Daftar produk yang telah kamu pesan dipantau secara real-time di sini.</p>

        <table>
            <thead>
                <tr>
                    <th>ID Pesanan</th>
                    <th>Menu Dipesan</th>
                    <th>Total Harga</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = mysqli_query($conn, "SELECT * FROM pesanan WHERE username = '$nama_user'");
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                ?>
                    <tr>
                        <td>#ORD-00<?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['nama_menu']); ?></td>
                        <td>Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?></td>
                        <td><span class="badge"><?php echo htmlspecialchars($row['status']); ?></span></td>
                    </tr>
                <?php 
                    }
                } else {
                    echo "<tr><td colspan='4' style='text-align: center; color: #aaa;'>Belum ada pesanan aktif. Yuk pesan menu favoritmu sekarang!</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

</body>
</html>