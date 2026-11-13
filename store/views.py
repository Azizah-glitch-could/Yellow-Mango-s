from django.shortcuts import render, redirect
from django.contrib.auth import authenticate, login

def index(request):
    return render(request, 'index.html')

def menu_view(request):
    return render(request, 'store/menu.html') # atau arahkan ke template menu Anda

def custom_login_view(request):
    if request.method == 'POST':
        username = request.POST.get('username')
        password = request.POST.get('password')
        role = request.POST.get('role')  # Menerima pilihan 'admin' atau 'user'

        user = authenticate(request, username=username, password=password)
        
        if user is not None:
            # Validasi peran sesuai pilihan
            if role == 'admin' and (user.is_staff or user.is_superuser):
                login(request, user)
                return redirect('/admin/')  # Atau diarahkan ke dashboard admin custom nanti
            elif role == 'user' and not user.is_staff:
                login(request, user)
                return redirect('index')    # Diarahkan ke beranda user
            else:
                error_message = "Peran (Role) yang Anda pilih tidak sesuai dengan akun ini!"
                return render(request, 'store/login.html', {'error': error_message})
        else:
            error_message = "Username atau Password salah!"
            return render(request, 'store/login.html', {'error': error_message})

    return render(request, 'store/login.html')