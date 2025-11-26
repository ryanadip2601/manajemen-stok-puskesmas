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
        .input-glow:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }
    </style>
</head>
<body class="bg-pattern min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo & Title -->
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-gradient-to-br from-blue-400 to-purple-500 rounded-3xl flex items-center justify-center shadow-2xl shadow-blue-500/30 mx-auto mb-4">
                <i class="fas fa-lock-open text-white text-3xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-white">Reset Password</h1>
            <p class="text-slate-400 mt-2">Buat password baru untuk akun Anda</p>
        </div>

        <!-- Reset Password Card -->
        <div class="bg-slate-800/50 backdrop-blur-xl rounded-3xl shadow-2xl border border-slate-700/50 p-8">
            @if($errors->any())
                <div class="bg-red-500/20 border border-red-500/50 text-red-300 p-4 mb-6 rounded-xl">
                    <ul class="list-disc list-inside text-sm">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-emerald-500/20 border border-emerald-500/50 rounded-xl p-4 mb-6">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-lg bg-emerald-500/30 flex items-center justify-center mr-3">
                        <i class="fas fa-check text-emerald-400"></i>
                    </div>
                    <p class="text-emerald-300 text-sm">
                        Kode verifikasi berhasil! Silakan buat password baru.
                    </p>
                </div>
            </div>

            <form method="POST" action="{{ route('password.reset') }}">
                @csrf
                
                <div class="mb-4">
                    <label class="block text-slate-400 text-sm font-medium mb-2">
                        <i class="fas fa-lock mr-2"></i>Password Baru
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            name="password"
                            id="password"
                            class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-4 text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 input-glow"
                            placeholder="Minimal 6 karakter"
                            required
                        >
                        <button type="button" onclick="togglePassword('password', 'icon1')" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-slate-500 hover:text-slate-300">
                            <i class="fas fa-eye" id="icon1"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-slate-400 text-sm font-medium mb-2">
                        <i class="fas fa-lock mr-2"></i>Konfirmasi Password
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            name="password_confirmation"
                            id="password_confirmation"
                            class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-4 text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 input-glow"
                            placeholder="Ulangi password baru"
                            required
                        >
                        <button type="button" onclick="togglePassword('password_confirmation', 'icon2')" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-slate-500 hover:text-slate-300">
                            <i class="fas fa-eye" id="icon2"></i>
                        </button>
                    </div>
                </div>

                <button 
                    type="submit" 
                    class="w-full bg-gradient-to-r from-emerald-500 to-teal-600 text-white py-4 rounded-xl font-bold hover:from-emerald-600 hover:to-teal-700 transition-all"
                >
                    <i class="fas fa-save mr-2"></i>
                    Simpan Password Baru
                </button>
            </form>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8">
            <p class="text-slate-500 text-sm">&copy; {{ date('Y') }} UPTD Puskesmas Karang Rejo</p>
        </div>
    </div>

    <script>
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
