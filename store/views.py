import uuid
import urllib.parse
import csv
from django.shortcuts import render, redirect, get_object_or_404
from django.contrib.auth import authenticate, login, logout, update_session_auth_hash
from django.contrib.auth.decorators import login_required
from django.contrib.auth.models import User
from django.contrib import messages
from django.utils.text import slugify
from django.utils import timezone
from django.http import HttpResponse, JsonResponse
from django.db.models import Q
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
    UserProfile,
)


# ==========================================
# 1. VIEW HALAMAN PUBLIK UTAMA
# ==========================================

def index(request):
    """
    Landing page utama Yellow Mango's:
    - Menampilkan produk terlaris / rekomendasi dari database
    - Menampilkan ulasan & testimoni dari database (ContactMessage)
    - Menampilkan artikel / blog terbaru dari database (Article)
    - Menampilkan pengumuman & promo aktif dari database (Announcement)
    """
    # 1. Produk Terlaris (Best Seller) dari Database
    best_sellers = Product.objects.filter(is_available=True).filter(
        Q(badge_primary='BEST_SELLER') | Q(badge_secondary='BEST_SELLER')
    )[:6]

    # Fallback jika belum ada yang diset Best Seller
    if not best_sellers.exists():
        best_sellers = Product.objects.filter(is_available=True)[:6]

    # 2. Katalog Preview
    all_products = Product.objects.filter(is_available=True)[:8]

    # 3. Artikel & Blog Terbaru (yang diterbitkan via Admin Panel)
    latest_articles = Article.objects.filter(is_published=True).order_by('-created_at')[:3]

    # 4. Pengumuman / Promo Aktif
    announcements = Announcement.objects.filter(is_active=True).order_by('-created_at')[:3]

    # 5. Testimoni dari Pelanggan (ContactMessage)
    testimonials = ContactMessage.objects.filter(is_read=True).order_by('-created_at')[:6]
    if not testimonials.exists():
        testimonials = ContactMessage.objects.all().order_by('-created_at')[:4]

    # Kategori untuk tombol filter cepat di landing page
    categories = [cat[0] for cat in Product.CATEGORY_CHOICES]

    context = {
        'best_sellers': best_sellers,
        'all_products': all_products,
        'latest_articles': latest_articles,
        'announcements': announcements,
        'testimonials': testimonials,
        'categories': categories,
    }
    return render(request, 'index.html', context)


def menu_view(request):
    """
    Katalog Menu lengkap dengan fitur:
    - Kolom Pencarian (Search Bar) berdasarkan nama & deskripsi
    - Filter Kategori (Smoothies, Dessert, Coffee, dll.)
    - Filter Label/Badge (Best Seller, Promo, Baru)
    - Pengurutan (Harga terendah/tertinggi, terpopuler, terbaru)
    """
    q = request.GET.get('q', '').strip()
    category = request.GET.get('category', '').strip()
    badge = request.GET.get('badge', '').strip()
    sort = request.GET.get('sort', 'default')

    queryset = Product.objects.filter(is_available=True)

    # 1. Filter Pencarian
    if q:
        queryset = queryset.filter(Q(name__icontains=q) | Q(description__icontains=q))

    # 2. Filter Kategori
    if category and category != 'ALL':
        queryset = queryset.filter(category=category)

    # 3. Filter Badge / Label
    if badge and badge != 'ALL':
        queryset = queryset.filter(Q(badge_primary=badge) | Q(badge_secondary=badge))

    # 4. Pengurutan
    if sort == 'price_asc':
        queryset = queryset.order_by('price')
    elif sort == 'price_desc':
        queryset = queryset.order_by('-price')
    elif sort == 'newest':
        queryset = queryset.order_by('-created_at')
    elif sort == 'popular':
        queryset = queryset.filter(badge_primary='BEST_SELLER').union(queryset)
    else:
        queryset = queryset.order_by('-id')

    # Daftar kategori unik dari model pilihan
    category_list = [choice[0] for choice in Product.CATEGORY_CHOICES]

    context = {
        'products': queryset,
        'category_list': category_list,
        'selected_category': category,
        'selected_badge': badge,
        'selected_sort': sort,
        'search_query': q,
        'total_found': queryset.count(),
    }
    return render(request, 'store/menu.html', context)


def product_detail_view(request, pk):
    """
    Halaman Detail Produk:
    - Deskripsi lengkap
    - Pilihan variasi ukuran (Reguler / Large)
    - Pilihan level gula & level es
    - Input catatan khusus & jumlah pesanan
    - Tombol Tambah ke Keranjang & Beli Sekarang
    """
    product = get_object_or_404(Product, pk=pk, is_available=True)
    related_products = Product.objects.filter(category=product.category, is_available=True).exclude(pk=pk)[:4]

    context = {
        'product': product,
        'related_products': related_products,
    }
    return render(request, 'store/product_detail.html', context)


