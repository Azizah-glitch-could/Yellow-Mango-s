<?php
session_start();
include 'koneksi.php';

$error = '';

if (isset($_POST['login'])) {
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    $result = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email' AND password = '$password'");
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $_SESSION['nama']  = $row['nama'];
        $_SESSION['role']  = $row['role'];
        $_SESSION['email'] = $row['email'];

        if ($row['role'] === 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: user_dashboard.php");
        }
        exit;
    } else {
        $error = "Email atau password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - Yellow Mango's</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #1B3022; color: #ffffff; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: #ffffff; color: #1B3022; padding: 40px; border-radius: 12px; width: 100%; max-width: 400px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); }
        h2 { margin-top: 0; color: #1B3022; text-align: center; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 14px; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; }
        .btn { width: 100%; background: #FFC72C; color: #1B3022; border: none; padding: 12px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #e5b225; }
        .alert-error { background: #fee2e2; color: #b91c1c; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 13px; text-align: center; }
        .footer-link { text-align: center; margin-top: 15px; font-size: 14px; }
        .footer-link a { color: #1B3022; font-weight: bold; text-decoration: none; }
        .footer-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <div class="card">
        <h2>Masuk Akun</h2>
        <p style="text-align: center; color: #666; font-size: 13px; margin-bottom: 20px;">Masukkan email dan password Anda.</p>

        <?php if (!empty($error)) { echo "<div class='alert-error'>$error</div>"; } ?>

        <form action="" method="POST">
            <div class="form-group">
                <label>Email</label>
                <!-- Dikosongkan agar diisi sendiri -->
                <input type="email" name="email" required placeholder="nama@email.com">
            </div>
            <div class="form-group">
                <label>Password</label>
                <!-- Dikosongkan agar diisi sendiri -->
                <input type="password" name="password" required placeholder="Masukkan password">
            </div>
            <button type="submit" name="login" class="btn">Masuk</button>
        </form>

        <div class="footer-link">
            Belum punya akun? <a href="register.php">Daftar di sini</a>
        </div>
    </div>

</body>
</html>