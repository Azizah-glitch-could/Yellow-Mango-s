import uuid
from django.shortcuts import render, redirect, get_object_or_404
from django.contrib.auth import authenticate, login, logout
from django.contrib.auth.decorators import login_required
from django.contrib.auth.models import User
from django.contrib import messages
from django.utils.text import slugify
from .models import (
    Product,
    Order,
    OrderItem,
    Page,
    Article,
    Announcement,
    MediaAsset,
    ContactMessage,
    SiteSetting,
    ActivityLog,
)

def index(request):
    return render(request, 'index.html')

def menu_view(request):
    products = Product.objects.all().order_by('-id')
    return render(request, 'store/menu.html', {'products': products})


def custom_login_view(request):
    error_message = None
    default_mode = request.GET.get('mode', 'login') # Bisa mendeteksi jika ingin langsung ke mode sign up

    if request.method == 'POST':
        action = request.POST.get('action') # 'login' atau 'signup'
        username = request.POST.get('username')
        password = request.POST.get('password')

        if action == 'signup':
            # Proses Pendaftaran Akun Baru
            if User.objects.filter(username=username).exists():
                error_message = "Username sudah terdaftar, silakan gunakan yang lain."
                default_mode = 'signup'
            else:
                user = User.objects.create_user(username=username, password=password)
                login(request, user)
                return redirect('index')
        else:
            # Proses Masuk (Log In)
            user = authenticate(request, username=username, password=password)
            if user is not None:
                login(request, user)
                if user.is_staff or user.is_superuser:
                    return redirect('/admin/')
                else:
                    return redirect('index')
            else:
                error_message = "Username atau password salah!"
                default_mode = 'login'

    return render(request, 'store/login.html', {
        'error': error_message,
        'mode': default_mode
    })

def custom_admin_login(request):
    if request.method == 'POST':
        user_name = request.POST.get('username')
        pass_word = request.POST.get('password')
        user = authenticate(request, username=user_name, password=pass_word)

        # Pastikan user adalah superuser/staff
        if user is not None and user.is_staff:
            login(request, user)
            return redirect('admin_dashboard') # Lempar ke dashboard admin asli setelah login
        else:
            error = "Username atau password salah, atau Anda bukan admin."
            return render(request, 'store/custom_login.html', {'error': error})

    return render(request, 'store/custom_login.html')

@login_required(login_url='/custom-admin-login/')
def admin_dashboard(request):
    context = {
        'total_products': Product.objects.count(),
        'total_orders': Order.objects.count(),
        'total_users': User.objects.count(),
        'total_articles': Article.objects.count(),
        'recent_activities': ActivityLog.objects.all()[:6],
    }
    return render(request, 'store/admin_panel/dashboard.html', context)

@login_required(login_url='/custom-admin-login/')
def admin_products(request):
    if request.method == 'POST':
        action = request.POST.get('action')

        # TAMBAH MENU BARU
        if action == 'add':
            name = request.POST.get('name')
            category = request.POST.get('category', 'Lain-Lain')  # <-- Tangkap Kategori
            price = request.POST.get('price')
            description = request.POST.get('description')
            badge_primary = request.POST.get('badge_primary', 'NONE')
            badge_secondary = request.POST.get('badge_secondary', 'NONE')
            image = request.FILES.get('image')
            image_url = request.POST.get('image_url')
            is_available = request.POST.get('is_available') == 'on'

            Product.objects.create(
                name=name,
                category=category,  # <-- Simpan Kategori
                price=price,
                description=description,
                badge_primary=badge_primary,
                badge_secondary=badge_secondary,
                image=image,
                image_url=image_url,
                is_available=is_available
            )
            ActivityLog.objects.create(
                user=request.user,
                action=f"Menambahkan menu baru: {name} (Kategori: {category})",
                category="Produk & Menu"
            )
            messages.success(request, f"Menu '{name}' berhasil ditambahkan!")
            return redirect('admin_products')

        # EDIT MENU
        elif action == 'edit':
            product_id = request.POST.get('product_id')
            product = get_object_or_404(Product, id=product_id)
            product.name = request.POST.get('name')
            product.category = request.POST.get('category', product.category)  # <-- Perbarui Kategori
            product.price = request.POST.get('price')
            product.description = request.POST.get('description')
            product.badge_primary = request.POST.get('badge_primary', 'NONE')
            product.badge_secondary = request.POST.get('badge_secondary', 'NONE')
            product.image_url = request.POST.get('image_url')
            product.is_available = request.POST.get('is_available') == 'on'
            
            if request.FILES.get('image'):
                product.image = request.FILES.get('image')
                
            product.save()
            ActivityLog.objects.create(
                user=request.user,
                action=f"Memperbarui menu: {product.name}",
                category="Produk & Menu"
            )
            messages.success(request, f"Menu '{product.name}' berhasil diperbarui!")
            return redirect('admin_products')

        # HAPUS MENU
        elif action == 'delete':
            product_id = request.POST.get('product_id')
            product = get_object_or_404(Product, id=product_id)
            nama_menu = product.name
            product.delete()
            ActivityLog.objects.create(
                user=request.user,
                action=f"Menghapus menu: {nama_menu}",
                category="Produk & Menu"
            )
            messages.success(request, f"Menu '{nama_menu}' berhasil dihapus.")
            return redirect('admin_products')

    products = Product.objects.all().order_by('-id')
    context = {
        'products': products,
        'badge_choices': Product.BADGE_CHOICES,
    }
    return render(request, 'store/admin_panel/products.html', context)

