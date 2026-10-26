# Yellow Mango's: Website E-Commerce (HTML + CSS + JavaScript + PHP + MySQL)

Proyek mata kuliah E-Commerce, kelompok Oopsie (kelas C 24, semester 5). Isinya mengikuti LK Pertemuan 2 sampai 6.
Teknologi sesuai LK 3: **HTML, CSS, JavaScript** (tampilan), **PHP** (proses di server), **MySQL** (database), pembayaran **simulasi** untuk Midtrans.

## Isi folder

```
yellow-mangos/
├── index.html              Halaman utama (semua halaman dimuat di sini)
├── assets/
│   ├── css/style.css       Tampilan
│   └── js/app.js           Interaksi + komunikasi dengan server
├── api/                    Backend PHP
│   ├── config.php          PENGATURAN: database, nomor WhatsApp, kode promo, ongkir
│   ├── lib.php             Fungsi bersama (koneksi PDO, validasi, stok)
│   ├── bootstrap.php       Mengambil produk dari MySQL
│   ├── order_create.php    Menyimpan pesanan saat checkout
│   ├── order_pay.php       Status pembayaran (simulasi)
│   ├── order_get.php / order_list.php   Melacak pesanan
│   ├── order_return.php    Pengajuan retur + unggah bukti
│   ├── user_*.php          Akun pengguna: daftar, masuk, keluar, dashboard (user_me), ubah profil/password
│   └── admin_*.php         Login admin, data, ubah status/stok/harga
├── database/
│   ├── database.sql        Struktur tabel + data awal (import ke MySQL)
│   ├── tambah_pengguna.sql Tambahan tabel `pengguna` untuk database lama (lihat bawah)
│   └── buat_hash.php       Pembuat hash password admin
├── uploads/                Bukti retur dari pelanggan
└── wireframe/index.html    Tahap 1: wireframe 4 halaman wajib
```

## Cara menjalankan di komputer (XAMPP atau Laragon)

1. **Nyalakan** Apache dan MySQL (XAMPP: tombol Start di XAMPP Control Panel. Laragon: Start All).
2. **Salin folder `yellow-mangos`** ke:
   - XAMPP: `C:\xampp\htdocs\yellow-mangos`
   - Laragon: `C:\laragon\www\yellow-mangos`
3. **Import database**: buka `http://localhost/phpmyadmin` → tab **Import** → pilih `database/database.sql` → **Import**.
   Database `db_yellow_mangos` dibuat otomatis, berisi tabel `produk`, `pelanggan`, `pesanan`, `detail_pesanan`, `log_pesanan`, `retur_pesanan`, `admin`, `pengguna`.
4. **Cek `api/config.php`**: bawaan XAMPP/Laragon (user `root`, password kosong) sudah cocok. Ganti `WA_NUMBER`, `HOURS`, dan `PROMO_END`.
5. **Buka** `http://localhost/yellow-mangos/`. Jangan klik dua kali `index.html`, karena PHP hanya jalan lewat server.

Dashboard admin: menu **Admin**. Akun awal `admin` / `admin123`.

## Akun pengguna: daftar, masuk, dashboard (tugas "Set Up Project")

- **Daftar**: tombol **Masuk** di pojok kanan atas → *Daftar gratis* → isi nama, email, WhatsApp, password (min. 8 karakter, huruf + angka).
- **Masuk / Keluar**: email + password. Setelah masuk, tombol di header berubah menjadi nama pengguna.
- **Dashboard pengguna** (`#akun`): ringkasan pesanan, riwayat pesanan (juga dari perangkat lain), edit profil dan alamat, ubah password.
- **Checkout**: jika sedang masuk, data pemesan terisi otomatis dan pesanan tersimpan di akun. Tamu tetap bisa membeli tanpa akun.
- **Keamanan**: password disimpan sebagai hash (`password_hash`), sesi memakai cookie `ymuser` (terpisah dari admin), sesi diganti saat login, 5 kali salah → ditahan 10 menit, pesan error tidak membocorkan apakah email terdaftar.

**Jika database sudah di-import sebelum fitur ini:** import `database/tambah_pengguna.sql` lewat phpMyAdmin (tab Import) saja. Data lama tidak hilang. Jika belum pernah import, cukup `database.sql` (sudah termasuk tabel `pengguna`).

## Mengganti password admin

Dari terminal (XAMPP: buka Shell di Control Panel):

```
php database/buat_hash.php PasswordBaruKamu
```

Salin hasilnya, lalu di phpMyAdmin → tab SQL:

```sql
UPDATE admin SET password_hash = 'HASIL_DI_SINI' WHERE username = 'admin';
```

## Alur uji untuk presentasi (Tahap 5)

1. **Menu** → pilih Smoothies Mangga → **Pesan Sekarang**.
2. Isi **Data**, pilih **Pengiriman**, coba kode `MANGO10`, **Pembayaran**, centang syarat, **Bayar**.
3. Di halaman pembayaran klik **Pembayaran gagal**, lalu **Bayar lagi**, lalu **Pembayaran berhasil** (ini pengganti notifikasi Midtrans).
4. Cek **stok berkurang** di Menu dan tabel `pesanan` di phpMyAdmin.
5. Masuk **Admin**, klik status berikutnya sampai *Delivered*.
6. Di **Pesanan** buka pesanan itu, ajukan **retur** dengan foto. Setujui retur di Admin.

## Memasang secara online

Netlify dan Vercel biasa **tidak bisa** menjalankan PHP/MySQL. Pakai hosting PHP:

1. Daftar hosting PHP + MySQL (gratis untuk tugas: InfinityFree, atau hosting cPanel milik kampus/kelompok).
2. Di panel hosting, buat database MySQL. Catat **host, nama database, user, password**.
3. Buka phpMyAdmin di hosting → pilih database yang baru dibuat → **Import** `database/database.sql`.
   (Hapus dua baris awal `CREATE DATABASE ...` dan `USE db_yellow_mangos;` bila hosting tidak mengizinkan.)
4. Edit `api/config.php`: isi `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` dengan data hosting.
5. Unggah isi folder `yellow-mangos` ke folder `htdocs` (lewat File Manager atau FTP).
6. Buka alamat domain hosting. **Ganti password admin** (lihat di atas).

## Yang masih simulasi

- Pembayaran: tombol "Pembayaran berhasil/gagal" menggantikan notifikasi Midtrans. Di `api/config.php`, `SIMULATE_PAYMENT = false` mematikan tombol itu. Untuk Midtrans asli, status harus diubah oleh *webhook* dari Midtrans di `order_pay.php`.
- Kurir dan ongkir: tarif tetap di `config.php`; admin memesan GoSend/GrabExpress sendiri lalu mengubah status.
- Ulasan dan rating di halaman produk adalah contoh.

## Catatan keamanan (sudah ditangani)

- Harga dan total dihitung ulang di server, bukan dari browser.
- Query memakai PDO prepared statement; keluaran HTML di-escape.
- Stok dikurangi atomik saat pembayaran berhasil, jadi tidak bisa terjual melebihi stok.
- Pesanan hanya bisa dilihat dengan nomor order **dan** nomor WhatsApp yang cocok.
- Dashboard admin dan akun pengguna memakai login + sesi; password disimpan sebagai hash.
- Pesanan juga bisa dibuka oleh pemilik akun yang sedang masuk.
- Unggahan retur dibatasi jenis (foto/video), ukuran 8 MB, dan folder `uploads/` tidak boleh menjalankan PHP (Apache).
