-- Khusus jika database db_yellow_mangos SUDAH di-import sebelumnya dan kamu tidak mau menghapus data pesanan.
-- Menambahkan fitur akun (register, login, dashboard). Jalankan di phpMyAdmin -> pilih db_yellow_mangos -> tab SQL.
-- Jika kamu import ulang database.sql yang baru, file ini TIDAK perlu dijalankan.
USE db_yellow_mangos;

CREATE TABLE IF NOT EXISTS pengguna (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(80) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  no_hp VARCHAR(20) NOT NULL,
  alamat TEXT NULL,
  password_hash VARCHAR(255) NOT NULL,
  dibuat BIGINT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE pesanan ADD COLUMN id_pengguna INT NULL AFTER id_pelanggan;
ALTER TABLE pesanan ADD CONSTRAINT fk_pesanan_pengguna FOREIGN KEY (id_pengguna) REFERENCES pengguna(id) ON DELETE SET NULL;