@login_required(login_url='/custom-admin-login/')
def admin_orders(request):
    if request.method == 'POST':
        action = request.POST.get('action')

        # UBAH STATUS PESANAN LANGSUNG DARI TABEL
        if action == 'update_status':
            order_id = request.POST.get('order_id')
            new_status = request.POST.get('status')
            order = get_object_or_404(Order, id=order_id)
            order.status = new_status
            order.save()

            ActivityLog.objects.create(
                user=request.user,
                action=f"Mengubah status pesanan #{order.order_number} menjadi {order.get_status_display()}",
                category="Pesanan"
            )
            messages.success(request, f"Status pesanan #{order.order_number} diperbarui!")
            return redirect('admin_orders')

        # TAMBAH PESANAN MANUAL (MISAL PESANAN VIA WA / KASIR)
        elif action == 'add_manual':
            customer_name = request.POST.get('customer_name')
            customer_phone = request.POST.get('customer_phone')
            product_id = request.POST.get('product_id')
            quantity = int(request.POST.get('quantity', 1))
            notes = request.POST.get('notes', '')

            product = get_object_or_404(Product, id=product_id)
            total_amount = product.price * quantity
            order_number = f"YM-{uuid.uuid4().hex[:6].upper()}"

            order = Order.objects.create(
                order_number=order_number,
                customer_name=customer_name,
                customer_phone=customer_phone,
                notes=notes,
                total_amount=total_amount,
                status='PROCESSING'
            )

            OrderItem.objects.create(
                order=order,
                product=product,
                product_name=product.name,
                price=product.price,
                quantity=quantity
            )

            ActivityLog.objects.create(
                user=request.user,
                action=f"Mencatat pesanan manual #{order_number} ({customer_name})",
                category="Pesanan"
            )
            messages.success(request, f"Pesanan #{order_number} berhasil dicatat!")
            return redirect('admin_orders')

        # HAPUS PESANAN
        elif action == 'delete':
            order_id = request.POST.get('order_id')
            order = get_object_or_404(Order, id=order_id)
            no_pesanan = order.order_number
            order.delete()

            ActivityLog.objects.create(
                user=request.user,
                action=f"Menghapus pesanan #{no_pesanan}",
                category="Pesanan"
            )
            messages.success(request, f"Pesanan #{no_pesanan} telah dihapus.")
            return redirect('admin_orders')

    # Filter status jika ada parameter ?status=...
    status_filter = request.GET.get('status', 'ALL')
    if status_filter != 'ALL':
        orders = Order.objects.filter(status=status_filter).prefetch_related('items')
    else:
        orders = Order.objects.all().prefetch_related('items')

    context = {
        'orders': orders,
        'status_choices': Order.STATUS_CHOICES,
        'status_filter': status_filter,
        'products': Product.objects.filter(is_available=True),
    }
    return render(request, 'store/admin_panel/orders.html', context)

