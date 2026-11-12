from django.contrib import admin
from .models import Kategori, Produk, Outlet, Keranjang, Pesanan, DetailPesanan, Testimoni

admin.site.register(Kategori)
admin.site.register(Produk)
admin.site.register(Outlet)
admin.site.register(Keranjang)
admin.site.register(Pesanan)
admin.site.register(DetailPesanan)
admin.site.register(Testimoni)