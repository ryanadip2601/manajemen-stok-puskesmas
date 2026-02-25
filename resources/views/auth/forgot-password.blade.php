<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - UPTD Puskesmas Karang Rejo</title>
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
            box-shadow: 0 0 30px rgba(59, 130, 246, 0.4);
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

        <!-- Forgot Password Card -->
        <div class="bg-slate-800/50 backdrop-blur-xl rounded-3xl shadow-2xl border border-slate-700/50 p-8">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-blue-500/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-envelope text-blue-400 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-white">Lupa Password?</h3>
                <p class="text-slate-400 text-sm mt-2">Masukkan email Anda dan kami akan mengirimkan link untuk reset password.</p>
            </div>

            @if(session('success'))
                <div class="bg-green-500/20 border border-green-500/50 text-green-300 p-4 mb-6 rounded-xl flex items-start">
                    <i class="fas fa-check-circle mr-3 mt-0.5"></i>
                    <div>
                        <p class="font-medium">Email Terkirim!</p>
                        <p class="text-sm mt-1">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

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

            <form method="POST" action="{{ route('password.send') }}">
                @csrf
                
                <div class="mb-6">
                    <label class="block text-slate-400 text-sm font-medium mb-2">
                        <i class="fas fa-envelope mr-2"></i>Email Terdaftar
                    </label>
                    <input 
                        type="email" 
                        name="email" 
                        value="{{ old('email') }}"
                        class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 input-glow"
                        placeholder="Masukkan email Anda"
                        required
                        autofocus
                    >
                </div>

                <button 
                    type="submit" 
                    class="w-full bg-gradient-to-r from-blue-500 to-blue-600 text-white py-3 rounded-xl font-bold hover:from-blue-600 hover:to-blue-700 transition-all btn-glow flex items-center justify-center"
                >
                    <i class="fas fa-paper-plane mr-2"></i>
                    Kirim Link Reset Password
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="{{ route('login') }}" class="text-slate-400 hover:text-white text-sm transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Kembali ke halaman login
                </a>
            </div>
        </div>

        <!-- Info Box -->
        <div class="mt-6 bg-slate-800/30 border border-slate-700/50 rounded-2xl p-4">
            <div class="flex items-start">
                <i class="fas fa-info-circle text-blue-400 mt-0.5 mr-3"></i>
                <div class="text-sm text-slate-400">
                    <p class="font-medium text-slate-300 mb-1">Cara Reset Password:</p>
                    <ol class="list-decimal list-inside space-y-1">
                        <li>Masukkan email yang terdaftar</li>
                        <li>Cek inbox atau folder spam di email Anda</li>
                        <li>Klik link reset password di email</li>
                        <li>Buat password baru</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6">
            <p class="text-slate-500 text-sm">&copy; {{ date('Y') }} UPTD Puskesmas Karang Rejo</p>
        </div>
    </div>
</body>
</html>
