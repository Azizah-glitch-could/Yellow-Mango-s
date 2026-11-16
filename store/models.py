from django.db import models
from django.contrib.auth.models import User
from django.utils.text import slugify


# 1. MODEL PRODUK / MENU
class Product(models.Model):
    CATEGORY_CHOICES = [
        ('Coffee', 'Coffee'),
        ('Signature Coffee', 'Signature Coffee'),
        ('Non-Coffee', 'Non-Coffee'),
        ('Tea', 'Tea'),
        ('Minuman Tradisional', 'Minuman Tradisional'),
        ('Snack', 'Snack'),
        ('Indomie', 'Indomie'),
        ('Main Course', 'Main Course'),
        ('Lain-Lain', 'Lain-Lain'),
    ]

    BADGE_CHOICES = [
        ('NONE', 'Tanpa Badge'),
        ('BEST_SELLER', 'Best Seller'),
        ('NEW', 'Terbaru'),
        ('PROMO', 'Promo'),
    ]

    name = models.CharField(max_length=200)
    category = models.CharField(max_length=50, choices=CATEGORY_CHOICES, default='Lain-Lain')
    price = models.DecimalField(max_digits=10, decimal_places=2)
    description = models.TextField(blank=True, null=True)
    badge_primary = models.CharField(max_length=20, choices=BADGE_CHOICES, default='NONE')
    badge_secondary = models.CharField(max_length=20, choices=BADGE_CHOICES, default='NONE')
    image = models.ImageField(upload_to='products/', blank=True, null=True)
    image_url = models.URLField(blank=True, null=True)
    is_available = models.BooleanField(default=True)
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f"{self.name} ({self.category})"


# 2. MODEL PESANAN (ORDER & ORDER ITEM)
class Order(models.Model):
    STATUS_CHOICES = [
        ('PENDING', 'Menunggu Pembayaran'),
        ('PROCESSING', 'Sedang Diproses'),
        ('READY', 'Siap Diambil / Dikirim'),
        ('COMPLETED', 'Selesai'),
        ('CANCELLED', 'Dibatalkan'),
    ]

    order_number = models.CharField(max_length=30, unique=True, verbose_name="Nomor Pesanan")
    customer = models.ForeignKey(User, on_delete=models.SET_NULL, null=True, blank=True, related_name='orders')
    customer_name = models.CharField(max_length=150, verbose_name="Nama Pemesan")
    customer_phone = models.CharField(max_length=25, verbose_name="Nomor WhatsApp/HP")
    notes = models.TextField(blank=True, null=True, verbose_name="Catatan Pesanan")
    total_amount = models.PositiveIntegerField(default=0, verbose_name="Total Harga (Rp)")
    status = models.CharField(max_length=20, choices=STATUS_CHOICES, default='PENDING', verbose_name="Status Pesanan")
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        verbose_name = "Pesanan"
        verbose_name_plural = "Daftar Pesanan"
        ordering = ['-created_at']

    def __str__(self):
        return f"#{self.order_number} - {self.customer_name} ({self.get_status_display()})"


class OrderItem(models.Model):
    order = models.ForeignKey(Order, on_delete=models.CASCADE, related_name='items')
    product = models.ForeignKey(Product, on_delete=models.SET_NULL, null=True)
    product_name = models.CharField(max_length=150)
    price = models.PositiveIntegerField()
    quantity = models.PositiveIntegerField(default=1)

    def get_subtotal(self):
        return self.price * self.quantity

    def __str__(self):
        return f"{self.quantity}x {self.product_name}"


# 3. MODEL HALAMAN STATIS / DINAMIS (PAGE BUILDER)
class Page(models.Model):
    title = models.CharField(max_length=150, verbose_name="Judul Halaman")
    slug = models.SlugField(max_length=170, unique=True, blank=True)
    content = models.TextField(verbose_name="Isi Konten Halaman")
    is_published = models.BooleanField(default=True, verbose_name="Tampilkan di Website")
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        verbose_name = "Halaman"
        verbose_name_plural = "Manajemen Halaman"

    def save(self, *args, **kwargs):
        if not self.slug:
            self.slug = slugify(self.title)
        super().save(*args, **kwargs)

    def __str__(self):
        return self.title


# 4. MODEL BERITA / ARTIKEL / BLOG
class Article(models.Model):
    title = models.CharField(max_length=200, verbose_name="Judul Artikel")
    slug = models.SlugField(max_length=220, unique=True, blank=True)
    category = models.CharField(max_length=100, default="Tips & Kuliner", verbose_name="Kategori")
    tags = models.CharField(max_length=200, blank=True, help_text="Pisahkan dengan koma, contoh: mangga, sehat, promo")
    thumbnail = models.ImageField(upload_to='articles/', blank=True, null=True, verbose_name="Gambar Sampul")
    content = models.TextField(verbose_name="Isi Artikel")
    author = models.ForeignKey(User, on_delete=models.SET_NULL, null=True, blank=True)
    is_published = models.BooleanField(default=True, verbose_name="Publikasikan")
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        verbose_name = "Artikel / Blog"
        verbose_name_plural = "Daftar Artikel & Blog"
        ordering = ['-created_at']

    def save(self, *args, **kwargs):
        if not self.slug:
            self.slug = slugify(self.title)
        super().save(*args, **kwargs)

    def __str__(self):
        return self.title


