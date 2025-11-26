<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Kode - UPTD Puskesmas Karang Rejo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .bg-pattern {
            background-color: #0f172a;
            background-image: 
                radial-gradient(at 47% 33%, hsl(220, 50%, 15%) 0, transparent 59%),
                radial-gradient(at 82% 65%, hsl(240, 50%, 12%) 0, transparent 55%);
        }
        .input-glow:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }
        .code-input {
            letter-spacing: 1rem;
            text-align: center;
            font-size: 1.5rem;
            font-weight: bold;
        }
    </style>
</head>
<body class="bg-pattern min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo & Title -->
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-gradient-to-br from-green-400 to-emerald-500 rounded-3xl flex items-center justify-center shadow-2xl shadow-green-500/30 mx-auto mb-4">
                <i class="fas fa-shield-alt text-white text-3xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-white">Verifikasi Kode</h1>
            <p class="text-slate-400 mt-2">Masukkan kode 6 digit yang dikirim</p>
        </div>

        <!-- Verify Code Card -->
        <div class="bg-slate-800/50 backdrop-blur-xl rounded-3xl shadow-2xl border border-slate-700/50 p-8">
            @if(session('error'))
                <div class="bg-red-500/20 border border-red-500/50 text-red-300 p-4 mb-6 rounded-xl">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-3"></i>
                        <p>{{ session('error') }}</p>
                    </div>
                </div>
            @endif

            <!-- WhatsApp Link -->
            @if(isset($whatsapp_link))
                <div class="bg-green-500/20 border border-green-500/50 rounded-xl p-4 mb-6">
                    <div class="text-center">
                        <p class="text-green-300 text-sm mb-3">
                            <i class="fab fa-whatsapp mr-2"></i>
                            Klik tombol di bawah untuk mengirim kode ke WhatsApp Anda
                        </p>
                        <a 
                            href="{{ $whatsapp_link }}" 
                            target="_blank"
                            class="inline-flex items-center bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-xl font-bold transition-all"
                        >
                            <i class="fab fa-whatsapp text-xl mr-2"></i>
                            Kirim ke WhatsApp
                        </a>
                        @if(isset($code) && app()->environment('local'))
                            <p class="text-yellow-400 text-xs mt-3">
                                <i class="fas fa-info-circle mr-1"></i>
                                [Dev Mode] Kode: <span class="font-mono font-bold">{{ $code }}</span>
                            </p>
                        @endif
                    </div>
                </div>
            @endif

            <div class="text-center mb-6">
                @if(isset($user))
                    <p class="text-slate-400 text-sm">
                        Kode dikirim ke:<br>
                        <span class="text-white font-semibold">{{ $user->phone }}</span>
                    </p>
                @endif
            </div>

            <form method="POST" action="{{ route('password.verify.post') }}">
                @csrf
                
                <div class="mb-6">
                    <label class="block text-slate-400 text-sm font-medium mb-2 text-center">
                        Masukkan Kode Verifikasi
                    </label>
                    <input 
                        type="text" 
                        name="code" 
                        maxlength="6"
                        class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-4 text-white placeholder-slate-500 focus:outline-none focus:border-green-500 input-glow code-input"
                        placeholder="------"
                        required
                        autofocus
                    >
                </div>

                <button 
                    type="submit" 
                    class="w-full bg-gradient-to-r from-green-500 to-emerald-600 text-white py-4 rounded-xl font-bold hover:from-green-600 hover:to-emerald-700 transition-all"
                >
                    <i class="fas fa-check-circle mr-2"></i>
                    Verifikasi Kode
                </button>
            </form>

            <div class="mt-6 text-center space-y-3">
                <p class="text-slate-500 text-sm">
                    Kode berlaku selama 15 menit
                </p>
                <a href="{{ route('password.request') }}" class="text-blue-400 hover:text-blue-300 text-sm block">
                    <i class="fas fa-redo mr-1"></i>Kirim ulang kode
                </a>
                <a href="{{ route('login') }}" class="text-slate-400 hover:text-white text-sm block">
                    <i class="fas fa-arrow-left mr-1"></i>Kembali ke login
                </a>
            </div>
        </div>
    </div>
</body>
</html>
