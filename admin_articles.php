<?php
include 'koneksi.php';

// Proses Tambah Artikel
if (isset($_POST['tambah_artikel'])) {
    $judul       = mysqli_real_escape_string($conn, $_POST['judul']);
    $kategori    = mysqli_real_escape_string($conn, $_POST['kategori']);
    $konten      = mysqli_real_escape_string($conn, $_POST['konten']);
    $link_sumber = mysqli_real_escape_string($conn, $_POST['link_sumber']);
    
    // Upload Foto
    $foto = '';
    if (!empty($_FILES['foto']['name'])) {
        $foto = time() . '_' . $_FILES['foto']['name'];
        move_uploaded_file($_FILES['foto']['tmp_name'], 'uploads/' . $foto);
    }

    $query = "INSERT INTO articles (judul, kategori, konten, link_sumber, foto) VALUES ('$judul', '$kategori', '$konten', '$link_sumber', '$foto')";
    mysqli_query($conn, $query);
    $pesan = "Artikel berhasil ditambahkan!";
}

// Proses Hapus Artikel
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    // Hapus file foto jika ada
    $q_foto = mysqli_query($conn, "SELECT foto FROM articles WHERE id=$id");
    $data_f = mysqli_fetch_assoc($q_foto);
    if (!empty($data_f['foto']) && file_exists('uploads/' . $data_f['foto'])) {
        unlink('uploads/' . $data_f['foto']);
    }
    
    mysqli_query($conn, "DELETE FROM articles WHERE id=$id");
    header("Location: admin_articles.php");
    exit;
}

$result = mysqli_query($conn, "SELECT * FROM articles ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin - Kelola Artikel & Promo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; background-color: #1B3022; color: #ffffff; margin: 0; padding: 0; }
        
        /* Navbar Admin Sticky */
        .navbar-custom { position: sticky; top: 0; z-index: 1000; background-color: #1B3022; padding: 20px 50px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.1); box-sizing: border-box; }
        .logo { font-size: 22px; font-weight: bold; color: #FFC72C; text-decoration: none; }
        .nav-links { list-style: none; display: flex; gap: 25px; margin: 0; padding: 0; align-items: center; }
        .nav-links a { color: white; text-decoration: none; font-size: 15px; }
        .nav-links a:hover, .nav-links a.active { color: #FFC72C; }
        .btn-keluar { background-color: #d9534f; color: white; padding: 6px 14px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 14px; }

        .container-custom { padding: 40px 50px; max-width: 1100px; margin: 0 auto; }
        h2 { color: #FFC72C; font-weight: bold; }

        /* Card Styling */
        .card-custom { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 12px; padding: 30px; margin-bottom: 40px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .card-custom h5 { color: #FFC72C; font-weight: bold; margin-bottom: 20px; }

        /* Form Controls */
        .form-label { color: #f1f5f9; font-weight: bold; }
        .form-control { background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.2); color: white; border-radius: 8px; padding: 10px 15px; }
        .form-control:focus { background: rgba(255, 255, 255, 0.12); border-color: #FFC72C; color: white; box-shadow: 0 0 0 0.25rem rgba(255, 199, 44, 0.25); }
        .form-control::placeholder { color: #94a3b8; }

        /* Buttons */
        .btn-publish { background: #FFC72C; color: #1B3022; font-weight: bold; border: none; padding: 10px 25px; border-radius: 8px; transition: background 0.2s; }
        .btn-publish:hover { background: #e5b225; }

        /* Tabel Styling */
        .table-custom { color: #f1f5f9; vertical-align: middle; }
        .table-custom th { background-color: #122017 !important; color: #FFC72C !important; border-color: rgba(255,255,255,0.1); }
        .table-custom td { background-color: rgba(255,255,255,0.03) !important; color: #f1f5f9 !important; border-color: rgba(255,255,255,0.1); }
    </style>
</head>
<body>

    <!-- Header / Navbar Admin -->
    <header class="navbar-custom">
        <a href="admin_dashboard.php" class="logo">Yellow Mango's (Admin)</a>
        <ul class="nav-links" style="list-style: none; display: flex; gap: 25px; margin: 0; padding: 0; align-items: center;">
    <li><a href="admin_dashboard.php" style="color: #FFC72C; font-weight: bold; text-decoration: none;">Beranda Admin</a></li>
    <li><a href="admin_menu.php" style="color: white; text-decoration: none;">Kelola Menu</a></li>
    <li><a href="admin_articles.php" style="color: white; text-decoration: none;">Kelola Artikel</a></li>
    <li><a href="admin_pesanan.php" style="color: white; text-decoration: none;">Pesanan Masuk</a></li>
    <li><a href="login.php" class="btn-keluar">Keluar</a></li>
</ul>
    </header>

    <div class="container-custom">
        <h2 class="mb-4">📰 Kelola Artikel & Berita / Promo</h2>
        
        <?php if (isset($pesan)) : ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $pesan; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Form Tambah Artikel -->
        <div class="card-custom">
            <h5>+ Tambah Artikel atau Promo Baru</h5>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Judul Artikel</label>
                    <input type="text" name="judul" class="form-control" placeholder="Masukkan judul artikel..." required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Kategori</label>
                    <input type="text" name="kategori" class="form-control" placeholder="Contoh: Promo, Tips Sehat, Berita" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Konten Artikel</label>
                    <textarea name="konten" class="form-control" rows="4" placeholder="Tulis isi artikel atau ringkasan promo di sini..." required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Link Sumber Luar (Opsional)</label>
                    <input type="url" name="link_sumber" class="form-control" placeholder="https://contoh-sumber-artikel.com">
                </div>
                <div class="mb-4">
                    <label class="form-label">Foto Banner/Thumbnail</label>
                    <input type="file" name="foto" class="form-control">
                </div>
                <button type="submit" name="tambah_artikel" class="btn btn-publish">Publish Artikel</button>
            </form>
        </div>

        <!-- Daftar Artikel -->
        <div class="card-custom">
            <h5>📋 Daftar Artikel & Promo Published</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-custom align-middle">
                    <thead>
                        <tr>
                            <th width="50" class="text-center">No</th>
                            <th width="100" class="text-center">Foto</th>
                            <th>Judul, Kategori & Link</th>
                            <th width="120">Tanggal</th>
                            <th width="90" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; while ($row = mysqli_fetch_assoc($result)) : ?>
                        <tr>
                            <td class="text-center"><?= $no++; ?></td>
                            <td class="text-center">
                                <?php if (!empty($row['foto']) && file_exists('uploads/' . $row['foto'])): ?>
                                    <img src="uploads/<?= $row['foto']; ?>" width="80" height="50" style="object-fit:cover; border-radius:6px;">
                                <?php else: ?>
                                    <span class="text-muted small">Tanpa Foto</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong style="color: #FFC72C; font-size: 16px;"><?= htmlspecialchars($row['judul']); ?></strong><br>
                                <span class="badge bg-warning text-dark mb-1"><?= htmlspecialchars($row['kategori']); ?></span><br>
                                <?php if (!empty($row['link_sumber'])): ?>
                                    <a href="<?= htmlspecialchars($row['link_sumber']); ?>" target="_blank" class="small text-info text-decoration-none">🔗 Link Sumber ↗</a>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d M Y', strtotime($row['tanggal'])); ?></td>
                            <td class="text-center">
                                <a href="admin_articles.php?hapus=<?= $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus artikel ini?')">Hapus</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>