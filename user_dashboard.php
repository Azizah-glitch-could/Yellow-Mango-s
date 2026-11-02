<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard User - Yellow Mango's</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h1 { color: #2D6A4F; }
        .logout { background: #D90429; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Selamat Datang, <?php echo htmlspecialchars($_SESSION['nama']); ?>! (Pelanggan)</h1>
        <p>Ini adalah halaman <b>Dashboard User</b> tempat pelanggan melihat katalog produk dan melakukan pemesanan Mango Smoothies favorit.</p>
        <hr>
        <h3>Menu Favorit Anda:</h3>
        <ul>
            <li>Mango Smoothies Original - Rp 18.000</li>
            <li>Avocado Mango Special - Rp 20.000</li>
        </ul>
        <a href="login.php" class="logout">Logout / Keluar</a>
    </div>
</body>
</html>