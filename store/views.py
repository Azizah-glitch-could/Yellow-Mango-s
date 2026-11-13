from django.shortcuts import render, redirect
from django.contrib.auth import authenticate, login

def index(request):
    return render(request, 'index.html')

def menu_view(request):
    return render(request, 'store/menu.html') # atau arahkan ke template menu Anda

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