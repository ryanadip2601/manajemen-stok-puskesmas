<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Manajemen Stok Barang') - UPTD Puskesmas Karang Rejo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-gradient-to-b from-blue-800 to-blue-900 text-white fixed h-full shadow-xl">
            <div class="p-6 border-b border-blue-700">
                <h1 class="text-xl font-bold">Puskesmas</h1>
                <p class="text-sm text-blue-200">Karang Rejo</p>
            </div>
            
            <nav class="mt-6">
                <a href="{{ route('dashboard') }}" class="flex items-center px-6 py-3 hover:bg-blue-700 transition {{ request()->routeIs('dashboard') ? 'bg-blue-700 border-r-4 border-white' : '' }}">
                    <i class="fas fa-home mr-3"></i>
                    <span>Dashboard</span>
                </a>
                
                <a href="{{ route('items.index') }}" class="flex items-center px-6 py-3 hover:bg-blue-700 transition {{ request()->routeIs('items.*') ? 'bg-blue-700 border-r-4 border-white' : '' }}">
                    <i class="fas fa-box mr-3"></i>
                    <span>Data Barang</span>
                </a>
                
                <a href="{{ route('categories.index') }}" class="flex items-center px-6 py-3 hover:bg-blue-700 transition {{ request()->routeIs('categories.*') ? 'bg-blue-700 border-r-4 border-white' : '' }}">
                    <i class="fas fa-folder mr-3"></i>
                    <span>Kategori</span>
                </a>
                
                <a href="{{ route('stock-in.index') }}" class="flex items-center px-6 py-3 hover:bg-blue-700 transition {{ request()->routeIs('stock-in.*') ? 'bg-blue-700 border-r-4 border-white' : '' }}">
                    <i class="fas fa-arrow-down mr-3"></i>
                    <span>Barang Masuk</span>
                </a>
                
                <a href="{{ route('stock-out.index') }}" class="flex items-center px-6 py-3 hover:bg-blue-700 transition {{ request()->routeIs('stock-out.*') ? 'bg-blue-700 border-r-4 border-white' : '' }}">
                    <i class="fas fa-arrow-up mr-3"></i>
                    <span>Barang Keluar</span>
                </a>
            </nav>

            <div class="absolute bottom-0 w-64 p-6 border-t border-blue-700">
                <div class="flex items-center mb-3">
                    <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="ml-3">
                        <p class="font-semibold">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-blue-200">{{ ucfirst(auth()->user()->role) }}</p>
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white py-2 rounded-lg transition flex items-center justify-center">
                        <i class="fas fa-sign-out-alt mr-2"></i>
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 ml-64">
            <!-- Header -->
            <header class="bg-white shadow-sm">
                <div class="px-8 py-4">
                    <h2 class="text-2xl font-bold text-gray-800">@yield('header', 'Dashboard')</h2>
                    <p class="text-sm text-gray-600">@yield('breadcrumb')</p>
                </div>
            </header>

            <!-- Content -->
            <div class="p-8">
                @if(session('success'))
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r-lg shadow">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle mr-3"></i>
                            <p>{{ session('success') }}</p>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r-lg shadow">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle mr-3"></i>
                            <p>{{ session('error') }}</p>
                        </div>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    @stack('scripts')
</body>
</html>
