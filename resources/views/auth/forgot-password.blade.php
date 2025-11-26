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
    </style>
</head>
<body class="bg-pattern min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo & Title -->
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-gradient-to-br from-orange-400 to-red-500 rounded-3xl flex items-center justify-center shadow-2xl shadow-orange-500/30 mx-auto mb-4">
                <i class="fas fa-key text-white text-3xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-white">Lupa Password?</h1>
            <p class="text-slate-400 mt-2">Jangan khawatir, kami akan membantu Anda</p>
        </div>

        <!-- Forgot Password Card -->
        <div class="bg-slate-800/50 backdrop-blur-xl rounded-3xl shadow-2xl border border-slate-700/50 p-8">
            @if(session('error'))
                <div class="bg-red-500/20 border border-red-500/50 text-red-300 p-4 mb-6 rounded-xl">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-3"></i>
                        <p>{{ session('error') }}</p>
                    </div>
                </div>
            @endif

            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-blue-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fab fa-whatsapp text-green-400 text-3xl"></i>
                </div>
                <p class="text-slate-400 text-sm">
                    Masukkan email atau nomor WhatsApp Anda.<br>
                    Kode verifikasi akan dikirim via WhatsApp.
                </p>
            </div>

            <form method="POST" action="{{ route('password.send') }}">
                @csrf
                
                <div class="mb-6">
                    <label class="block text-slate-400 text-sm font-medium mb-2">
                        <i class="fas fa-at mr-2"></i>Email atau Nomor HP
                    </label>
                    <input 
                        type="text" 
                        name="email_or_phone" 
                        value="{{ old('email_or_phone') }}"
                        class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-4 text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 input-glow"
                        placeholder="contoh@email.com atau 08xxxxxxxxxx"
                        required
                    >
                </div>

                <button 
                    type="submit" 
                    class="w-full bg-gradient-to-r from-blue-500 to-blue-600 text-white py-4 rounded-xl font-bold hover:from-blue-600 hover:to-blue-700 transition-all"
                >
                    <i class="fas fa-paper-plane mr-2"></i>
                    Kirim Kode Verifikasi
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="{{ route('login') }}" class="text-slate-400 hover:text-white text-sm">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali ke halaman login
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8">
            <p class="text-slate-500 text-sm">&copy; {{ date('Y') }} UPTD Puskesmas Karang Rejo</p>
        </div>
    </div>
</body>
</html>