# ==========================================
# 2. SISTEM KERANJANG BELANJA (DJANGO SESSIONS)
# ==========================================

def add_to_cart(request, product_id):
    """
    Menambahkan item ke keranjang belanja pengguna menggunakan Django Session.
    Mendukung opsi ukuran, level gula, level es, catatan khusus, dan jumlah (qty).
    Mendukung request AJAX (JSON) dan formulir normal.
    """
    product = get_object_or_404(Product, id=product_id, is_available=True)

    if request.method == 'POST':
        try:
            quantity = int(request.POST.get('quantity', 1))
            if quantity < 1:
                quantity = 1
        except (ValueError, TypeError):
            quantity = 1

        size = request.POST.get('size', 'Reguler')
        sugar = request.POST.get('sugar', 'Normal')
        ice = request.POST.get('ice', 'Normal')
        notes = request.POST.get('notes', '').strip()
        direct_checkout = request.POST.get('direct_checkout') == 'true'
    else:
        quantity = 1
        size = 'Reguler'
        sugar = 'Normal'
        ice = 'Normal'
        notes = ''
        direct_checkout = False

    # Tambahan biaya jika memilih Large (+ Rp 4.000)
    extra_price = 4000 if size == 'Large' else 0
    unit_price = float(product.price) + extra_price

    # Buat item key unik berdasarkan variasi yang dipilih
    clean_notes = notes.replace(' ', '_')[:20]
    item_key = f"{product.id}_{size}_{sugar}_{ice}_{clean_notes}"

    cart = request.session.get('cart', {})
    if not isinstance(cart, dict):
        cart = {}

    image_url = product.image.url if product.image else (product.image_url or '/static/images/mangga.jpg')

    if item_key in cart:
        cart[item_key]['quantity'] += quantity
        cart[item_key]['subtotal'] = cart[item_key]['quantity'] * cart[item_key]['price']
    else:
        cart[item_key] = {
            'product_id': product.id,
            'name': product.name,
            'price': unit_price,
            'base_price': float(product.price),
            'quantity': quantity,
            'size': size,
            'sugar': sugar,
            'ice': ice,
            'notes': notes,
            'image': image_url,
            'subtotal': unit_price * quantity,
        }

    request.session['cart'] = cart
    request.session.modified = True

    # Hitung total sekarang
    total_qty = sum(item.get('quantity', 1) for item in cart.values())
    total_amount = sum(item.get('subtotal', 0) for item in cart.values())

    # Jika langsung klik 'Beli Sekarang'
    if direct_checkout:
        return redirect('checkout')

    # Jika request berupa AJAX
    if request.headers.get('x-requested-with') == 'XMLHttpRequest' or request.GET.get('ajax') == '1':
        return JsonResponse({
            'success': True,
            'message': f"'{product.name}' berhasil dimasukkan ke keranjang!",
            'cart_count': total_qty,
            'cart_total': total_amount,
        })

    messages.success(request, f"'{product.name}' ({size}) berhasil dimasukkan ke keranjang belanja!")
    next_url = request.META.get('HTTP_REFERER') or '/cart/'
    return redirect(next_url)


def update_cart(request, item_key):
    """
    Mengubah kuantitas item dalam keranjang belanja (+, -, atau set manual).
    """
    cart = request.session.get('cart', {})
    if not isinstance(cart, dict):
        cart = {}

    if item_key in cart:
        action = request.POST.get('action') or request.GET.get('action')
        if action == 'increase':
            cart[item_key]['quantity'] += 1
        elif action == 'decrease':
            cart[item_key]['quantity'] -= 1
            if cart[item_key]['quantity'] <= 0:
                del cart[item_key]
        elif action == 'set':
            try:
                new_qty = int(request.POST.get('quantity', 1))
                if new_qty <= 0:
                    del cart[item_key]
                else:
                    cart[item_key]['quantity'] = new_qty
            except (ValueError, TypeError):
                pass

        if item_key in cart:
            cart[item_key]['subtotal'] = cart[item_key]['quantity'] * cart[item_key]['price']

        request.session['cart'] = cart
        request.session.modified = True

    total_qty = sum(item.get('quantity', 1) for item in cart.values())
    total_amount = sum(item.get('subtotal', 0) for item in cart.values())

    if request.headers.get('x-requested-with') == 'XMLHttpRequest':
        return JsonResponse({
            'success': True,
            'cart_count': total_qty,
            'cart_total': total_amount,
            'item_subtotal': cart.get(item_key, {}).get('subtotal', 0),
            'item_quantity': cart.get(item_key, {}).get('quantity', 0),
        })

    return redirect('cart')


