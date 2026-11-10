<?php
session_start();
include 'koneksi.php';

// Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$nama_admin = $_SESSION['nama'] ?? 'Admin Yellow Mango';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Masuk Admin - Yellow Mango's</title>
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
        
        .container { 
            padding: 40px 50px; 
            max-width: 1200px; 
            margin: 0 auto; 
        }
        h1 { 
            color: #FFC72C; 
            margin-bottom: 10px; 
        }

        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px; 
            background: rgba(255,255,255,0.05); 
            border-radius: 8px; 
            overflow: hidden; 
        }
        th, td { 
            padding: 15px; 
            text-align: left; 
            border-bottom: 1px solid rgba(255,255,255,0.1); 
        }
        th { 
            background-color: rgba(255, 199, 44, 0.2); 
            color: #FFC72C; 
        }
        .badge-pending { 
            background: #f59e0b; 
            color: #1B3022; 
            padding: 5px 10px; 
            border-radius: 6px; 
            font-size: 12px; 
            font-weight: bold; 
        }
    </style>
</head>
<body>

    <!-- Navbar Admin -->
    <header class="navbar">
        <a href="admin_dashboard.php" class="logo">Yellow Mango's (Admin)</a>
       <ul class="nav-links" style="list-style: none; display: flex; gap: 25px; margin: 0; padding: 0; align-items: center;">
    <li><a href="admin_dashboard.php" style="color: #FFC72C; font-weight: bold; text-decoration: none;">Beranda Admin</a></li>
    <li><a href="admin_menu.php" style="color: white; text-decoration: none;">Kelola Menu</a></li>
    <li><a href="admin_articles.php" style="color: white; text-decoration: none;">Kelola Artikel</a></li>
    <li><a href="admin_pesanan.php" style="color: white; text-decoration: none;">Pesanan Masuk</a></li>
    <li><a href="login.php" class="btn-keluar">Keluar</a></li>
</ul>
    </header>

    <!-- Konten Pesanan Masuk -->
    <div class="container">
        <h1>Daftar Pesanan Masuk</h1>
        <p style="color: #d1d5db; margin-bottom: 25px;">Pantau semua pesanan yang masuk dari pelanggan di sini.</p>

        <table>
            <thead>
                <tr>
                    <th>ID Pesanan</th>
                    <th>Nama Pelanggan</th>
                    <th>Menu Dipesan</th>
                    <th>Total Harga</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <!-- Contoh baris statis / placeholder data pesanan -->
                <tr>
                    <td>#ORD-001</td>
                    <td>User Setia</td>
                    <td>Smoothies Mangga Original</td>
                    <td>Rp 18.000</td>
                    <td><span class="badge-pending">Menunggu Konfirmasi</span></td>
                    <td>
                        <button style="background: #4ade80; color: #1B3022; border: none; padding: 6px 12px; border-radius: 6px; font-weight: bold; cursor: pointer;">Terima</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

</body>
</html>