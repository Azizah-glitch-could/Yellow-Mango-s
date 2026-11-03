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
    <title>Pesanan Saya - Yellow Mango's</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #1B3022; color: #ffffff; margin: 0; padding: 0; }
        .navbar { background-color: #1B3022; padding: 20px 50px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .logo { font-size: 22px; font-weight: bold; color: #FFC72C; text-decoration: none; }
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
        <a href="user_dashboard.php" class="logo">Yellow Mango's</a>
        <ul class="nav-links">
            <li><a href="user_dashboard.php">Beranda</a></li>
            <li><a href="user_menu.php">Menu</a></li>
            <li><a href="user_pesanan.php" class="active">Pesanan Saya</a></li>
            <li><a href="login.php" class="btn-keluar">Keluar (<?php echo htmlspecialchars($nama_user); ?>)</a></li>
        </ul>
    </header>

    <div class="container">
        <h1>Riwayat Pesanan Saya</h1>
        <p style="color: #d1d5db; margin-bottom: 25px;">Daftar produk yang telah kamu pesan dapat dipantau di sini.</p>

        <table>
            <thead>
                <tr>
                    <th>ID Pesanan</th>
                    <th>Menu</th>
                    <th>Total Harga</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>#ORD-001</td>
                    <td>Smoothies Mangga Original</td>
                    <td>Rp 18.000</td>
                    <td><span class="badge">Menunggu Konfirmasi Admin</span></td>
                </tr>
            </tbody>
        </table>
    </div>

</body>
</html>