def remove_from_cart(request, item_key):
    """
    Menghapus satu item dari keranjang belanja.
    """
    cart = request.session.get('cart', {})
    if isinstance(cart, dict) and item_key in cart:
        item_name = cart[item_key].get('name', 'Menu')
        del cart[item_key]
        request.session['cart'] = cart
        request.session.modified = True
        messages.info(request, f"'{item_name}' telah dihapus dari keranjang belanja.")

    if request.headers.get('x-requested-with') == 'XMLHttpRequest':
        total_qty = sum(item.get('quantity', 1) for item in cart.values())
        total_amount = sum(item.get('subtotal', 0) for item in cart.values())
        return JsonResponse({
            'success': True,
            'cart_count': total_qty,
            'cart_total': total_amount,
        })

    return redirect('cart')


def clear_cart(request):
    """
    Mengosongkan seluruh keranjang belanja.
    """
    request.session['cart'] = {}
    request.session.modified = True
    messages.info(request, "Keranjang belanja telah dikosongkan.")
    return redirect('cart')


def cart_view(request):
    """
    Halaman utama keranjang belanja (Shopping Cart View).
    """
    cart = request.session.get('cart', {})
    if not isinstance(cart, dict):
        cart = {}

    subtotal = sum(item.get('subtotal', 0) for item in cart.values())
    total_items = sum(item.get('quantity', 1) for item in cart.values())

    context = {
        'cart': cart,
        'subtotal': subtotal,
        'total_items': total_items,
        'grand_total': subtotal,
    }
    return render(request, 'store/cart.html', context)


# ==========================================
# 3. ALUR CHECKOUT & INTEGRASI WHATSAPP
# ==========================================