@login_required(login_url='/custom-admin-login/')
def admin_pages(request):
    if request.method == 'POST':
        action = request.POST.get('action')

        # TAMBAH HALAMAN BARU
        if action == 'add':
            title = request.POST.get('title')
            slug_input = request.POST.get('slug')
            content = request.POST.get('content')
            is_published = request.POST.get('is_published') == 'on'

            slug = slugify(slug_input) if slug_input else slugify(title)

            Page.objects.create(
                title=title,
                slug=slug,
                content=content,
                is_published=is_published
            )
            ActivityLog.objects.create(
                user=request.user,
                action=f"Membuat halaman baru: {title}",
                category="Page Builder"
            )
            messages.success(request, f"Halaman '{title}' berhasil dibuat!")
            return redirect('admin_pages')

        # EDIT HALAMAN
        elif action == 'edit':
            page_id = request.POST.get('page_id')
            page = get_object_or_404(Page, id=page_id)
            page.title = request.POST.get('title')
            slug_input = request.POST.get('slug')
            page.slug = slugify(slug_input) if slug_input else slugify(page.title)
            page.content = request.POST.get('content')
            page.is_published = request.POST.get('is_published') == 'on'
            page.save()

            ActivityLog.objects.create(
                user=request.user,
                action=f"Memperbarui halaman: {page.title}",
                category="Page Builder"
            )
            messages.success(request, f"Halaman '{page.title}' berhasil diperbarui!")
            return redirect('admin_pages')

        # HAPUS HALAMAN
        elif action == 'delete':
            page_id = request.POST.get('page_id')
            page = get_object_or_404(Page, id=page_id)
            judul = page.title
            page.delete()

            ActivityLog.objects.create(
                user=request.user,
                action=f"Menghapus halaman: {judul}",
                category="Page Builder"
            )
            messages.success(request, f"Halaman '{judul}' telah dihapus.")
            return redirect('admin_pages')

    pages = Page.objects.all().order_by('-updated_at')
    return render(request, 'store/admin_panel/pages.html', {'pages': pages})


# VIEW PUBLIK AGAR HALAMAN YANG DIBUAT BISA DIBUKA PENGUNJUNG
def page_detail(request, slug):
    page = get_object_or_404(Page, slug=slug, is_published=True)
    return render(request, 'store/page_detail.html', {'page': page})

@login_required(login_url='/custom-admin-login/')
def admin_articles(request):
    if request.method == 'POST':
        action = request.POST.get('action')

        # TAMBAH ARTIKEL BARU
        if action == 'add':
            title = request.POST.get('title')
            category = request.POST.get('category', 'Tips & Kuliner')
            tags = request.POST.get('tags', '')
            content = request.POST.get('content')
            thumbnail = request.FILES.get('thumbnail')
            is_published = request.POST.get('is_published') == 'on'

            Article.objects.create(
                title=title,
                category=category,
                tags=tags,
                content=content,
                thumbnail=thumbnail,
                author=request.user,
                is_published=is_published
            )
            ActivityLog.objects.create(
                user=request.user,
                action=f"Membuat artikel baru: {title}",
                category="Artikel & Blog"
            )
            messages.success(request, f"Artikel '{title}' berhasil diterbitkan!")
            return redirect('admin_articles')

        # EDIT ARTIKEL
        elif action == 'edit':
            article_id = request.POST.get('article_id')
            article = get_object_or_404(Article, id=article_id)
            article.title = request.POST.get('title')
            article.category = request.POST.get('category', 'Tips & Kuliner')
            article.tags = request.POST.get('tags', '')
            article.content = request.POST.get('content')
            article.is_published = request.POST.get('is_published') == 'on'

            if request.FILES.get('thumbnail'):
                article.thumbnail = request.FILES.get('thumbnail')

            article.save()
            ActivityLog.objects.create(
                user=request.user,
                action=f"Memperbarui artikel: {article.title}",
                category="Artikel & Blog"
            )
            messages.success(request, f"Artikel '{article.title}' berhasil diperbarui!")
            return redirect('admin_articles')

        # HAPUS ARTIKEL
        elif action == 'delete':
            article_id = request.POST.get('article_id')
            article = get_object_or_404(Article, id=article_id)
            jud = article.title
            article.delete()

            ActivityLog.objects.create(
                user=request.user,
                action=f"Menghapus artikel: {jud}",
                category="Artikel & Blog"
            )
            messages.success(request, f"Artikel '{jud}' telah dihapus.")
            return redirect('admin_articles')

    articles = Article.objects.all().order_by('-created_at')
    return render(request, 'store/admin_panel/articles.html', {'articles': articles})

