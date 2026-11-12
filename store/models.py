from django.db import models
from django.contrib.auth.models import User

# 1. Tabel Kategori Produk (Minuman, Dessert, Camilan, dll)
class Kategori(models.Model):
    nama = models.CharField(max_length=100)
    deskripsi = models.TextField(blank=True, null=True)

    def __str__(self):
        return self.nama

# 2. Tabel Menu / Produk Yellow Mango's
class Produk(models.Model):
    kategori = models.ForeignKey(Kategori, on_delete=models.CASCADE, related_name='produk')
    nama = models.CharField(max_length=150)
    deskripsi = models.TextField()
    harga = models.DecimalField(max_digits=10, decimal_places=0)
    gambar = models.ImageField(upload_to='produk/', blank=True, null=True)
    tersedia = models.BooleanField(default=True)
    unggulan = models.BooleanField(default=False) # Untuk ditampilkan di Beranda
    dibuat_pada = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return self.nama

# 3. Tabel Lokasi Outlet
class Outlet(models.Model):
    nama_cabang = models.CharField(max_length=100)
    alamat = models.TextField()
    kota = models.CharField(max_length=100)
    jam_buka = models.CharField(max_length=100, default="10.00 - 22.00 WITA")
    link_maps = models.URLField(blank=True, null=True)

    def __str__(self):
        return f"{self.nama_cabang} - {self.kota}"

# 4. Tabel Keranjang Belanja (Cart)
class Keranjang(models.Model):
    user = models.ForeignKey(User, on_delete=models.CASCADE)
    produk = models.ForeignKey(Produk, on_delete=models.CASCADE)
    jumlah = models.PositiveIntegerField(default=1)
    ditambahkan_pada = models.DateTimeField(auto_now_add=True)

    def subtotal(self):
        return self.produk.harga * self.jumlah

    def __str__(self):
        return f"{self.user.username} - {self.produk.nama} ({self.jumlah})"

# 5. Tabel Pesanan (Order)
class Pesanan(models.Model):
    STATUS_CHOICES = [
        ('Menunggu Pembayaran', 'Menunggu Pembayaran'),
        ('Diproses', 'Diproses'),
        ('Dikirim', 'Dikirim'),
        ('Selesai', 'Selesai'),
        ('Dibatalkan', 'Dibatalkan'),
    ]

    user = models.ForeignKey(User, on_delete=models.CASCADE)
    outlet = models.ForeignKey(Outlet, on_delete=models.SET_NULL, null=True)
    kode_pesanan = models.CharField(max_length=20, unique=True)
    alamat_pengiriman = models.TextField()
    nomor_hp = models.CharField(max_length=20)
    total_harga = models.DecimalField(max_digits=12, decimal_places=0)
    status = models.CharField(max_length=30, choices=STATUS_CHOICES, default='Menunggu Pembayaran')
    tanggal_pesan = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f"Order #{self.kode_pesanan} - {self.user.username}"

# 6. Tabel Detail Item dalam Pesanan
class DetailPesanan(models.Model):
    pesanan = models.ForeignKey(Pesanan, on_delete=models.CASCADE, related_name='items')
    produk = models.ForeignKey(Produk, on_delete=models.CASCADE)
    jumlah = models.PositiveIntegerField()
    harga_satuan = models.DecimalField(max_digits=10, decimal_places=0)

    def __str__(self):
        return f"{self.produk.nama} x {self.jumlah}"

# 7. Tabel Testimoni Pelanggan
class Testimoni(models.Model):
    nama_pelanggan = models.CharField(max_length=100)
    status_pelanggan = models.CharField(max_length=100, default="Pelanggan Setia")
    judul_ulasan = models.CharField(max_length=150)
    isi_ulasan = models.TextField()
    rating = models.PositiveIntegerField(default=5)
    tampilkan = models.BooleanField(default=True)

    def __str__(self):
        return f"{self.nama_pelanggan} - {self.judul_ulasan}"