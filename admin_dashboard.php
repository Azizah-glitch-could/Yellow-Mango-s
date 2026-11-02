<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
include 'koneksi.php';
// Ambil jumlah total user terdaftar dari database lokal
$query_users = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
$data_users = mysqli_fetch_assoc($query_users);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - Yellow Mango's</title>
    <style>
        body { font-family: Arial, sans-serif; background: #1B4332; color: white; margin: 0; padding: 20px; }
        .container { background: #2D6A4F; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.3); }
        h1 { color: #FFB703; }
        .card { background: #40916C; padding: 15px; border-radius: 6px; margin: 15px 0; display: inline-block; }
        .logout { background: #D90429; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Dashboard Admin - Yellow Mango's</h1>
        <p>Halo, <b><?php echo htmlspecialchars($_SESSION['nama']); ?></b>. Anda memiliki hak akses penuh sebagai pengelola sistem.</p>
        
        <div class="card">
            <h3>Total Pengguna Terdaftar di Database:</h3>
            <p style="font-size: 24px; font-weight: bold; margin: 0;"><?php echo $data_users['total']; ?> Akun</p>
        </div>
        <br>
        <a href="login.php" class="logout">Logout / Keluar</a>
    </div>
</body>
</html>