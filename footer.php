<?php
// Ambil data pengaturan web terbaru untuk footer
$q_footer = mysqli_query($conn, "SELECT * FROM pengaturan_web WHERE id=1");
$set_footer = mysqli_fetch_assoc($q_footer);
?>

<footer style="background-color: #122017; color: #f1f5f9; padding: 50px 50px 20px; border-top: 1px solid rgba(255,255,255,0.1); margin-top: 60px;">
    <div style="max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 30px; margin-bottom: 40px;">
        
        <!-- Kolom 1: Profil Brand -->
        <div>
            <h3 style="color: #FFC72C; font-size: 20px; margin-bottom: 15px; font-weight: bold;"><?= htmlspecialchars($set_footer['nama_website'] ?? "Yellow Mango's"); ?></h3>
            <p style="color: #cbd5e1; font-size: 14px; line-height: 1.6;">
                <?= htmlspecialchars($set_footer['deskripsi_toko'] ?? "Pusat olahan mangga segar pilihan kualitas terbaik di Makassar."); ?>
            </p>
        </div>

        <!-- Kolom 2: Navigasi Cepat -->
        <div>
            <h4 style="color: #FFC72C; font-size: 16px; margin-bottom: 15px; font-weight: bold;">Navigasi Cepat</h4>
            <ul style="list-style: none; padding: 0; margin: 0; line-height: 2.2;">
                <li><a href="user_dashboard.php" style="color: #cbd5e1; text-decoration: none; font-size: 14px;">Beranda</a></li>
                <li><a href="user_menu.php" style="color: #cbd5e1; text-decoration: none; font-size: 14px;">Katalog Menu</a></li>
                <li><a href="user_articles.php" style="color: #FFC72C; text-decoration: none; font-size: 14px; font-weight: bold;">Artikel & Promo</a></li>
                <li><a href="user_pesanan.php" style="color: #cbd5e1; text-decoration: none; font-size: 14px;">Pesanan Saya</a></li>
            </ul>
        </div>

        <!-- Kolom 3: Informasi & Kebijakan -->
        <div>
            <h4 style="color: #FFC72C; font-size: 16px; margin-bottom: 15px; font-weight: bold;">Informasi & Kebijakan</h4>
            <ul style="list-style: none; padding: 0; margin: 0; line-height: 2.2;">
                <li><a href="tentang_kami.php" style="color: #cbd5e1; text-decoration: none; font-size: 14px;">Tentang Kami</a></li>
                <li><a href="kebijakan_privasi.php" style="color: #cbd5e1; text-decoration: none; font-size: 14px;">Kebijakan Privasi</a></li>
                <li><a href="syarat_ketentuan.php" style="color: #cbd5e1; text-decoration: none; font-size: 14px;">Syarat & Ketentuan</a></li>
            </ul>
        </div>

        <!-- Kolom 4: Hubungi Kami -->
        <div>
            <h4 style="color: #FFC72C; font-size: 16px; margin-bottom: 15px; font-weight: bold;">Hubungi Kami</h4>
            <p style="color: #cbd5e1; font-size: 14px; margin-bottom: 10px;">📍 <?= htmlspecialchars($set_footer['alamat'] ?? 'Jl. Tamalanrea Raya, Makassar'); ?></p>
            <p style="color: #cbd5e1; font-size: 14px; margin-bottom: 10px;">✉️ <?= htmlspecialchars($set_footer['email_kontak'] ?? 'support@yellowmangos.com'); ?></p>
            <p style="color: #cbd5e1; font-size: 14px; margin-bottom: 10px;">📱 WhatsApp: <?= htmlspecialchars($set_footer['telepon'] ?? '081234567890'); ?></p>
        </div>

    </div>

    <div style="text-align: center; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 20px; font-size: 13px; color: #94a3b8;">
        &copy; <?= date('Y'); ?> <?= htmlspecialchars($set_footer['nama_website'] ?? "Yellow Mango's"); ?>. All rights reserved.
    </div>
</footer>