def checkout_view(request):
    """
    Halaman formulir Checkout:
    - Merekap pesanan dari keranjang belanja
    - Mengisi data nama pemesan, kontak WhatsApp, tipe pesanan, alamat, dan catatan
    - Menyimpan pesanan ke database (Order & OrderItem)
    - Otomatis membuat format pesan WhatsApp rapi ke nomor WhatsApp Admin
    """
    cart = request.session.get('cart', {})
    if not isinstance(cart, dict) or not cart:
        messages.warning(request, "Keranjang belanja Anda masih kosong. Silakan pilih menu terlebih dahulu.")
        return redirect('menu')

    subtotal = sum(item.get('subtotal', 0) for item in cart.values())
    total_items = sum(item.get('quantity', 1) for item in cart.values())

    if request.method == 'POST':
        customer_name = request.POST.get('customer_name', '').strip()
        customer_phone = request.POST.get('customer_phone', '').strip()
        order_type = request.POST.get('order_type', 'Delivery')
        delivery_address = request.POST.get('delivery_address', '').strip()
        notes = request.POST.get('notes', '').strip()

        if not customer_name or not customer_phone:
            messages.error(request, "Nama pemesan dan Nomor WhatsApp aktif wajib diisi!")
            return render(request, 'store/checkout.html', {
                'cart': cart,
                'subtotal': subtotal,
                'grand_total': subtotal,
                'customer_name': customer_name,
                'customer_phone': customer_phone,
                'order_type': order_type,
                'delivery_address': delivery_address,
                'notes': notes,
            })

        # Generate Kode Pesanan Unik: YM-YYYYMMDD-XXXX
        order_number = f"YM-{timezone.now().strftime('%Y%m%d')}-{uuid.uuid4().hex[:5].upper()}"

        # Catatan lengkap untuk order
        full_notes_parts = [f"Tipe: {order_type}"]
        if order_type == 'Delivery' and delivery_address:
            full_notes_parts.append(f"Alamat: {delivery_address}")
        if notes:
            full_notes_parts.append(f"Catatan: {notes}")
        combined_notes = " | ".join(full_notes_parts)

        # 1. Simpan ke Database Model Order
        order = Order.objects.create(
            order_number=order_number,
            customer=request.user if request.user.is_authenticated else None,
            customer_name=customer_name,
            customer_phone=customer_phone,
            notes=combined_notes,
            total_amount=int(subtotal),
            status='PENDING'
        )

        # 2. Simpan Item Pesanan ke OrderItem & susun teks pesan WA
        item_text_list = []
        for key, item in cart.items():
            product_obj = Product.objects.filter(id=item.get('product_id')).first()
            item_desc = f"{item['name']} ({item.get('size', 'Reguler')})"
            
            sub_options = []
            if item.get('sugar') and item.get('sugar') != 'Normal':
                sub_options.append(f"Gula: {item['sugar']}")
            if item.get('ice') and item.get('ice') != 'Normal':
                sub_options.append(f"Es: {item['ice']}")
            if item.get('notes'):
                sub_options.append(f"Cat: {item['notes']}")
            if sub_options:
                item_desc += f" [{', '.join(sub_options)}]"

            OrderItem.objects.create(
                order=order,
                product=product_obj,
                product_name=item_desc,
                price=int(item['price']),
                quantity=int(item['quantity'])
            )

            item_text_list.append(
                f"• {item['quantity']}x {item_desc} @ Rp {int(item['price']):,} = Rp {int(item['subtotal']):,}"
            )

        # 3. Catat Log Aktivitas & Update Profil User
        if request.user.is_authenticated:
            profile, _ = UserProfile.objects.get_or_create(user=request.user)
            if customer_phone and not profile.phone:
                profile.phone = customer_phone
            if delivery_address and not profile.default_address:
                profile.default_address = delivery_address
            profile.save()

        ActivityLog.objects.create(
            user=request.user if request.user.is_authenticated else None,
            action=f"Pesanan baru #{order.order_number} dari {customer_name} (Rp {order.total_amount:,})",
            category="Pesanan"
        )

        # 4. Kosongkan keranjang belanja di session
        request.session['cart'] = {}
        request.session.modified = True

        # 5. Dapatkan nomor WhatsApp Admin
        setting = SiteSetting.objects.first()
        raw_admin_wa = setting.phone_primary if setting and setting.phone_primary else "0821-YELLOW-M"
        if setting and setting.whatsapp_url and setting.whatsapp_url not in ('#', ''):
            # Jika admin mengisi URL WA lengkap
            clean_admin_phone = ''.join(c for c in setting.whatsapp_url if c.isdigit())
        else:
            # Bersihkan karakter non-digit
            clean_admin_phone = ''.join(c for c in raw_admin_wa if c.isdigit())
        
        # Format ke 62 jika diawali 0
        if clean_admin_phone.startswith('0'):
            clean_admin_phone = '62' + clean_admin_phone[1:]
        elif not clean_admin_phone.startswith('62') and clean_admin_phone:
            clean_admin_phone = '62' + clean_admin_phone
        if not clean_admin_phone:
            clean_admin_phone = "6282193557890"

        # 6. Format Pesan WhatsApp yang Rapi & Profesional
        items_formatted = "\n".join(item_text_list)
        address_info = f"\n*Alamat Pengiriman:* {delivery_address}" if (order_type == 'Delivery' and delivery_address) else ""
        notes_info = f"\n*Catatan Pemesan:* {notes}" if notes else ""

        wa_text = (
            f"🥭 *PESANAN BARU - YELLOW MANGO'S* 🥭\n"
            f"━━━━━━━━━━━━━━━━━━━━━━━━━━\n"
            f"*No. Pesanan:* #{order.order_number}\n"
            f"*Nama:* {customer_name}\n"
            f"*No. WhatsApp:* {customer_phone}\n"
            f"*Tipe Layanan:* {order_type}"
            f"{address_info}"
            f"{notes_info}\n"
            f"━━━━━━━━━━━━━━━━━━━━━━━━━━\n"
            f"*Rincian Menu:*\n"
            f"{items_formatted}\n"
            f"━━━━━━━━━━━━━━━━━━━━━━━━━━\n"
            f"*TOTAL PEMBAYARAN:* Rp {order.total_amount:,}\n"
            f"*Status:* Menunggu Konfirmasi Dapur\n"
            f"━━━━━━━━━━━━━━━━━━━━━━━━━━\n"
            f"Halo Admin Yellow Mango's Makassar, saya ingin memesan menu di atas. "
            f"Mohon konfirmasi ketersediaan dan detail pembayarannya ya. Terima kasih! 💛"
        )

        encoded_wa_msg = urllib.parse.quote(wa_text)
        wa_url = f"https://api.whatsapp.com/send?phone={clean_admin_phone}&text={encoded_wa_msg}"

        request.session['last_wa_url'] = wa_url
        return redirect('order_success', order_number=order.order_number)

    # Inisialisasi data form jika user sudah login
    prefilled_name = ''
    prefilled_phone = ''
    prefilled_address = ''
    if request.user.is_authenticated:
        prefilled_name = f"{request.user.first_name} {request.user.last_name}".strip() or request.user.username
        profile, _ = UserProfile.objects.get_or_create(user=request.user)
        prefilled_phone = profile.phone or ''
        prefilled_address = profile.default_address or ''

    context = {
        'cart': cart,
        'subtotal': subtotal,
        'grand_total': subtotal,
        'total_items': total_items,
        'prefilled_name': prefilled_name,
        'prefilled_phone': prefilled_phone,
        'prefilled_address': prefilled_address,
    }
    return render(request, 'store/checkout.html', context)