@login_required(login_url='/custom-admin-login/')
def admin_announcements(request):
    if request.method == 'POST':
        action = request.POST.get('action')

        # TAMBAH PENGUMUMAN / EVENT BARU
        if action == 'add':
            title = request.POST.get('title')
            announcement_type = request.POST.get('announcement_type', 'PROMO')
            description = request.POST.get('description')
            event_date = request.POST.get('event_date') or None
            image = request.FILES.get('image')
            is_active = request.POST.get('is_active') == 'on'

            Announcement.objects.create(
                title=title,
                announcement_type=announcement_type,
                description=description,
                event_date=event_date,
                image=image,
                is_active=is_active
            )
            ActivityLog.objects.create(
                user=request.user,
                action=f"Membuat pengumuman/event baru: {title}",
                category="Event & Promo"
            )
            messages.success(request, f"Pengumuman '{title}' berhasil ditambahkan!")
            return redirect('admin_announcements')

        # EDIT PENGUMUMAN / EVENT
        elif action == 'edit':
            ann_id = request.POST.get('announcement_id')
            ann = get_object_or_404(Announcement, id=ann_id)
            ann.title = request.POST.get('title')
            ann.announcement_type = request.POST.get('announcement_type', 'PROMO')
            ann.description = request.POST.get('description')
            
            event_date = request.POST.get('event_date')
            ann.event_date = event_date if event_date else None
            ann.is_active = request.POST.get('is_active') == 'on'

            if request.FILES.get('image'):
                ann.image = request.FILES.get('image')

            ann.save()
            ActivityLog.objects.create(
                user=request.user,
                action=f"Memperbarui pengumuman/event: {ann.title}",
                category="Event & Promo"
            )
            messages.success(request, f"Pengumuman '{ann.title}' berhasil diperbarui!")
            return redirect('admin_announcements')

        # HAPUS PENGUMUMAN / EVENT
        elif action == 'delete':
            ann_id = request.POST.get('announcement_id')
            ann = get_object_or_404(Announcement, id=ann_id)
            judul = ann.title
            ann.delete()

            ActivityLog.objects.create(
                user=request.user,
                action=f"Menghapus pengumuman/event: {judul}",
                category="Event & Promo"
            )
            messages.success(request, f"Pengumuman '{judul}' telah dihapus.")
            return redirect('admin_announcements')

    announcements = Announcement.objects.all().order_by('-created_at')
    context = {
        'announcements': announcements,
        'type_choices': Announcement.TYPE_CHOICES,
    }
    return render(request, 'store/admin_panel/announcements.html', context)

@login_required(login_url='/custom-admin-login/')
def admin_media(request):
    if request.method == 'POST':
        action = request.POST.get('action')

        if action == 'upload':
            title = request.POST.get('title')
            file_type = request.POST.get('file_type', 'IMAGE')
            file_obj = request.FILES.get('file')

            if file_obj:
                MediaAsset.objects.create(
                    title=title,
                    file_type=file_type,
                    file=file_obj,
                    uploaded_by=request.user
                )
                ActivityLog.objects.create(
                    user=request.user,
                    action=f"Mengunggah berkas media: {title}",
                    category="Media Library"
                )
                messages.success(request, f"Berkas '{title}' berhasil diunggah!")
            else:
                messages.error(request, "Pilih berkas yang ingin diunggah terlebih dahulu.")
            
            return redirect('admin_media')

        elif action == 'delete':
            media_id = request.POST.get('media_id')
            media = get_object_or_404(MediaAsset, id=media_id)
            nama_berkas = media.title
            media.delete()

            ActivityLog.objects.create(
                user=request.user,
                action=f"Menghapus berkas media: {nama_berkas}",
                category="Media Library"
            )
            messages.success(request, f"Berkas '{nama_berkas}' telah dihapus.")
            return redirect('admin_media')

    media_list = MediaAsset.objects.all().order_by('-uploaded_at')
    
    # Pastikan variabel ini bernama type_choices
    type_choices = [
        ('IMAGE', 'Gambar'),
        ('VIDEO', 'Video'),
        ('DOCUMENT', 'Dokumen PDF / File'),
    ]

    context = {
        'media_list': media_list,
        'type_choices': type_choices,
    }
    return render(request, 'store/admin_panel/media.html', context)

@login_required(login_url='/custom-admin-login/')
def admin_users(request):
    if request.method == 'POST':
        action = request.POST.get('action')

        # TAMBAH AKUN PENGGUNA / STAF BARU
        if action == 'add':
            username = request.POST.get('username')
            email = request.POST.get('email')
            password = request.POST.get('password')
            is_staff = request.POST.get('is_staff') == 'on'
            is_superuser = request.POST.get('is_superuser') == 'on'

            if User.objects.filter(username=username).exists():
                messages.error(request, f"Username '{username}' sudah digunakan.")
            else:
                user = User.objects.create_user(username=username, email=email, password=password)
                user.is_staff = is_staff
                user.is_superuser = is_superuser
                user.save()

                ActivityLog.objects.create(
                    user=request.user,
                    action=f"Membuat akun pengguna/staf baru: {username}",
                    category="Pengguna & Staf"
                )
                messages.success(request, f"Akun '{username}' berhasil ditambahkan!")
            return redirect('admin_users')

        # HAPUS AKUN
        elif action == 'delete':
            user_id = request.POST.get('user_id')
            target_user = get_object_or_404(User, id=user_id)
            
            if target_user == request.user:
                messages.error(request, "Anda tidak dapat menghapus akun yang sedang Anda gunakan.")
            else:
                uname = target_user.username
                target_user.delete()
                ActivityLog.objects.create(
                    user=request.user,
                    action=f"Menghapus akun pengguna: {uname}",
                    category="Pengguna & Staf"
                )
                messages.success(request, f"Akun '{uname}' telah dihapus.")
            return redirect('admin_users')

    users_list = User.objects.all().order_by('-date_joined')
    return render(request, 'store/admin_panel/users.html', {'users_list': users_list})

