<?php
session_start();
include 'koneksi.php';

$error = '';
$success = '';

if (isset($_POST['register'])) {
    $nama     = mysqli_real_escape_string($conn, $_POST['nama']);
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $role     = mysqli_real_escape_string($conn, $_POST['role']); // Menangkap pilihan role dari form

    // Validasi pilihan role agar aman
    if ($role !== 'user' && $role !== 'admin') {
        $role = 'user';
    }

    // Cek apakah email sudah terdaftar
    $cek_email = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email'");
    if (mysqli_num_rows($cek_email) > 0) {
        $error = "Email sudah terdaftar! Gunakan email lain.";
    } else {
        // Simpan user baru dengan role yang dipilih
        $query = "INSERT INTO users (nama, email, password, role) VALUES ('$nama', '$email', '$password', '$role')";
        if (mysqli_query($conn, $query)) {
            $success = "Pendaftaran berhasil! Silakan <a href='login.php' style='color: #166534; font-weight: bold;'>Masuk</a>.";
        } else {
            $error = "Terjadi kesalahan sistem.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Favicon website -->
    <link rel="icon" type="image/png" href="uploads/logo_mangga_bersih.png">
    <title>Daftar - Yellow Mango's</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #1B3022; color: #ffffff; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .card { background: #ffffff; color: #1B3022; padding: 40px; border-radius: 12px; width: 100%; max-width: 400px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); }
        h2 { margin-top: 0; color: #1B3022; text-align: center; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 14px; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; font-size: 14px; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #FFC72C; }
        .btn { width: 100%; background: #FFC72C; color: #1B3022; border: none; padding: 12px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 16px; transition: background 0.2s; }
        .btn:hover { background: #e5b225; }
        .alert-error { background: #fee2e2; color: #b91c1c; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 13px; text-align: center; }
        .alert-success { background: #d1fae5; color: #065f46; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 13px; text-align: center; }
        .footer-link { text-align: center; margin-top: 15px; font-size: 14px; }
        .footer-link a { color: #1B3022; font-weight: bold; text-decoration: none; }
        .footer-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <div class="card">
        <h2>Daftar Akun</h2>
        <p style="text-align: center; color: #666; font-size: 13px; margin-bottom: 20px;">Buat akun menggunakan email asli kamu.</p>

        <?php if (!empty($error)) { echo "<div class='alert-error'>$error</div>"; } ?>
        <?php if (!empty($success)) { echo "<div class='alert-success'>$success</div>"; } ?>

        <form action="" method="POST">
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama" required placeholder="Masukkan nama lengkap">
            </div>
            <div class="form-group">
                <label>Email Aktif</label>
                <input type="email" name="email" required placeholder="nama@email.com">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="Buat password">
            </div>
            <div class="form-group">
                <label>Daftar Sebagai</label>
                <select name="role" required>
                    <option value="user">User (Pelanggan)</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <button type="submit" name="register" class="btn">Daftar Sekarang</button>
        </form>

        <div class="footer-link">
            Sudah punya akun? <a href="login.php">Masuk di sini</a>
        </div>
    </div>

</body>
</html>