def order_success_view(request, order_number):
    """
    Halaman Konfirmasi Pesanan Sukses:
    - Memberikan ucapan terima kasih & rincian pesanan
    - Menampilkan tombol langsung menuju Chat WhatsApp Admin
    """
    order = get_object_or_404(Order, order_number=order_number)
    wa_url = request.session.get('last_wa_url')

    # Jika sesi WA URL hilang, buat ulang dari data order
    if not wa_url:
        setting = SiteSetting.objects.first()
        raw_admin_wa = setting.phone_primary if setting and setting.phone_primary else "0821-YELLOW-M"
        clean_admin_phone = ''.join(c for c in raw_admin_wa if c.isdigit())
        if clean_admin_phone.startswith('0'):
            clean_admin_phone = '62' + clean_admin_phone[1:]
        elif not clean_admin_phone.startswith('62') and clean_admin_phone:
            clean_admin_phone = '62' + clean_admin_phone
        if not clean_admin_phone:
            clean_admin_phone = "6282193557890"

        wa_text = (
            f"🥭 *PESANAN #{order.order_number} - YELLOW MANGO'S*\n"
            f"Nama: {order.customer_name}\n"
            f"Total: Rp {order.total_amount:,}\n"
            f"Halo Admin, mohon konfirmasi pesanan saya #{order.order_number} ya!"
        )
        wa_url = f"https://api.whatsapp.com/send?phone={clean_admin_phone}&text={urllib.parse.quote(wa_text)}"

    context = {
        'order': order,
        'wa_url': wa_url,
    }
    return render(request, 'store/order_success.html', context)


def submit_testimonial(request):
    """
    Formulir pengiriman ulasan / testimoni pengunjung yang langsung tersimpan
    ke database (ContactMessage) dan tercatat di Log Aktivitas.
    """
    if request.method == 'POST':
        name = request.POST.get('name', '').strip()
        phone = request.POST.get('phone', '').strip()
        email = request.POST.get('email', '').strip()
        rating = request.POST.get('rating', '5')
        message = request.POST.get('message', '').strip()

        if name and message:
            ContactMessage.objects.create(
                sender_name=name,
                sender_phone=phone,
                sender_email=email,
                subject=f"Ulasan Pelanggan (Bintang {rating} ⭐)",
                message=message,
                is_read=True
            )
            ActivityLog.objects.create(
                user=request.user if request.user.is_authenticated else None,
                action=f"Ulasan baru diterima dari: {name} (Bintang {rating})",
                category="Kotak Masuk & Ulasan"
            )
            messages.success(request, "Terima kasih! Ulasan Anda telah berhasil disimpan dan sangat berarti bagi Yellow Mango's.")
        else:
            messages.error(request, "Nama dan pesan ulasan wajib diisi!")

    return redirect('/#testimoni')


# ==========================================
# 4. AUTENTIKASI: USER & ADMIN
# ==========================================

def custom_login_view(request):
    """
    Halaman Login & Pendaftaran Akun Pelanggan (User Portal):
    - Mendukung Log In dengan Username atau Email
    - Mendukung Pendaftaran akun baru (Sign Up)
    - Mengembalikan user ke halaman tujuan sebelumnya (misal checkout)
    """
    error_message = None
    default_mode = request.GET.get('mode', 'login')
    next_url = request.GET.get('next') or request.POST.get('next') or 'index'

    if request.method == 'POST':
        action = request.POST.get('action')
        identifier = request.POST.get('username', '').strip()
        password = request.POST.get('password', '')

        if action == 'signup':
            first_name = request.POST.get('first_name', '').strip()
            last_name = request.POST.get('last_name', '').strip()
            phone = request.POST.get('phone', '').strip()
            confirm_password = request.POST.get('confirm_password', '')

            # Validasi input sign up
            if confirm_password and password != confirm_password:
                error_message = "Konfirmasi password tidak cocok!"
                default_mode = 'signup'
            elif User.objects.filter(username=identifier).exists() or User.objects.filter(email=identifier).exists():
                error_message = "Email atau Username sudah terdaftar, silakan gunakan yang lain."
                default_mode = 'signup'
            else:
                user = User.objects.create_user(
                    username=identifier,
                    email=identifier if '@' in identifier else '',
                    password=password,
                    first_name=first_name,
                    last_name=last_name
                )
                login(request, user)
                ActivityLog.objects.create(
                    user=user,
                    action=f"Pendaftaran akun pelanggan baru: {user.username}",
                    category="Pengguna & Staf"
                )
                messages.success(request, f"Selamat datang di Yellow Mango's, {user.first_name or user.username}!")
                return redirect(next_url)
        else:
            # Login: cek username langsung atau via email
            target_username = identifier
            if '@' in identifier:
                found_user = User.objects.filter(email=identifier).first()
                if found_user:
                    target_username = found_user.username

            user = authenticate(request, username=target_username, password=password)
            if user is not None:
                login(request, user)
                messages.success(request, f"Halo, {user.first_name or user.username}! Anda berhasil masuk.")
                if user.is_staff or user.is_superuser:
                    return redirect('admin_dashboard')
                return redirect(next_url)
            else:
                error_message = "Email/Username atau password yang Anda masukkan salah!"
                default_mode = 'login'

    return render(request, 'store/login.html', {
        'error': error_message,
        'mode': default_mode,
        'next': next_url,
    })