@login_required(login_url='/custom-admin-login/')
def admin_messages(request):
    if request.method == 'POST':
        action = request.POST.get('action')

        # TANDAI SUDAH DIBACA / BELUM
        if action == 'toggle_read':
            msg_id = request.POST.get('message_id')
            msg = get_object_or_404(ContactMessage, id=msg_id)
            # Toggle status is_read jika ada field-nya, atau set True
            if hasattr(msg, 'is_read'):
                msg.is_read = not msg.is_read
                msg.save()
            messages.success(request, f"Status pesan dari {msg.name} diperbarui!")
            return redirect('admin_messages')

        # HAPUS PESAN
        elif action == 'delete':
            msg_id = request.POST.get('message_id')
            msg = get_object_or_404(ContactMessage, id=msg_id)
            pengirim = msg.name
            msg.delete()

            ActivityLog.objects.create(
                user=request.user,
                action=f"Menghapus pesan dari: {pengirim}",
                category="Pesan Masuk"
            )
            messages.success(request, f"Pesan dari '{pengirim}' telah dihapus.")
            return redirect('admin_messages')

    messages_list = ContactMessage.objects.all().order_by('-created_at')
    return render(request, 'store/admin_panel/messages.html', {'messages_list': messages_list})

@login_required(login_url='/custom-admin-login/')
def admin_settings(request):
    # Mengambil objek pengaturan pertama (atau buat baru tanpa parameter field spesifik yang belum tentu ada)
    try:
        setting = SiteSetting.objects.first()
        if not setting:
            setting = SiteSetting.objects.create()
    except Exception:
        setting = None

    if request.method == 'POST':
        # Simpan pembaruan sesuai field yang tersedia di form
        if setting:
            if 'site_name' in request.POST:
                setting.site_name = request.POST.get('site_name')
            if 'tagline' in request.POST:
                setting.tagline = request.POST.get('tagline')
            if request.FILES.get('logo'):
                setting.logo = request.FILES.get('logo')
            setting.save()

        ActivityLog.objects.create(
            user=request.user,
            action="Memperbarui pengaturan umum situs",
            category="Pengaturan Sistem"
        )
        messages.success(request, "Pengaturan website berhasil diperbarui!")
        return redirect('admin_settings')

    return render(request, 'store/admin_panel/settings.html', {'setting': setting})

import csv
from django.http import HttpResponse

import csv
from django.http import HttpResponse

@login_required(login_url='/custom-admin-login/')
def admin_logs(request):
    # Fitur Ekspor Backup Data Log ke CSV
    if request.GET.get('action') == 'export_csv':
        response = HttpResponse(content_type='text/csv')
        response['Content-Disposition'] = 'attachment; filename="activity_logs_yellow_mangos.csv"'
        
        writer = csv.writer(response)
        writer.writerow(['ID', 'Pengguna', 'Aksi', 'Kategori', 'Waktu'])
        
        # Diubah dari -timestamp menjadi -created_at
        logs = ActivityLog.objects.all().order_by('-created_at')
        for log in logs:
            username = log.user.username if log.user else "System"
            writer.writerow([log.id, username, log.action, log.category, log.created_at])
            
        return response

    # Diubah dari -timestamp menjadi -created_at
    logs_list = ActivityLog.objects.all().order_by('-created_at')
    return render(request, 'store/admin_panel/logs.html', {'logs_list': logs_list})

def location_view(request):
    return render(request, 'store/location.html')

def about_view(request):
    return render(request, 'store/about.html')

def terms_view(request):
    return render(request, 'store/legal/terms.html')

def privacy_view(request):
    return render(request, 'store/legal/privacy.html')

def disclaimer_view(request):
    return render(request, 'store/legal/disclaimer.html')

def hours_view(request):
    return render(request, 'store/legal/hours.html')

def faq_view(request):
    return render(request, 'store/legal/faq.html')