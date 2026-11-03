<?php
session_start();
include 'koneksi.php';

$error = "";
if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $result = mysqli_query($conn, "SELECT * FROM users WHERE email='$email' AND password='$password'");
    
    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        $_SESSION['nama'] = $row['nama'];
        $_SESSION['role'] = $row['role'];

        if ($row['role'] === 'admin') {
            header("Location: admin_dashboard.php");
            exit;
        } else {
            header("Location: user_dashboard.php");
            exit;
        }
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
        body {
            font-family: Arial, sans-serif;
            background: #F4F1EA;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-card {
            background: white;
            padding: 35px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            width: 380px;
        }
        h2 {
            color: #1B3022;
            margin-bottom: 5px;
        }
        p.subtitle {
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
            color: #333;
            font-size: 14px;
        }
        input[type="email"], input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            background: #F9F9F9;
        }
        .checkbox-container {
            display: flex;
            align-items: center;
            font-size: 14px;
            color: #555;
            margin-bottom: 15px;
        }
        .checkbox-container input {
            margin-right: 8px;
        }
        button {
            width: 100%;
            padding: 11px;
            background: #FFC72C;
            color: #1B3022;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 15px;
        }
        button:hover {
            background: #E5B225;
        }
        .error {
            color: red;
            font-size: 13px;
            margin-bottom: 12px;
            text-align: center;
            background: #FFEDED;
            padding: 8px;
            border-radius: 6px;
        }
        .demo-accounts {
            margin-top: 20px;
            background: #FFF9E6;
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            border: 1px dashed #D4A373;
        }
        .demo-accounts p {
            margin: 0 0 6px 0;
            font-weight: bold;
            color: #1B3022;
        }
        .demo-accounts ul {
            margin: 0;
            padding-left: 18px;
            color: #444;
        }
        .demo-accounts li {
            margin-bottom: 3px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>Masuk</h2>
        <p class="subtitle">Masuk untuk membuka dashboard dan riwayat pesananmu.</p>
        
        <?php if (!empty($error)) echo "<div class='error'>$error</div>"; ?>

        <form method="POST">
            <label>Email</label>
            <input type="email" name="email" required placeholder="nama@email.com">
            
            <label>Password</label>
            <input type="password" name="password" id="password" required placeholder="••••••••••••">
            
            <div class="checkbox-container">
                <input type="checkbox" id="show-pass" onclick="togglePassword()">
                <label for="show-pass" style="margin-bottom:0; font-weight:normal;">Tampilkan password</label>
            </div>

            <button type="submit" name="login">Masuk</button>
        </form>

        <!-- Kotak Bantuan Akun Demo untuk Presentasi -->
        <div class="demo-accounts">
            <p>💡 Akun untuk Demo Presentasi:</p>
            <ul>
                <li><b>Admin (Pemilik Toko):</b> admin@yellow.com | 123456</li>
                <li><b>User (Pembeli):</b> user@yellow.com | 123456</li>
            </ul>
        </div>
    </div>

    <script>
        function togglePassword() {
            var x = document.getElementById("password");
            if (x.type === "password") {
                x.type = "text";
            } else {
                x.type = "password";
            }
        }
    </script>
</body>
</html>