def custom_admin_login(request):
    """
    Halaman Login Khusus Administrator & Staff Yellow Mango's:
    - Hanya memperbolehkan akun dengan hak akses is_staff / is_superuser
    - Mengarahkan langsung ke dashboard admin
    """
    error = None
    if request.method == 'POST':
        user_name = request.POST.get('username', '').strip()
        pass_word = request.POST.get('password', '')
        user = authenticate(request, username=user_name, password=pass_word)

        if user is not None:
            if user.is_staff or user.is_superuser:
                login(request, user)
                ActivityLog.objects.create(
                    user=user,
                    action=f"Admin {user.username} login ke Dashboard",
                    category="Sistem"
                )
                return redirect('admin_dashboard')
            else:
                error = "Akun Anda terdaftar sebagai pelanggan biasa, bukan administrator."
        else:
            error = "Username atau password admin salah!"

    return render(request, 'store/custom_login.html', {'error': error})


def custom_logout_view(request):
    """
    Fungsi Logout untuk Pelanggan & Staf:
    - Menghapus sesi login
    - Mengembalikan ke halaman utama dengan pesan informasi
    """
    user_name = request.user.username if request.user.is_authenticated else "Pengguna"
    logout(request)
    messages.info(request, f"Sampai jumpa lagi, {user_name}. Anda telah berhasil keluar.")
    return redirect('index')


# ==========================================
# 5. HALAMAN ARTIKEL & BLOG RESMI
# ==========================================

def articles_list_view(request):
    """
    Halaman Daftar Artikel & Blog Publik:
    - Menampilkan artikel yang diterbitkan (is_published=True)
    - Pencarian artikel berdasarkan kata kunci (judul, isi, tags)
    - Filter artikel berdasarkan kategori
    """
    q = request.GET.get('q', '').strip()
    category = request.GET.get('category', '').strip()

    articles = Article.objects.filter(is_published=True).order_by('-created_at')

    if q:
        articles = articles.filter(
            Q(title__icontains=q) | Q(content__icontains=q) | Q(tags__icontains=q)
        )

    if category and category != 'ALL':
        articles = articles.filter(category=category)

    # Ambil daftar kategori artikel yang unik dari database
    category_list = Article.objects.filter(is_published=True).values_list('category', flat=True).distinct()

    context = {
        'articles': articles,
        'category_list': category_list,
        'selected_category': category,
        'search_query': q,
        'total_articles': articles.count(),
    }
    return render(request, 'store/articles_list.html', context)


def article_detail_view(request, slug):
    """
    Halaman Detail Artikel / Blog:
    - Membaca artikel lengkap dari database
    - Menampilkan thumbnail, tanggal terbit, penulis, dan isi
    - Menampilkan rekomendasi artikel terkait
    """
    article = get_object_or_404(Article, slug=slug, is_published=True)
    related_articles = Article.objects.filter(is_published=True).exclude(id=article.id)[:3]

    context = {
        'article': article,
        'related_articles': related_articles,
    }
    return render(request, 'store/article_detail.html', context)


# ==========================================
# 6. DASHBOARD & PROFIL PENGGUNA (USER PROFILE & WEB SETTINGS)
# ==========================================

