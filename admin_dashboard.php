<?php
session_start();
include 'koneksi.php';

// Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$nama_admin = $_SESSION['nama'] ?? 'Admin Yellow Mango';

// Hitung total pengguna terdaftar di database
$query_users = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
$data_users = mysqli_fetch_assoc($query_users);
$total_users = $data_users['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Yellow Mango's</title>
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
        }
        h1 {
            font-size: 38px;
            color: #FFC72C;
            margin-bottom: 10px;
        }
        .admin-greeting {
            background: rgba(255, 199, 44, 0.15);
            border-left: 4px solid #FFC72C;
            padding: 12px 18px;
            border-radius: 6px;
            margin-bottom: 30px;
            font-size: 15px;
            color: #FFC72C;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 25px;
            border-radius: 12px;
        }
        .stat-card h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
            color: #d1d5db;
        }
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #FFC72C;
        }
    </style>
</head>
<body>

    <!-- Navbar Asli Admin -->
    <header class="navbar">
        <a href="#" class="logo">Yellow Mango's (Admin)</a>
       <ul class="nav-links" style="list-style: none; display: flex; gap: 25px; margin: 0; padding: 0; align-items: center;">
    <li><a href="admin_dashboard.php" style="color: #FFC72C; font-weight: bold; text-decoration: none;">Beranda Admin</a></li>
    <li><a href="admin_menu.php" style="color: white; text-decoration: none;">Kelola Menu</a></li>
    <li><a href="admin_articles.php" style="color: white; text-decoration: none;">Kelola Artikel</a></li>
    <li><a href="admin_pesanan.php" style="color: white; text-decoration: none;">Pesanan Masuk</a></li>
    <li><a href="login.php" class="btn-keluar">Keluar</a></li>
</ul>
    </header>

    <!-- Konten Utama Dashboard Admin -->
    <div class="container">
        <h1>Dashboard Pemilik Toko</h1>
        <div class="admin-greeting">
            👋 Halo, <b><?php echo htmlspecialchars($nama_admin); ?></b>. Anda memiliki hak akses penuh sebagai pengelola sistem dan inventaris toko.
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Pengguna Terdaftar di Database</h3>
                <div class="stat-value"><?php echo $total_users; ?> Akun</div>
            </div>
            
            <div class="stat-card">
                <h3>Status Sistem Toko</h3>
                <div class="stat-value" style="font-size: 20px; color: #4ade80; margin-top: 5px;">🟢 Aktif & Terhubung</div>
            </div>
        </div>
    </div>

</body>
</html>