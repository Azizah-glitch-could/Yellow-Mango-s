<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Tambah Kategori Baru
if (isset($_POST['tambah_kategori'])) {
    $nama_kategori = mysqli_real_escape_string($conn, $_POST['nama_kategori']);
    mysqli_query($conn, "INSERT INTO kategori (nama_kategori) VALUES ('$nama_kategori')");
    header("Location: admin_kategori.php");
    exit;
}

// Hapus Kategori
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM kategori WHERE id = $id");
    header("Location: admin_kategori.php");
    exit;
}

$result = mysqli_query($conn, "SELECT * FROM kategori");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="uploads/logo_mangga_bersih.png">
    <title>Kelola Kategori - Admin Yellow Mango's</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #1B3022; color: #ffffff; margin: 0; padding: 0; }
        .navbar { background-color: #1B3022; padding: 20px 50px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .logo { display: flex; align-items: center; gap: 12px; color: #FFC72C; font-weight: bold; font-size: 22px; text-decoration: none; }
        .logo img { height: 40px; width: 40px; object-fit: cover; border-radius: 50%; border: 2px solid #FFC72C; }
        .container { padding: 40px 50px; max-width: 800px; margin: 0 auto; }
        h1 { color: #FFC72C; margin-bottom: 20px; }
        .form-box { background: rgba(255,255,255,0.05); padding: 20px; border-radius: 8px; margin-bottom: 30px; border: 1px solid rgba(255,255,255,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input { width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc; background: rgba(255,255,255,0.1); color: white; box-sizing: border-box; }
        .btn { background: #FFC72C; color: #1B3022; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .btn:hover { background: #e5b225; }
        table { width: 100%; border-collapse: collapse; background: rgba(255,255,255,0.05); border-radius: 8px; overflow: hidden; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); }
        th { background-color: rgba(255, 199, 44, 0.2); color: #FFC72C; }
        .btn-danger { background: #d9534f; color: white; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 13px; }
    </style>
</head>
<body>

    <header class="navbar">
        <a href="admin_dashboard.php" class="logo">
            <img src="uploads/logo_mangga_bersih.png" alt="Logo">
            <span>Admin Yellow Mango's</span>
        </a>
        <a href="login.php" style="color: #d9534f; font-weight: bold; text-decoration: none;">Keluar</a>
    </header>

    <div class="container">
        <h1>Kelola Kategori Produk</h1>

        <!-- Form Tambah Kategori -->
        <div class="form-box">
            <form action="" method="POST">
                <div class="form-group">
                    <label>Nama Kategori Baru</label>
                    <input type="text" name="nama_kategori" required placeholder="Contoh: Smoothies, Topping, Snack">
                </div>
                <button type="submit" name="tambah_kategori" class="btn">Tambah Kategori</button>
            </form>
        </div>

        <!-- Tabel Daftar Kategori -->
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Kategori</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td>#CAT-0<?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['nama_kategori']); ?></td>
                    <td>
                        <a href="admin_kategori.php?hapus=<?php echo $row['id']; ?>" class="btn-danger" onclick="return confirm('Yakin ingin menghapus kategori ini?')">Hapus</a>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

</body>
</html>