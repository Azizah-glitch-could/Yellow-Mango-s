<?php
session_start();
include 'koneksi.php';

// Pastikan yang akses adalah admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$success = '';
$error = '';

// Proses Simpan Pengaturan
if (isset($_POST['update_settings'])) {
    $nama_situs = mysqli_real_escape_string($conn, $_POST['nama_situs']);
    $telepon    = mysqli_real_escape_string($conn, $_POST['telepon']);
    $alamat     = mysqli_real_escape_string($conn, $_POST['alamat']);
    $tentang    = mysqli_real_escape_string($conn, $_POST['tentang']);

    // Cek apakah data setting sudah ada di database
    $cek = mysqli_query($conn, "SELECT * FROM settings LIMIT 1");
    if (mysqli_num_rows($cek) > 0) {
        // Update data yang ada
        $query = "UPDATE settings SET nama_situs='$nama_situs', telepon='$telepon', alamat='$alamat', tentang='$tentang' WHERE id=1";
    } else {
        // Insert jika belum ada
        $query = "INSERT INTO settings (id, nama_situs, telepon, alamat, tentang) VALUES (1, '$nama_situs', '$telepon', '$alamat', '$tentang')";
    }

    if (mysqli_query($conn, $query)) {
        $success = "Pengaturan website berhasil diperbarui!";
    } else {
        $error = "Terjadi kesalahan saat menyimpan pengaturan.";
    }
}

// Ambil data pengaturan saat ini
$result = mysqli_query($conn, "SELECT * FROM settings LIMIT 1");
$setting = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Favicon website -->
    <link rel="icon" type="image/png" href="uploads/logo_mangga_bersih.png">
    <title>Pengaturan Web - Admin Yellow Mango's</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #1B3022; color: #ffffff; margin: 0; padding: 0; }
        .navbar { background-color: #1B3022; padding: 20px 50px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .logo { display: flex; align-items: center; gap: 12px; color: #FFC72C; font-weight: bold; font-size: 22px; text-decoration: none; }
        .logo img { height: 40px; width: 40px; object-fit: cover; border-radius: 50%; border: 2px solid #FFC72C; }
        .container { padding: 40px 50px; max-width: 800px; margin: 0 auto; }
        h1 { color: #FFC72C; margin-bottom: 10px; }
        .form-box { background: rgba(255,255,255,0.05); padding: 30px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; color: #FFC72C; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.05); color: white; box-sizing: border-box; font-size: 15px; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #FFC72C; }
        .btn { background: #FFC72C; color: #1B3022; border: none; padding: 12px 25px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 16px; transition: background 0.2s; }
        .btn:hover { background: #e5b225; }
        .alert-success { background: #d1fae5; color: #065f46; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; text-align: center; }
        .alert-error { background: #fee2e2; color: #b91c1c; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; text-align: center; }
        .nav-admin { list-style: none; display: flex; gap: 20px; margin: 0; padding: 0; align-items: center; }
        .nav-admin a { color: white; text-decoration: none; font-size: 14px; }
        .nav-admin a:hover { color: #FFC72C; }
    </style>
</head>
<body>

    <header class="navbar">
        <a href="admin_dashboard.php" class="logo">
            <img src="uploads/logo_mangga_bersih.png" alt="Logo">
            <span>Admin Yellow Mango's</span>
        </a>
        <ul class="nav-admin">
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="admin_menu.php">Kelola Menu</a></li>
            <li><a href="admin_kategori.php">Kategori</a></li>
            <li><a href="admin_settings.php" style="color: #FFC72C; font-weight: bold;">Pengaturan Web</a></li>
            <li><a href="login.php" style="color: #d9534f; font-weight: bold;">Keluar</a></li>
        </ul>
    </header>

    <div class="container">
        <h1>Pengaturan Website</h1>
        <p style="color: #cbd5e1; margin-bottom: 30px;">Kelola informasi profil, kontak, dan deskripsi umum toko yang tampil di website.</p>

        <?php if (!empty($success)) { echo "<div class='alert-success'>$success</div>"; } ?>
        <?php if (!empty($error)) { echo "<div class='alert-error'>$error</div>"; } ?>

        <div class="form-box">
            <form action="" method="POST">
                <div class="form-group">
                    <label>Nama Situs / Toko</label>
                    <input type="text" name="nama_situs" value="<?php echo htmlspecialchars($setting['nama_situs'] ?? 'Yellow Mango\'s'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Nomor Telepon / WhatsApp Kontak</label>
                    <input type="text" name="telepon" value="<?php echo htmlspecialchars($setting['telepon'] ?? ''); ?>" required placeholder="Contoh: +62 812-xxxx-xxxx">
                </div>

                <div class="form-group">
                    <label>Alamat Fisik Toko</label>
                    <textarea name="alamat" rows="3" required placeholder="Alamat lengkap toko..."><?php echo htmlspecialchars($setting['alamat'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Tentang Singkat Toko (Deskripsi Footer / Beranda)</label>
                    <textarea name="textarea" name="tentang" rows="4" required placeholder="Deskripsi singkat tentang Yellow Mango's..."><?php echo htmlspecialchars($setting['tentang'] ?? ''); ?></textarea>
                </div>

                <button type="submit" name="update_settings" class="btn">Simpan Perubahan</button>
            </form>
        </div>
    </div>

</body>
</html>