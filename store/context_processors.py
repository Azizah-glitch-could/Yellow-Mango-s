from .models import SiteSetting

def cart_and_settings(request):
    """
    Context processor global agar jumlah keranjang belanja (cart_count),
    total harga keranjang (cart_total), isi keranjang (cart),
    serta pengaturan toko (site_setting) dapat diakses di seluruh template HTML.
    """
    cart = request.session.get('cart', {})
    cart_count = 0
    cart_total = 0

    if isinstance(cart, dict):
        for item in cart.values():
            if isinstance(item, dict):
                qty = int(item.get('quantity', 1))
                cart_count += qty
                cart_total += int(float(item.get('subtotal', item.get('price', 0) * qty)))

    try:
        site_setting = SiteSetting.objects.first()
    except Exception:
        site_setting = None

    return {
        'cart': cart,
        'cart_count': cart_count,
        'cart_total': cart_total,
        'site_setting': site_setting,
    }
