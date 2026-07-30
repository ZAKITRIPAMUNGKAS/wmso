<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'WMS System') - Nama Perusahaan</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'] },
                    colors: {
                        brand: '#025cca',
                        indigo: {
                            50: '#eef6ff',
                            100: '#d9ebff',
                            200: '#bce0ff',
                            300: '#8eccff',
                            400: '#59b2ff',
                            500: '#025cca',
                            600: '#02469c',
                            700: '#0047a3',
                            800: '#003d8a',
                            900: '#003273',
                            950: '#001f4d'
                        }
                    }
                }
            }
        }
    </script>
    @livewireStyles
</head>
<body class="bg-slate-50 flex flex-col md:flex-row h-screen overflow-hidden text-slate-800 antialiased font-sans">

    <!-- Sidebar (Desktop only) -->
    <aside class="hidden md:flex w-64 bg-slate-900 border-r border-slate-800 flex-col transition-all shrink-0">
        <div class="h-16 flex items-center px-6 border-b border-slate-800">
            <i class="ph-fill ph-package text-indigo-500 text-2xl mr-3"></i>
            <span class="text-white font-bold text-lg tracking-wide">Nama Perusahaan</span>
        </div>

        <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
            <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Menu Utama</p>
            
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 {{ request()->routeIs('dashboard') ? 'bg-indigo-500/10 text-indigo-400' : 'text-slate-400 hover:text-white hover:bg-slate-800' }} rounded-lg font-medium transition-colors">
                <i class="ph {{ request()->routeIs('dashboard') ? 'ph-squares-four-fill' : 'ph-squares-four' }} text-xl"></i> Dashboard
            </a>
            <a href="{{ route('master-data.index') }}" class="flex items-center gap-3 px-3 py-2.5 {{ request()->routeIs('master-data.*') ? 'bg-indigo-500/10 text-indigo-400' : 'text-slate-400 hover:text-white hover:bg-slate-800' }} rounded-lg font-medium transition-colors">
                <i class="ph {{ request()->routeIs('master-data.*') ? 'ph-database-fill' : 'ph-database' }} text-xl"></i> Master Data
            </a>
            <a href="{{ route('barang-masuk.index') }}" class="flex items-center gap-3 px-3 py-2.5 {{ request()->routeIs('barang-masuk.*') ? 'bg-indigo-500/10 text-indigo-400' : 'text-slate-400 hover:text-white hover:bg-slate-800' }} rounded-lg font-medium transition-colors">
                <i class="ph {{ request()->routeIs('barang-masuk.*') ? 'ph-arrow-circle-down-fill' : 'ph-arrow-circle-down' }} text-xl"></i> Barang Masuk
            </a>
            <a href="{{ route('barang-keluar.index') }}" class="flex items-center gap-3 px-3 py-2.5 {{ request()->routeIs('barang-keluar.*') ? 'bg-indigo-500/10 text-indigo-400' : 'text-slate-400 hover:text-white hover:bg-slate-800' }} rounded-lg font-medium transition-colors">
                <i class="ph {{ request()->routeIs('barang-keluar.*') ? 'ph-arrow-circle-up-fill' : 'ph-arrow-circle-up' }} text-xl"></i> Barang Keluar
            </a>
            <a href="{{ route('inventory.index') }}" class="flex items-center gap-3 px-3 py-2.5 {{ request()->routeIs('inventory.index') ? 'bg-indigo-500/10 text-indigo-400' : 'text-slate-400 hover:text-white hover:bg-slate-800' }} rounded-lg font-medium transition-colors">
                <i class="ph {{ request()->routeIs('inventory.index') ? 'ph-package-fill' : 'ph-package' }} text-xl"></i> Inventori
            </a>
            <a href="{{ route('reports.index') }}" class="flex items-center gap-3 px-3 py-2.5 {{ request()->routeIs('reports.index') ? 'bg-indigo-500/10 text-indigo-400' : 'text-slate-400 hover:text-white hover:bg-slate-800' }} rounded-lg font-medium transition-colors">
                <i class="ph {{ request()->routeIs('reports.index') ? 'ph-chart-line-up-fill' : 'ph-chart-line-up' }} text-xl"></i> Laporan
            </a>
        </nav>

        <div class="p-4 border-t border-slate-800">
            <div class="flex items-center justify-between gap-3 px-3 py-2 rounded-lg bg-slate-800/50">
                <div class="flex items-center gap-3">
                    <img src="https://ui-avatars.com/api/?name=Tepe+Zhavarez&background=025cca&color=fff" class="w-9 h-9 rounded-full">
                    <div class="truncate">
                        <p class="text-sm font-semibold text-white truncate">Tepe Zhavarez</p>
                        <p class="text-xs text-slate-400">Super Admin</p>
                    </div>
                </div>
                <form method="POST" action="/logout">
                    @csrf
                    <button type="submit" class="p-2 text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition-colors shadow-sm" title="Logout">
                        <i class="ph ph-sign-out text-xl"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Bottom Bar (Mobile only) -->
    <nav class="md:hidden fixed bottom-0 left-0 w-full bg-slate-900 border-t border-slate-800 flex justify-around items-center h-16 z-50 px-2">
        <a href="{{ route('dashboard') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('dashboard') ? 'text-indigo-400' : 'text-slate-500' }}">
            <i class="ph {{ request()->routeIs('dashboard') ? 'ph-squares-four-fill' : 'ph-squares-four' }} text-2xl"></i>
            <span class="text-[10px] font-medium uppercase tracking-tight">Home</span>
        </a>
        <a href="{{ route('master-data.index') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('master-data.*') ? 'text-indigo-400' : 'text-slate-500' }}">
            <i class="ph {{ request()->routeIs('master-data.*') ? 'ph-database-fill' : 'ph-database' }} text-2xl"></i>
            <span class="text-[10px] font-medium uppercase tracking-tight">Master</span>
        </a>
        <a href="{{ route('barang-masuk.index') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('barang-masuk.*') ? 'text-indigo-400' : 'text-slate-500' }}">
            <i class="ph {{ request()->routeIs('barang-masuk.*') ? 'ph-arrow-circle-down-fill' : 'ph-arrow-circle-down' }} text-2xl"></i>
            <span class="text-[10px] font-medium uppercase tracking-tight">Masuk</span>
        </a>
        <a href="{{ route('barang-keluar.index') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('barang-keluar.*') ? 'text-indigo-400' : 'text-slate-500' }}">
            <i class="ph {{ request()->routeIs('barang-keluar.*') ? 'ph-arrow-circle-up-fill' : 'ph-arrow-circle-up' }} text-2xl"></i>
            <span class="text-[10px] font-medium uppercase tracking-tight">Keluar</span>
        </a>
        <a href="{{ route('reports.index') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('reports.index') ? 'text-indigo-400' : 'text-slate-500' }}">
            <i class="ph {{ request()->routeIs('reports.index') ? 'ph-chart-line-up-fill' : 'ph-chart-line-up' }} text-2xl"></i>
            <span class="text-[10px] font-medium uppercase tracking-tight">Laporan</span>
        </a>
    </nav>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <header class="h-16 bg-white border-b border-slate-200 flex justify-between items-center px-4 md:px-8 shadow-sm z-10 shrink-0">
            <div class="flex items-center gap-3">
                <i class="ph-fill ph-package text-indigo-600 text-2xl md:hidden"></i>
                <h1 class="text-lg md:text-xl font-bold text-slate-800 truncate">@yield('header')</h1>
            </div>
            <div class="flex items-center gap-2 md:gap-4">
                <button class="relative p-2 text-slate-400 hover:bg-slate-100 rounded-full transition">
                    <i class="ph ph-bell text-xl"></i>
                    <span class="absolute top-1.5 right-1.5 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-white"></span>
                </button>
                <img src="https://ui-avatars.com/api/?name=Tepe+Zhavarez&background=025cca&color=fff" class="w-8 h-8 rounded-full md:hidden border border-slate-200">
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 md:p-8 pb-24 md:pb-8">
            <div class="max-w-7xl mx-auto">
                @yield('content')
            </div>
        </div>
    </main>

    @livewireScripts
</body>
</html>
