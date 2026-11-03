<?php
session_start();
include 'koneksi.php';

// Pastikan hanya user yang bisa akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

$nama_user = $_SESSION['nama'] ?? 'User Setia';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pelanggan - Yellow Mango's</title>
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
            padding: 50px;
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            min-height: 70vh;
        }
        .hero-content {
            max-width: 600px;
        }
        h1 {
            font-size: 45px;
            color: #ffffff;
            line-height: 1.2;
            margin-bottom: 20px;
        }
        .user-greeting {
            background: rgba(255, 199, 44, 0.15);
            border-left: 4px solid #FFC72C;
            padding: 12px 18px;
            border-radius: 6px;
            margin-bottom: 30px;
            font-size: 15px;
            color: #FFC72C;
            display: inline-block;
        }
        .btn-pesan {
            display: inline-block;
            background: #FFC72C;
            color: #1B3022;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            font-size: 16px;
            margin-top: 15px;
        }
        .btn-pesan:hover {
            background: #e5b225;
        }
        .hero-image {
            width: 350px;
            height: 350px;
            background: #FFC72C;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .hero-image img {
            width: 180px;
        }
    </style>
</head>
<body>

    <!-- Navbar Pelanggan -->
    <header class="navbar">
        <a href="user_dashboard.php" class="logo">Yellow Mango's</a>
        <ul class="nav-links">
            <li><a href="user_dashboard.php" class="active">Beranda</a></li>
            <li><a href="user_menu.php">Menu</a></li>
            <li><a href="user_menu.php">Pesanan Saya</a></li>
            <li><a href="login.php" class="btn-keluar">Keluar</a></li>
        </ul>
    </header>

    <!-- Konten Utama Dashboard Pelanggan -->
    <div class="container">
        <div class="hero-content">
            <div class="user-greeting">
                👋 Halo, <b><?php echo htmlspecialchars($nama_user); ?></b>! Selamat datang di akun pelangganmu.
            </div>
            <h1>Segarkan harimu dengan pesan berbagai produk menyegarkan dari kami!!!</h1>
            <p style="color: #d1d5db; font-size: 16px; margin-bottom: 25px;">Banyak hal menyegarkan, yang bisa dipesan hanya dari satu website.</p>
            
            <!-- Tombol Pesan Disini yang sudah interaktif -->
            <a href="user_menu.php" class="btn-pesan">Pesan Disini</a>
        </div>

        <div class="hero-image">
            <div style="font-size: 120px; text-align: center;">🥭</div>
        </div>
    </div>

</body>
</html>