@login_required(login_url='/login/')
def user_profile_view(request):
    """
    Halaman Dashboard & Profil Pelanggan:
    - Informasi identitas pengguna & ringkasan aktivitas belanja
    - Riwayat seluruh pesanan (Order) yang dilakukan oleh pengguna
    - Pengaturan profil (Nama depan, nama belakang, email, WhatsApp, alamat pengiriman default)
    - Pengaturan keamanan (ganti kata sandi)
    """
    profile, _ = UserProfile.objects.get_or_create(user=request.user)
    user_orders = Order.objects.filter(customer=request.user).order_by('-created_at')

    total_orders = user_orders.count()
    total_spent = sum(order.total_amount for order in user_orders)
    active_orders = user_orders.filter(status__in=['PENDING', 'PROCESSING', 'READY']).count()

    active_tab = request.GET.get('tab', 'overview')

    if request.method == 'POST':
        action = request.POST.get('action')

        if action == 'update_profile':
            first_name = request.POST.get('first_name', '').strip()
            last_name = request.POST.get('last_name', '').strip()
            email = request.POST.get('email', '').strip()
            phone = request.POST.get('phone', '').strip()
            default_address = request.POST.get('default_address', '').strip()

            # Perbarui nama & email
            request.user.first_name = first_name
            request.user.last_name = last_name
            if email:
                request.user.email = email
            request.user.save()

            # Perbarui nomor telepon dan alamat default pada profil
            profile.phone = phone
            profile.default_address = default_address
            profile.save()

            ActivityLog.objects.create(
                user=request.user,
                action=f"Pengguna {request.user.username} memperbarui data profil & alamat",
                category="Profil Pengguna"
            )
            messages.success(request, "Profil dan alamat pengiriman Anda berhasil disimpan!")
            return redirect('/profil/?tab=pengaturan')

        elif action == 'change_password':
            current_password = request.POST.get('current_password', '')
            new_password = request.POST.get('new_password', '')
            confirm_password = request.POST.get('confirm_password', '')

            if not request.user.check_password(current_password):
                messages.error(request, "Kata sandi saat ini (lama) tidak sesuai!")
                return redirect('/profil/?tab=keamanan')
            elif new_password != confirm_password:
                messages.error(request, "Konfirmasi password baru tidak cocok!")
                return redirect('/profil/?tab=keamanan')
            elif len(new_password) < 6:
                messages.error(request, "Password baru minimal 6 karakter!")
                return redirect('/profil/?tab=keamanan')
            else:
                request.user.set_password(new_password)
                request.user.save()
                update_session_auth_hash(request, request.user)
                ActivityLog.objects.create(
                    user=request.user,
                    action=f"Pengguna {request.user.username} memperbarui kata sandi akun",
                    category="Keamanan Akun"
                )
                messages.success(request, "Kata sandi akun Anda berhasil diperbarui!")
                return redirect('/profil/?tab=keamanan')

    context = {
        'profile': profile,
        'orders': user_orders,
        'total_orders': total_orders,
        'total_spent': total_spent,
        'active_orders': active_orders,
        'active_tab': active_tab,
    }
    return render(request, 'store/user_profile.html', context)


# ==========================================
# 5. HALAMAN INFORMASI & LEGALITAS
# ==========================================

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

def page_detail(request, slug):
    page = get_object_or_404(Page, slug=slug, is_published=True)
    return render(request, 'store/page_detail.html', {'page': page})


# ==========================================
# 6. PANEL ADMIN KUSTOM (DASHBOARD & MANAGEMENT)
# ==========================================

@login_required(login_url='/custom-admin-login/')
def admin_dashboard(request):
    context = {
        'total_products': Product.objects.count(),
        'total_orders': Order.objects.count(),
        'total_users': User.objects.count(),
        'total_articles': Article.objects.count(),
        'recent_activities': ActivityLog.objects.all().order_by('-created_at')[:6],
        'recent_orders': Order.objects.all().order_by('-created_at')[:5],
    }
    return render(request, 'store/admin_panel/dashboard.html', context)


@login_required(login_url='/custom-admin-login/')
def admin_products(request):
    if request.method == 'POST':
        action = request.POST.get('action')

        # TAMBAH MENU BARU
        if action == 'add':
            name = request.POST.get('name')
            category = request.POST.get('category', 'Smoothies & Jus')
            price = request.POST.get('price')
            description = request.POST.get('description')
            badge_primary = request.POST.get('badge_primary', 'NONE')
            badge_secondary = request.POST.get('badge_secondary', 'NONE')
            image = request.FILES.get('image')
            image_url = request.POST.get('image_url')
            is_available = request.POST.get('is_available') == 'on'

            Product.objects.create(
                name=name,
                category=category,
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
            product.category = request.POST.get('category', product.category)
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
        'category_choices': Product.CATEGORY_CHOICES,
    }
    return render(request, 'store/admin_panel/products.html', context)


