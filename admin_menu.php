<?php
session_start();
include 'koneksi.php';

// Proses Hapus Menu
if (isset($_GET['hapus'])) {
    $id_menu = $_GET['hapus'];
    
    // Ambil nama file foto dulu untuk dihapus dari folder uploads
    $query_foto = mysqli_query($conn, "SELECT foto FROM menu WHERE id = '$id_menu'");
    $data_foto = mysqli_fetch_assoc($query_foto);
    $file_foto = $data_foto['foto'] ?? '';
    
    if (!empty($file_foto) && file_exists('uploads/' . $file_foto)) {
        unlink('uploads/' . $file_foto); // Hapus file fisik dari folder
    }
    
    // Hapus data dari database
    mysqli_query($conn, "DELETE FROM menu WHERE id = '$id_menu'");
    header("Location: admin_menu.php");
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$nama_admin = $_SESSION['nama'] ?? 'Admin Yellow Mango';

// Proses Tambah Menu & Upload Foto
if (isset($_POST['tambah_menu'])) {
    $nama_menu = mysqli_real_escape_string($conn, $_POST['nama_menu']);
    $harga     = mysqli_real_escape_string($conn, $_POST['harga']);
    $stok      = mysqli_real_escape_string($conn, $_POST['stok']);

    // Tangani File Foto
    $nama_file   = $_FILES['foto']['name'];
    $ukuran_file = $_FILES['foto']['size'];
    $error       = $_FILES['foto']['error'];
    $tmp_name    = $_FILES['foto']['tmp_name'];

    if ($error === 0) {
        // Pastikan folder uploads sudah ada, jika belum buat secara otomatis
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }

        // Beri nama unik agar tidak ada file yang saling menimpa (bentrok)
        $nama_file_baru = uniqid() . '-' . $nama_file;
        
        // Pindahkan file ke folder uploads
        if (move_uploaded_file($tmp_name, 'uploads/' . $nama_file_baru)) {
            // Simpan ke database jika upload fisik berhasil
            $query = "INSERT INTO menu (nama_menu, harga, stok, foto) VALUES ('$nama_menu', '$harga', '$stok', '$nama_file_baru')";
            mysqli_query($conn, $query);
        }
        
        header("Location: admin_menu.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Menu Admin - Yellow Mango's</title>
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
        
        .form-box { background: rgba(255,255,255,0.05); padding: 25px; border-radius: 12px; margin-bottom: 30px; border: 1px solid rgba(255,255,255,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: #FFC72C; font-weight: bold; }
        .form-group input { width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc; box-sizing: border-box; }
        
        .btn-submit { background: #FFC72C; color: #1B3022; padding: 10px 20px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .btn-submit:hover { background: #e5b225; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: rgba(255,255,255,0.05); border-radius: 8px; overflow: hidden; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); }
        th { background-color: rgba(255, 199, 44, 0.2); color: #FFC72C; }
        img.thumb { width: 50px; height: 50px; object-fit: cover; border-radius: 6px; }
        .btn-delete { background: #ef4444; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: bold; }
    </style>
</head>
<body>

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

    <div class="container">
        <h1>Manajemen Produk & Menu Toko</h1>
        <p style="color: #d1d5db; margin-bottom: 25px;">Tambah menu baru lengkap dengan foto produk asli untuk pelanggan.</p>

        <!-- Form Tambah Menu -->
        <div class="form-box">
            <h3 style="color: #FFC72C; margin-top: 0;">+ Tambah Menu Baru</h3>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Nama Produk / Menu:</label>
                    <input type="text" name="nama_menu" required placeholder="Contoh: Smoothies Mangga Original">
                </div>
                <div class="form-group">
                    <label>Harga (Rp):</label>
                    <input type="number" name="harga" required placeholder="Contoh: 18000">
                </div>
                <div class="form-group">
                    <label>Stok:</label>
                    <input type="number" name="stok" required placeholder="Contoh: 20">
                </div>
                <div class="form-group">
                    <label>Foto Produk:</label>
                    <input type="file" name="foto" accept="image/*" required style="background: white; color: black;">
                </div>
                <button type="submit" name="tambah_menu" class="btn-submit">Simpan Menu</button>
            </form>
        </div>

        <!-- Tabel Daftar Menu -->
        <h3 style="color: #FFC72C;">Daftar Menu Saat Ini</h3>
        <table>
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Nama Produk</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = mysqli_query($conn, "SELECT * FROM menu");
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                ?>
                    <tr>
                        <td>
                            <?php if (!empty($row['foto']) && file_exists('uploads/' . $row['foto'])) { ?>
                                <img src="uploads/<?php echo $row['foto']; ?>" class="thumb" alt="Foto">
                            <?php } else { ?>
                                <span style="font-size: 12px; color: #888;">Tanpa Foto</span>
                            <?php } ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['nama_menu']); ?></td>
                        <td>Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?></td>
                        <td><?php echo $row['stok']; ?> Porsi</td>
                        <td>
                            <a href="admin_menu.php?hapus=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Yakin ingin menghapus menu ini?')">Hapus</a>
                        </td>
                    </tr>
                <?php 
                    }
                } else {
                    echo "<tr><td colspan='5' style='text-align: center; color: #aaa;'>Belum ada menu yang ditambahkan.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

</body>
</html>