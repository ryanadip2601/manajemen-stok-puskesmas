<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - UPTD Puskesmas Karang Rejo</title>
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
        }
        .input-glow:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }
        .btn-glow:hover {
            box-shadow: 0 0 30px rgba(34, 197, 94, 0.4);
        }
        /* Mobile Responsive & Scrollable */
        html, body {
            height: 100%;
            overflow-x: hidden;
        }
        body {
            min-height: 100vh;
            min-height: -webkit-fill-available;
        }
        html {
            height: -webkit-fill-available;
        }
        @media (max-width: 640px) {
            body {
                padding: 1rem;
                align-items: flex-start;
                padding-top: 1.5rem;
                padding-bottom: 1.5rem;
            }
        }
    </style>
</head>
<body class="bg-pattern min-h-screen flex items-center justify-center p-4 overflow-y-auto">
    <div class="w-full max-w-md">
        <!-- Logo & Title -->
        <div class="text-center mb-6">
            <div class="w-20 h-20 bg-gradient-to-br from-emerald-400 to-teal-600 rounded-3xl flex items-center justify-center shadow-2xl shadow-emerald-500/30 mx-auto mb-4">
                <i class="fas fa-hospital text-white text-3xl"></i>
            </div>
            <h1 class="text-xl font-bold text-white">UPTD Puskesmas</h1>
            <h2 class="text-2xl font-extrabold gradient-text">Karang Rejo</h2>
        </div>

        <!-- Reset Password Card -->
        <div class="bg-slate-800/50 backdrop-blur-xl rounded-3xl shadow-2xl border border-slate-700/50 p-8">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-emerald-500/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-key text-emerald-400 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-white">Buat Password Baru</h3>
                <p class="text-slate-400 text-sm mt-2">Masukkan password baru untuk akun Anda.</p>
            </div>

            @if(session('error'))
                <div class="bg-red-500/20 border border-red-500/50 text-red-300 p-4 mb-6 rounded-xl flex items-center">
                    <i class="fas fa-exclamation-circle mr-3"></i>
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-500/20 border border-red-500/50 text-red-300 p-4 mb-6 rounded-xl">
                    <ul class="list-disc list-inside text-sm">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('password.reset') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">

                <div class="mb-4">
                    <label class="block text-slate-400 text-sm font-medium mb-2">
                        <i class="fas fa-envelope mr-2"></i>Email
                    </label>
                    <input 
                        type="email" 
                        value="{{ $email }}"
                        class="w-full bg-slate-700/30 border border-slate-600 rounded-xl px-4 py-3 text-slate-400 cursor-not-allowed"
                        disabled
                    >
                </div>

                <div class="mb-4">
                    <label class="block text-slate-400 text-sm font-medium mb-2">
                        <i class="fas fa-lock mr-2"></i>Password Baru
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            name="password"
                            id="password"
                            class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 input-glow pr-12"
                            placeholder="Minimal 6 karakter"
                            required
                            autofocus
                        >
                        <button type="button" onclick="togglePassword('password')" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white">
                            <i class="fas fa-eye" id="password-icon"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-slate-400 text-sm font-medium mb-2">
                        <i class="fas fa-lock mr-2"></i>Konfirmasi Password Baru
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            name="password_confirmation"
                            id="password_confirmation"
                            class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 input-glow pr-12"
                            placeholder="Ulangi password baru"
                            required
                        >
                        <button type="button" onclick="togglePassword('password_confirmation')" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white">
                            <i class="fas fa-eye" id="password_confirmation-icon"></i>
                        </button>
                    </div>
                </div>

                <button 
                    type="submit" 
                    class="w-full bg-gradient-to-r from-emerald-500 to-teal-600 text-white py-3 rounded-xl font-bold hover:from-emerald-600 hover:to-teal-700 transition-all btn-glow flex items-center justify-center"
                >
                    <i class="fas fa-save mr-2"></i>
                    Simpan Password Baru
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="{{ route('login') }}" class="text-slate-400 hover:text-white text-sm transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Kembali ke halaman login
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6">
            <p class="text-slate-500 text-sm">&copy; {{ date('Y') }} UPTD Puskesmas Karang Rejo</p>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + '-icon');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
