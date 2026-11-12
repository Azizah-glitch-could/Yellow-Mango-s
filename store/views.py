from django.shortcuts import render

def index(request):
    return render(request, 'index.html')

def menu_view(request):
    return render(request, 'store/menu.html') # atau arahkan ke template menu Anda