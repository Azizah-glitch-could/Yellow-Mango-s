-- Yellow Mango's: database MySQL / MariaDB (Tahap 2)
-- Cara pakai: XAMPP/Laragon -> phpMyAdmin -> tab Import -> pilih file ini -> Import.
-- Database dibuat otomatis dengan nama db_yellow_mangos.

DROP TABLE IF EXISTS retur_pesanan;
DROP TABLE IF EXISTS log_pesanan;
DROP TABLE IF EXISTS detail_pesanan;
DROP TABLE IF EXISTS pesanan;
DROP TABLE IF EXISTS pengguna;
DROP TABLE IF EXISTS pelanggan;
DROP TABLE IF EXISTS admin;
DROP TABLE IF EXISTS produk;

-- Tabel utama 1: produk (id, nama_produk, harga, stok, foto, deskripsi + kolom pendukung katalog LK 5)
CREATE TABLE produk (
  id VARCHAR(20) NOT NULL PRIMARY KEY,
  nama_produk VARCHAR(120) NOT NULL,
  nama_singkat VARCHAR(60) NOT NULL,
  kategori VARCHAR(20) NOT NULL,
  harga INT NULL,
  harga_promo INT NULL,
  stok INT NOT NULL DEFAULT 0,
  kapasitas INT NOT NULL DEFAULT 0,
  segera_hadir TINYINT(1) NOT NULL DEFAULT 0,
  label VARCHAR(100) NOT NULL DEFAULT '',
  rating DECIMAL(2,1) NOT NULL DEFAULT 0,
  jumlah_ulasan INT NOT NULL DEFAULT 0,
  terjual INT NOT NULL DEFAULT 0,
  urutan INT NOT NULL DEFAULT 0,
  warna VARCHAR(9) NULL,
  warna_bg VARCHAR(9) NULL,
  ilustrasi VARCHAR(10) NULL,
  foto VARCHAR(255) NULL,
  teks_alt VARCHAR(160) NULL,
  kata_kunci VARCHAR(80) NULL,
  ringkasan TEXT NULL,
  deskripsi TEXT NULL,
  kata_rasa VARCHAR(160) NULL,
  ukuran VARCHAR(160) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel utama 2: pelanggan (id, nama, email, no_hp + alamat)
CREATE TABLE pelanggan (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(80) NOT NULL,
  email VARCHAR(120) NULL,
  no_hp VARCHAR(20) NOT NULL UNIQUE,
  alamat TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Akun pengguna (register, login, dashboard)
CREATE TABLE pengguna (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(80) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  no_hp VARCHAR(20) NOT NULL,
  alamat TEXT NULL,
  password_hash VARCHAR(255) NOT NULL,
  dibuat BIGINT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel utama 3: pesanan (id_order, id_pelanggan, total_harga, status_pembayaran + rincian)
CREATE TABLE pesanan (
  id_order INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nomor_order VARCHAR(24) NOT NULL UNIQUE,
  id_pelanggan INT NOT NULL,
  id_pengguna INT NULL,
  alamat_kirim TEXT NULL,
  catatan TEXT NULL,
  pengiriman VARCHAR(10) NOT NULL,
  kurir VARCHAR(10) NULL,
  cabang INT NOT NULL DEFAULT 0,
  metode_bayar VARCHAR(12) NOT NULL,
  subtotal INT NOT NULL,
  diskon INT NOT NULL DEFAULT 0,
  ongkir INT NOT NULL DEFAULT 0,
  total_harga INT NOT NULL,
  status_pembayaran VARCHAR(12) NOT NULL DEFAULT 'menunggu',
  tahap INT NOT NULL DEFAULT -1,
  alasan_gagal VARCHAR(160) NULL,
  batas_bayar BIGINT NOT NULL,
  dibuat BIGINT NOT NULL,
  selesai_pada BIGINT NULL,
  FOREIGN KEY (id_pelanggan) REFERENCES pelanggan(id),
  FOREIGN KEY (id_pengguna) REFERENCES pengguna(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel pendukung
CREATE TABLE detail_pesanan (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_order INT NOT NULL,
  id_produk VARCHAR(20) NOT NULL,
  nama_item VARCHAR(120) NOT NULL,
  jumlah INT NOT NULL,
  harga_satuan INT NOT NULL,
  FOREIGN KEY (id_order) REFERENCES pesanan(id_order) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE log_pesanan (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_order INT NOT NULL,
  label VARCHAR(60) NOT NULL,
  waktu BIGINT NOT NULL,
  FOREIGN KEY (id_order) REFERENCES pesanan(id_order) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE retur_pesanan (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_order INT NOT NULL UNIQUE,
  alasan VARCHAR(80) NOT NULL,
  solusi VARCHAR(12) NOT NULL,
  catatan TEXT NULL,
  nama_file VARCHAR(160) NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'Menunggu verifikasi',
  dibuat BIGINT NOT NULL,
  FOREIGN KEY (id_order) REFERENCES pesanan(id_order) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE admin (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(40) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data awal dari LK 5 (stok 20/20/15, batas minimum 5 cup).
-- Login admin awal: admin / admin123  (ganti sebelum dipakai online: lihat README).
INSERT INTO produk (id,nama_produk,nama_singkat,kategori,harga,harga_promo,stok,kapasitas,segera_hadir,label,rating,jumlah_ulasan,terjual,urutan,warna,warna_bg,ilustrasi,foto,teks_alt,kata_kunci,ringkasan,deskripsi,kata_rasa,ukuran) VALUES ('mangga', 'Yellow Mango''s Smoothies Mangga', 'Smoothies Mangga', 'smoothies', '18000', '16000', 20, 20, 0, 'Best Seller,Promo', 4.8, 124, 0, 1, '#FFB703', '#FFE9A8', 'cup', NULL, 'Smoothies Mangga Yellow Mango''s', 'smoothies mangga segar', 'Smoothies Mangga adalah minuman segar dengan rasa mangga yang cocok dinikmati saat santai, belajar, maupun bekerja. Rasanya fruity dan praktis dipesan lewat website Yellow Mango''s.', 'Nikmati Smoothies Mangga Yellow Mango''s untuk menemani aktivitasmu. Perpaduan rasa mangga yang fruity dan tekstur smoothies yang lembut membuatnya cocok jadi pilihan saat kamu butuh sesuatu yang segar. Pilih Smoothies Mangga, masukkan ke keranjang, lalu checkout langsung di website.', 'Segar,Creamy,Fruity,Nikmat', 'Regular, ±360 ml (±400 g), disajikan dalam cup');
INSERT INTO produk (id,nama_produk,nama_singkat,kategori,harga,harga_promo,stok,kapasitas,segera_hadir,label,rating,jumlah_ulasan,terjual,urutan,warna,warna_bg,ilustrasi,foto,teks_alt,kata_kunci,ringkasan,deskripsi,kata_rasa,ukuran) VALUES ('alpukat', 'Yellow Mango''s Smoothies Alpukat', 'Smoothies Alpukat', 'smoothies', '18000', NULL, 20, 20, 0, '', 4.7, 86, 0, 2, '#9CBF5B', '#E1EFC4', 'cup', NULL, 'Smoothies Alpukat Yellow Mango''s', 'smoothies alpukat segar', 'Smoothies Alpukat yang lembut dan mengenyangkan, pas untuk teman belajar atau kerja. Segar, praktis, dan siap dipesan dari mana saja.', 'Butuh minuman yang segar sekaligus mengenyangkan? Smoothies Alpukat Yellow Mango''s dibuat dari alpukat pilihan dengan tekstur creamy. Cocok untuk jeda kuliah atau istirahat kantor. Pilih, masukkan ke keranjang, dan checkout lewat website.', 'Segar,Creamy,Mengenyangkan', 'Regular, ±360 ml (±400 g), disajikan dalam cup');
INSERT INTO produk (id,nama_produk,nama_singkat,kategori,harga,harga_promo,stok,kapasitas,segera_hadir,label,rating,jumlah_ulasan,terjual,urutan,warna,warna_bg,ilustrasi,foto,teks_alt,kata_kunci,ringkasan,deskripsi,kata_rasa,ukuran) VALUES ('salad', 'Yellow Mango''s Salad Buah Segar', 'Salad Buah', 'dessert', '20000', NULL, 15, 15, 0, '', 4.8, 64, 0, 3, '#FF8FA3', '#FFD9CF', 'bowl', NULL, 'Salad Buah Segar Yellow Mango''s', 'salad buah segar', 'Salad Buah Segar berisi potongan buah pilihan, jadi dessert berbahan buah yang praktis dan lebih ringan.', 'Salad Buah Segar Yellow Mango''s cocok untuk kamu yang ingin dessert manis tapi tetap berbahan buah. Disajikan dalam cup atau box kedap, lengkap dengan sendok. Teman yang pas untuk Smoothies Mangga, coba paket Mango Fresh Combo.', 'Segar,Fruity,Praktis', 'Regular, ±300 g, cup/box kedap + sendok');
INSERT INTO produk (id,nama_produk,nama_singkat,kategori,harga,harga_promo,stok,kapasitas,segera_hadir,label,rating,jumlah_ulasan,terjual,urutan,warna,warna_bg,ilustrasi,foto,teks_alt,kata_kunci,ringkasan,deskripsi,kata_rasa,ukuran) VALUES ('taiwan', 'Yellow Mango''s Taiwan Dessert', 'Taiwan Dessert', 'dessert', NULL, NULL, 0, 0, 1, 'New', 0, 0, 0, 4, '#B79BE0', '#E8DDF5', 'dessert', NULL, 'Taiwan Dessert Yellow Mango''s', 'taiwan dessert', 'Taiwan Dessert segera hadir di katalog. Harga dan stok akan diumumkan.', 'Taiwan Dessert masuk daftar produk Yellow Mango''s, tetapi stok dan harganya belum ditetapkan di LK 5.', '', 'Segera hadir');

INSERT INTO admin (username, password_hash) VALUES ('admin', '$2y$10$smPhOv1N5FHWG4W/kdOkmOey1sA219Y7x1YB8UqE/rviGu94ehVUW');
