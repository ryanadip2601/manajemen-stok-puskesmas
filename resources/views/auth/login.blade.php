<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SIMBAR | Sistem Manajemen Barang</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .bg-pattern {
            background-color: #0f172a;
            background-image: 
                radial-gradient(at 47% 33%, hsl(220, 50%, 15%) 0, transparent 59%),
                radial-gradient(at 82% 65%, hsl(240, 50%, 12%) 0, transparent 55%);
            overflow: hidden;
        }
        .gradient-text {
            background: linear-gradient(135deg, #34d399, #06b6d4, #3b82f6);
            background-size: 200% 200%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradientShift 3s ease infinite;
        }
        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        .input-glow:focus {
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }
        .btn-glow:hover {
            box-shadow: 0 0 15px rgba(34, 197, 94, 0.25);
        }
        .float {
            animation: float 4s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(3deg); }
            50% { transform: translateY(-15px) rotate(-3deg); }
        }
        .slide-up {
            animation: slideUp 0.8s ease-out forwards;
            opacity: 0;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .slide-up-delay-1 { animation-delay: 0.1s; }
        .slide-up-delay-2 { animation-delay: 0.2s; }
        .slide-up-delay-3 { animation-delay: 0.3s; }
        .slide-up-delay-4 { animation-delay: 0.4s; }
        .pulse-ring {
            animation: pulseRing 3s ease-out infinite;
        }
        @keyframes pulseRing {
            0% { transform: scale(0.95); opacity: 0.4; }
            100% { transform: scale(1.2); opacity: 0; }
        }
        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(52, 211, 153, 0.5);
            border-radius: 50%;
            animation: particleFloat 10s ease-in-out infinite;
            opacity: 0.3;
        }
        @keyframes particleFloat {
            0%, 100% { transform: translateY(0) translateX(0); opacity: 0.3; }
            50% { transform: translateY(-15px) translateX(8px); opacity: 0.2; }
        }
        .glow-border {
            position: relative;
        }
        .glow-border::before {
            content: '';
            position: absolute;
            inset: -1px;
            background: linear-gradient(45deg, #34d399, #06b6d4, #3b82f6);
            background-size: 300% 300%;
            border-radius: 1.5rem;
            z-index: -1;
            animation: borderGlow 6s linear infinite;
            opacity: 0.2;
            filter: blur(4px);
        }
        @keyframes borderGlow {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .logo-shine {
            position: relative;
            overflow: hidden;
        }
        .logo-shine::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent 40%, rgba(255,255,255,0.15) 50%, transparent 60%);
            animation: shine 4s ease-in-out infinite;
        }
        @keyframes shine {
            0%, 100% { transform: translateX(-100%) rotate(45deg); }
            50% { transform: translateX(100%) rotate(45deg); }
        }
        .typing-text {
            overflow: hidden;
            white-space: nowrap;
            border-right: 2px solid #34d399;
            animation: typing 2s steps(20) forwards, blink 0.8s step-end infinite;
            width: 0;
        }
        @keyframes typing {
            to { width: 100%; }
        }
        @keyframes blink {
            50% { border-color: transparent; }
        }
        .card-3d {
            transform-style: preserve-3d;
            transition: transform 0.3s ease;
        }
        .card-3d:hover {
            transform: perspective(1000px) rotateX(2deg) rotateY(-2deg);
        }
    </style>
</head>
<body class="bg-pattern min-h-screen flex items-center justify-center p-4 relative">
    <!-- Floating Particles -->
    <div class="particle" style="top: 10%; left: 10%; animation-delay: 0s;"></div>
    <div class="particle" style="top: 20%; left: 80%; animation-delay: 1s;"></div>
    <div class="particle" style="top: 60%; left: 15%; animation-delay: 2s;"></div>
    <div class="particle" style="top: 70%; left: 85%; animation-delay: 3s;"></div>
    <div class="particle" style="top: 40%; left: 5%; animation-delay: 4s;"></div>
    <div class="particle" style="top: 80%; left: 50%; animation-delay: 5s;"></div>
    <div class="particle" style="top: 15%; left: 60%; animation-delay: 6s;"></div>
    <div class="particle" style="top: 90%; left: 20%; animation-delay: 7s;"></div>

    <div class="w-full max-w-md relative z-10">
        <!-- Logo & Title -->
        <div class="text-center mb-8 slide-up">
            <div class="float inline-block relative">
                <!-- Pulse Ring Effect -->
                <div class="absolute inset-0 w-24 h-24 mx-auto bg-emerald-500/15 rounded-3xl pulse-ring"></div>
                <div class="logo-shine w-24 h-24 bg-gradient-to-br from-emerald-400 via-teal-500 to-cyan-600 rounded-3xl flex items-center justify-center shadow-xl shadow-emerald-500/20 mx-auto mb-4 transform rotate-3 hover:rotate-0 hover:scale-105 transition-all duration-500 relative">
                    <i class="fas fa-boxes-stacked text-white text-4xl drop-shadow-lg"></i>
                </div>
            </div>
            <h1 class="text-5xl font-black gradient-text tracking-wider mt-4 slide-up slide-up-delay-1">SIMBAR</h1>
            <p class="text-slate-300 text-lg mt-2 slide-up slide-up-delay-2">Sistem Manajemen Barang</p>
            <p class="text-slate-500 text-sm mt-1 slide-up slide-up-delay-3">UPTD Puskesmas Karang Rejo - Kota Balikpapan</p>
        </div>

        <!-- Login Card -->
        <div class="glow-border card-3d bg-slate-800/50 backdrop-blur-xl rounded-3xl shadow-2xl border border-slate-700/50 p-8 slide-up slide-up-delay-2">
            <div class="text-center mb-8">
                <h3 class="text-xl font-bold text-white">Selamat Datang</h3>
                <p class="text-slate-400 text-sm mt-1">Silakan masuk ke SIMBAR</p>
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
                        <i class="fas fa-user mr-2"></i>Username
                    </label>
                    <div class="relative">
                        <input 
                            type="text" 
                            name="email" 
                            value="{{ old('email') }}"
                            class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-4 text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 input-glow transition-all"
                            placeholder="Masukkan username"
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
                    class="group w-full bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500 text-white py-4 rounded-xl font-bold hover:from-emerald-600 hover:via-teal-600 hover:to-cyan-600 transition-all duration-300 btn-glow transform hover:scale-[1.02] active:scale-[0.98] relative overflow-hidden"
                >
                    <span class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-700"></span>
                    <i class="fas fa-sign-in-alt mr-2 group-hover:rotate-12 transition-transform"></i>
                    Masuk
                </button>
            </form>

            <div class="mt-6 pt-6 border-t border-slate-700/50 text-center">
                <p class="text-slate-400 text-sm">
                    Belum punya akun?
                    <a href="{{ route('register') }}" class="text-emerald-400 hover:text-emerald-300 font-semibold ml-1 hover:underline transition-all">
                        Daftar Sekarang
                    </a>
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8 slide-up slide-up-delay-4">
            <p class="text-slate-500 text-sm">
                &copy; {{ date('Y') }} <span class="text-emerald-400 font-semibold">SIMBAR</span> - UPTD Puskesmas Karang Rejo
            </p>
            <p class="text-slate-600 text-xs mt-1">
                Sistem Manajemen Barang v1.0
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
