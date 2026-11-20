from django.contrib import admin
from django.urls import path
from django.conf import settings
from django.conf.urls.static import static
from store import views

urlpatterns = [
    # Halaman Publik
    path('', views.index, name='index'),
    path('menu/', views.menu_view, name='menu'),
    path('artikel/', views.articles_list_view, name='articles_list'),
    path('artikel/<slug:slug>/', views.article_detail_view, name='article_detail'),
    path('profil/', views.user_profile_view, name='user_profile'),
    path('menu/produk/<int:pk>/', views.product_detail_view, name='product_detail'),
    path('lokasi/', views.location_view, name='lokasi'),
    path('about/', views.about_view, name='about'),
    path('tulis-testimoni/', views.submit_testimonial, name='submit_testimonial'),

    # Sistem Keranjang Belanja (Shopping Cart - Django Sessions)
    path('cart/', views.cart_view, name='cart'),
    path('cart/add/<int:product_id>/', views.add_to_cart, name='add_to_cart'),
    path('cart/update/<str:item_key>/', views.update_cart, name='update_cart'),
    path('cart/remove/<str:item_key>/', views.remove_from_cart, name='remove_from_cart'),
    path('cart/clear/', views.clear_cart, name='clear_cart'),

    # Alur Checkout & Pesanan WhatsApp
    path('checkout/', views.checkout_view, name='checkout'),
    path('order-success/<str:order_number>/', views.order_success_view, name='order_success'),

    # Autentikasi Pengguna & Admin
    path('login/', views.custom_login_view, name='login'),
    path('logout/', views.custom_logout_view, name='logout'),
    path('custom-admin-login/', views.custom_admin_login, name='custom_admin_login'),

    # Legalitas & Informasi
    path('syarat-ketentuan/', views.terms_view, name='terms'),
    path('kebijakan-privasi/', views.privacy_view, name='privacy'),
    path('disclaimer/', views.disclaimer_view, name='disclaimer'),
    path('jam-operasional/', views.hours_view, name='hours'),
    path('faq/', views.faq_view, name='faq'),
    path('halaman/<slug:slug>/', views.page_detail, name='page_detail'),

    # Django Admin Default
    path('admin/', admin.site.urls),

    # Panel Admin Kustom (Yellow Mango's Dashboard)
    path('dashboard/', views.admin_dashboard, name='admin_dashboard'),
    path('dashboard/produk/', views.admin_products, name='admin_products'),
    path('dashboard/pesanan/', views.admin_orders, name='admin_orders'),
    path('dashboard/halaman/', views.admin_pages, name='admin_pages'),
    path('dashboard/artikel/', views.admin_articles, name='admin_articles'),
    path('dashboard/agenda/', views.admin_announcements, name='admin_announcements'),
    path('dashboard/media/', views.admin_media, name='admin_media'),
    path('dashboard/pengguna/', views.admin_users, name='admin_users'),
    path('dashboard/pesan/', views.admin_messages, name='admin_messages'),
    path('dashboard/pengaturan/', views.admin_settings, name='admin_settings'),
    path('dashboard/log/', views.admin_logs, name='admin_logs'),
]

if settings.DEBUG:
    urlpatterns += static(settings.MEDIA_URL, document_root=settings.MEDIA_ROOT)
    urlpatterns += static(settings.STATIC_URL, document_root=settings.STATIC_ROOT)