# 5. MODEL AGENDA, EVENT & PENGUMUMAN
class Announcement(models.Model):
    TYPE_CHOICES = [
        ('PROMO', 'Promo & Diskon'),
        ('EVENT', 'Agenda & Event'),
        ('INFO', 'Pengumuman Umum'),
    ]

    title = models.CharField(max_length=200, verbose_name="Judul Pengumuman / Event")
    announcement_type = models.CharField(max_length=20, choices=TYPE_CHOICES, default='PROMO', verbose_name="Jenis")
    description = models.TextField(verbose_name="Detail Informasi")
    event_date = models.DateField(blank=True, null=True, verbose_name="Tanggal Pelaksanaan (Opsional)")
    banner = models.ImageField(upload_to='announcements/', blank=True, null=True, verbose_name="Banner Poster")
    is_active = models.BooleanField(default=True, verbose_name="Status Aktif")
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        verbose_name = "Pengumuman & Event"
        verbose_name_plural = "Agenda, Event & Pengumuman"
        ordering = ['-created_at']

    def __str__(self):
        return f"[{self.get_announcement_type_display()}] {self.title}"


# 6. MODEL MEDIA LIBRARY (PENYIMPANAN BERKAS)
class MediaAsset(models.Model):
    FILE_TYPE_CHOICES = [
        ('IMAGE', 'Gambar'),
        ('VIDEO', 'Video'),
        ('DOCUMENT', 'Dokumen PDF / File'),
    ]

    title = models.CharField(max_length=150, verbose_name="Nama Berkas")
    file = models.FileField(upload_to='library/%Y/%m/', verbose_name="File Unggahan")
    file_type = models.CharField(max_length=20, choices=FILE_TYPE_CHOICES, default='IMAGE', verbose_name="Tipe Berkas")
    uploaded_by = models.ForeignKey(User, on_delete=models.SET_NULL, null=True, blank=True)
    uploaded_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        verbose_name = "Media Library"
        verbose_name_plural = "Media Library"
        ordering = ['-uploaded_at']

    def __str__(self):
        return self.title


# 7. MODEL DATA INTERAKSI / PESAN MASUK
class ContactMessage(models.Model):
    sender_name = models.CharField(max_length=150, verbose_name="Nama Pengirim")
    sender_email = models.EmailField(blank=True, null=True, verbose_name="Email")
    sender_phone = models.CharField(max_length=25, blank=True, null=True, verbose_name="No. Telepon / WA")
    subject = models.CharField(max_length=200, default="Pertanyaan / Ulasan", verbose_name="Subjek")
    message = models.TextField(verbose_name="Isi Pesan / Ulasan")
    admin_reply = models.TextField(blank=True, null=True, verbose_name="Balasan Admin")
    is_read = models.BooleanField(default=False, verbose_name="Sudah Dibaca")
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        verbose_name = "Pesan Masuk"
        verbose_name_plural = "Kotak Masuk & Ulasan"
        ordering = ['-created_at']

    def __str__(self):
        return f"{self.sender_name} - {self.subject}"


# 8. MODEL PENGATURAN UMUM & OUTLET (SITE SETTINGS)
class SiteSetting(models.Model):
    site_name = models.CharField(max_length=100, default="Yellow Mango's", verbose_name="Nama Situs")
    tagline = models.CharField(
        max_length=255,
        default="Kesegaran mangga asli pilihan berkualitas tinggi di setiap tegukan dan gigitan untuk harimu.",
        verbose_name="Tagline Brand"
    )
    seo_description = models.TextField(blank=True, verbose_name="Deskripsi SEO")
    logo = models.ImageField(upload_to='settings/', blank=True, null=True, verbose_name="Logo Website")
    favicon = models.ImageField(upload_to='settings/', blank=True, null=True, verbose_name="Favicon")
    
    # Kontak & Outlet Tunggal (Makassar Pusat)
    phone_primary = models.CharField(max_length=30, default="0821-YELLOW-M", verbose_name="Nomor Layanan 1")
    phone_secondary = models.CharField(max_length=30, default="(0411) 123-4567", verbose_name="Nomor Layanan 2")
    outlet_name = models.CharField(max_length=100, default="Makassar Pusat", verbose_name="Nama Outlet Utama")
    outlet_address = models.TextField(
        default="Jl. Andi Pangeran Pettarani No. 45\nMakassar, Sulawesi Selatan",
        verbose_name="Alamat Outlet Utama"
    )
    instagram_url = models.URLField(blank=True, default="#", verbose_name="Link Instagram")
    tiktok_url = models.URLField(blank=True, default="#", verbose_name="Link TikTok")
    whatsapp_url = models.URLField(blank=True, default="#", verbose_name="Link WhatsApp")

    class Meta:
        verbose_name = "Pengaturan Website"
        verbose_name_plural = "Pengaturan Website"

    def __str__(self):
        return self.site_name


# 9. MODEL AKTIVITAS TERBARU (RECENT ACTIVITY LOG)
class ActivityLog(models.Model):
    user = models.ForeignKey(User, on_delete=models.SET_NULL, null=True, blank=True)
    action = models.CharField(max_length=255, verbose_name="Aktivitas")
    category = models.CharField(max_length=50, default="Sistem", verbose_name="Modul")
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        verbose_name = "Log Aktivitas"
        verbose_name_plural = "Aktivitas Terbaru"
        ordering = ['-created_at']

    def __str__(self):
        return f"{self.action} ({self.created_at.strftime('%d/%m/%Y %H:%M')})"