<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - UPTD Puskesmas Karang Rejo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .bg-pattern {
            background-color: #0f172a;
            background-image: 
                radial-gradient(at 47% 33%, hsl(220, 50%, 15%) 0, transparent 59%),
                radial-gradient(at 82% 65%, hsl(240, 50%, 12%) 0, transparent 55%);
        }
        .gradient-text {
            background: linear-gradient(135deg, #60a5fa, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .input-glow:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }
        .btn-glow:hover {
            box-shadow: 0 0 30px rgba(34, 197, 94, 0.4);
        }
        .float {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
    </style>
</head>
<body class="bg-pattern min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo & Title -->
        <div class="text-center mb-8">
            <div class="float inline-block">
                <div class="w-24 h-24 bg-gradient-to-br from-emerald-400 to-teal-600 rounded-3xl flex items-center justify-center shadow-2xl shadow-emerald-500/30 mx-auto mb-4 transform rotate-3 hover:rotate-0 transition-all duration-500">
                    <i class="fas fa-hospital text-white text-4xl"></i>
                </div>
            </div>
            <h1 class="text-2xl font-bold text-white mb-1">UPTD Puskesmas</h1>
            <h2 class="text-3xl font-extrabold gradient-text">Karang Rejo</h2>
            <p class="text-slate-400 mt-2">Kota Balikpapan</p>
        </div>

        <!-- Login Card -->
        <div class="bg-slate-800/50 backdrop-blur-xl rounded-3xl shadow-2xl border border-slate-700/50 p-8">
            <div class="text-center mb-8">
                <h3 class="text-xl font-bold text-white">Selamat Datang</h3>
                <p class="text-slate-400 text-sm mt-1">Silakan masuk ke sistem manajemen stok</p>
            </div>

            @if($errors->any())
                <div class="bg-red-500/20 border border-red-500/50 text-red-300 p-4 mb-6 rounded-xl">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-3"></i>
                        <p>{{ $errors->first() }}</p>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                
                <div class="mb-6">
                    <label class="block text-slate-400 text-sm font-medium mb-2">
                        <i class="fas fa-user mr-2"></i>Username / Email
                    </label>
                    <div class="relative">
                        <input 
                            type="text" 
                            name="email" 
                            value="{{ old('email') }}"
                            class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-4 text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 input-glow transition-all"
                            placeholder="Masukkan username atau email"
                            required
                        >
                        <div class="absolute right-4 top-1/2 transform -translate-y-1/2 text-slate-500">
                            <i class="fas fa-user"></i>
                        </div>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-slate-400 text-sm font-medium mb-2">
                        <i class="fas fa-lock mr-2"></i>Password
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            name="password" 
                            id="password"
                            class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-4 text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 input-glow transition-all"
                            placeholder="Masukkan password Anda"
                            required
                        >
                        <button type="button" onclick="togglePassword()" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-slate-500 hover:text-slate-300 transition">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between mb-6">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded bg-slate-700 border-slate-600 text-emerald-500 focus:ring-emerald-500 focus:ring-offset-slate-800">
                        <span class="ml-2 text-sm text-slate-400">Ingat saya</span>
                    </label>
                    <a href="{{ route('password.request') }}" class="text-sm text-blue-400 hover:text-blue-300 transition">
                        Lupa Password?
                    </a>
                </div>

                <button 
                    type="submit" 
                    class="w-full bg-gradient-to-r from-emerald-500 to-teal-600 text-white py-4 rounded-xl font-bold hover:from-emerald-600 hover:to-teal-700 transition-all btn-glow transform hover:scale-[1.02] active:scale-[0.98]"
                >
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Masuk
                </button>
            </form>

            <div class="mt-6 pt-6 border-t border-slate-700/50 text-center">
                <p class="text-slate-400 text-sm">
                    Belum punya akun?
                    <a href="{{ route('register') }}" class="text-emerald-400 hover:text-emerald-300 font-semibold ml-1">
                        Daftar Sekarang
                    </a>
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8">
            <p class="text-slate-500 text-sm">
                &copy; {{ date('Y') }} UPTD Puskesmas Karang Rejo Balikpapan
            </p>
            <p class="text-slate-600 text-xs mt-1">
                Sistem Manajemen Stok Barang v1.0
            </p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
