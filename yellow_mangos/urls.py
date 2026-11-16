from django.contrib import admin
from django.urls import path
from django.conf import settings
from django.conf.urls.static import static
from store import views  # Mengimpor views dari aplikasi store
from store.views import index, menu_view, location_view, about_view

urlpatterns = [
    path('', views.index, name='index'),
    path('menu/', views.menu_view, name='menu'),
    path('lokasi/', location_view, name='lokasi'),
    path('about/', about_view, name='about'),
    path('syarat-ketentuan/', terms_view, name='terms'),
    path('kebijakan-privasi/', privacy_view, name='privacy'),
    path('disclaimer/', disclaimer_view, name='disclaimer'),
    path('jam-operasional/', hours_view, name='hours'),
    path('faq/', faq_view, name='faq'),
    path('login/', views.custom_login_view, name='login'),
    path('custom-admin-login/', views.custom_admin_login, name='custom_admin_login'),
    path('admin/', admin.site.urls),
    path('dashboard/', views.admin_dashboard, name='admin_dashboard'),
    path('dashboard/produk/', views.admin_products, name='admin_products'),
    path('dashboard/pesanan/', views.admin_orders, name='admin_orders'),
    path('dashboard/halaman/', views.admin_pages, name='admin_pages'),
    path('halaman/<slug:slug>/', views.page_detail, name='page_detail'),
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