@login_required(login_url='/custom-admin-login/')
def admin_orders(request):
    if request.method == 'POST':
        action = request.POST.get('action')

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


@login_required(login_url='/custom-admin-login/')
def admin_articles(request):
    if request.method == 'POST':
        action = request.POST.get('action')

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

        if action == 'add':
            title = request.POST.get('title')
            announcement_type = request.POST.get('announcement_type', 'PROMO')
            description = request.POST.get('description')
            event_date = request.POST.get('event_date') or None
            banner = request.FILES.get('banner') or request.FILES.get('image')
            is_active = request.POST.get('is_active') == 'on'

            Announcement.objects.create(
                title=title,
                announcement_type=announcement_type,
                description=description,
                event_date=event_date,
                banner=banner,
                is_active=is_active
            )
            ActivityLog.objects.create(
                user=request.user,
                action=f"Membuat pengumuman/event baru: {title}",
                category="Event & Promo"
            )
            messages.success(request, f"Pengumuman '{title}' berhasil ditambahkan!")
            return redirect('admin_announcements')

        elif action == 'edit':
            ann_id = request.POST.get('announcement_id')
            ann = get_object_or_404(Announcement, id=ann_id)
            ann.title = request.POST.get('title')
            ann.announcement_type = request.POST.get('announcement_type', 'PROMO')
            ann.description = request.POST.get('description')
            
            event_date = request.POST.get('event_date')
            ann.event_date = event_date if event_date else None
            ann.is_active = request.POST.get('is_active') == 'on'

            if request.FILES.get('banner'):
                ann.banner = request.FILES.get('banner')
            elif request.FILES.get('image'):
                ann.banner = request.FILES.get('image')

            ann.save()
            ActivityLog.objects.create(
                user=request.user,
                action=f"Memperbarui pengumuman/event: {ann.title}",
                category="Event & Promo"
            )
            messages.success(request, f"Pengumuman '{ann.title}' berhasil diperbarui!")
            return redirect('admin_announcements')

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

        if action == 'toggle_read':
            msg_id = request.POST.get('message_id')
            msg = get_object_or_404(ContactMessage, id=msg_id)
            msg.is_read = not msg.is_read
            msg.save()
            messages.success(request, f"Status pesan dari {msg.sender_name} diperbarui!")
            return redirect('admin_messages')

        elif action == 'delete':
            msg_id = request.POST.get('message_id')
            msg = get_object_or_404(ContactMessage, id=msg_id)
            pengirim = msg.sender_name
            msg.delete()

            ActivityLog.objects.create(
                user=request.user,
                action=f"Menghapus pesan dari: {pengirim}",
                category="Kotak Masuk & Ulasan"
            )
            messages.success(request, f"Pesan dari '{pengirim}' telah dihapus.")
            return redirect('admin_messages')

    messages_list = ContactMessage.objects.all().order_by('-created_at')
    return render(request, 'store/admin_panel/messages.html', {'messages_list': messages_list})


@login_required(login_url='/custom-admin-login/')
def admin_settings(request):
    try:
        setting = SiteSetting.objects.first()
        if not setting:
            setting = SiteSetting.objects.create()
    except Exception:
        setting = None

    if request.method == 'POST':
        if setting:
            if 'site_name' in request.POST:
                setting.site_name = request.POST.get('site_name')
            if 'tagline' in request.POST:
                setting.tagline = request.POST.get('tagline')
            if 'phone_primary' in request.POST:
                setting.phone_primary = request.POST.get('phone_primary')
            if 'outlet_address' in request.POST:
                setting.outlet_address = request.POST.get('outlet_address')
            if 'whatsapp_url' in request.POST:
                setting.whatsapp_url = request.POST.get('whatsapp_url')
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


@login_required(login_url='/custom-admin-login/')
def admin_logs(request):
    if request.GET.get('action') == 'export_csv':
        response = HttpResponse(content_type='text/csv')
        response['Content-Disposition'] = 'attachment; filename="activity_logs_yellow_mangos.csv"'
        
        writer = csv.writer(response)
        writer.writerow(['ID', 'Pengguna', 'Aksi', 'Kategori', 'Waktu'])
        
        logs = ActivityLog.objects.all().order_by('-created_at')
        for log in logs:
            username = log.user.username if log.user else "System"
            writer.writerow([log.id, username, log.action, log.category, log.created_at])
            
        return response

    logs_list = ActivityLog.objects.all().order_by('-created_at')
    return render(request, 'store/admin_panel/logs.html', {'logs_list': logs_list})
