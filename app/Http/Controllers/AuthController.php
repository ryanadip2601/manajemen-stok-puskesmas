<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'password' => 'required|min:6|confirmed',
        ], [
            'name.required' => 'Nama harus diisi',
            'email.required' => 'Email harus diisi',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah terdaftar',
            'phone.required' => 'Nomor HP harus diisi',
            'password.required' => 'Password harus diisi',
            'password.min' => 'Password minimal 6 karakter',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'staff', // Default role untuk pendaftaran baru
        ]);

        Auth::login($user);

        Log::create([
            'user_id' => $user->id,
            'action' => 'register',
            'description' => 'User baru ' . $user->name . ' mendaftar sebagai staff'
        ]);

        return redirect()->route('dashboard')->with('success', 'Selamat datang! Akun Anda berhasil dibuat.');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required',
        ], [
            'email.required' => 'Username/Email harus diisi',
            'password.required' => 'Password harus diisi',
        ]);

        if (Auth::attempt($request->only('email', 'password'), $request->filled('remember'))) {
            $request->session()->regenerate();

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'login',
                'description' => 'User ' . Auth::user()->name . ' berhasil login'
            ]);

            return redirect()->intended(route('dashboard'));
        }

        throw ValidationException::withMessages([
            'email' => 'Username/Email atau password salah.',
        ]);
    }

    // Profile & Change Password
    public function showProfile()
    {
        return view('auth.profile');
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ], [
            'name.required' => 'Nama harus diisi',
        ]);

        $user->update([
            'name' => $request->name,
            'phone' => $request->phone,
        ]);

        Log::create([
            'user_id' => $user->id,
            'action' => 'profile_updated',
            'description' => 'User ' . $user->name . ' mengupdate profil'
        ]);

        return back()->with('success', 'Profil berhasil diperbarui');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:6|confirmed',
        ], [
            'current_password.required' => 'Password saat ini harus diisi',
            'password.required' => 'Password baru harus diisi',
            'password.min' => 'Password baru minimal 6 karakter',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini salah']);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        Log::create([
            'user_id' => $user->id,
            'action' => 'password_changed',
            'description' => 'User ' . $user->name . ' mengubah password'
        ]);

        return back()->with('success', 'Password berhasil diubah');
    }

    public function logout(Request $request)
    {
        Log::create([
            'user_id' => Auth::id(),
            'action' => 'logout',
            'description' => 'User ' . Auth::user()->name . ' logout'
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    // Forgot Password
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetCode(Request $request)
    {
        $request->validate([
            'email_or_phone' => 'required',
        ], [
            'email_or_phone.required' => 'Email atau nomor HP harus diisi',
        ]);

        $input = $request->email_or_phone;
        
        // Cari user berdasarkan email atau phone
        $user = User::where('email', $input)
            ->orWhere('phone', $input)
            ->first();

        if (!$user) {
            return back()->with('error', 'Email atau nomor HP tidak ditemukan.');
        }

        // Generate reset code
        $code = $user->generateResetCode();

        // Simpan user id di session untuk verifikasi
        session(['reset_user_id' => $user->id]);

        // Buat pesan WhatsApp
        $message = "🔐 *KODE RESET PASSWORD*\n\n";
        $message .= "Kode verifikasi Anda: *{$code}*\n\n";
        $message .= "Kode ini berlaku selama 15 menit.\n";
        $message .= "Jangan bagikan kode ini kepada siapapun.\n\n";
        $message .= "_UPTD Puskesmas Karang Rejo_";

        // Redirect ke halaman dengan link WhatsApp dan form verifikasi
        return view('auth.verify-code', [
            'user' => $user,
            'whatsapp_link' => $user->getWhatsAppLink($message),
            'code' => $code, // Hanya untuk development, hapus di production
        ]);
    }

    public function showVerifyCode()
    {
        if (!session('reset_user_id')) {
            return redirect()->route('password.request')->with('error', 'Silakan mulai proses reset password dari awal.');
        }
        
        $user = User::find(session('reset_user_id'));
        if (!$user) {
            return redirect()->route('password.request')->with('error', 'User tidak ditemukan.');
        }

        return view('auth.verify-code', ['user' => $user]);
    }

    public function verifyCode(Request $request)
    {
        $request->validate([
            'code' => 'required|digits:6',
        ], [
            'code.required' => 'Kode verifikasi harus diisi',
            'code.digits' => 'Kode verifikasi harus 6 digit',
        ]);

        $user = User::find(session('reset_user_id'));
        
        if (!$user) {
            return redirect()->route('password.request')->with('error', 'Silakan mulai proses reset password dari awal.');
        }

        if (!$user->verifyResetCode($request->code)) {
            return back()->with('error', 'Kode verifikasi salah atau sudah kadaluarsa.');
        }

        session(['reset_code_verified' => true]);

        return redirect()->route('password.reset.form');
    }

    public function showResetPassword()
    {
        if (!session('reset_user_id') || !session('reset_code_verified')) {
            return redirect()->route('password.request')->with('error', 'Silakan mulai proses reset password dari awal.');
        }

        return view('auth.reset-password');
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|min:6|confirmed',
        ], [
            'password.required' => 'Password baru harus diisi',
            'password.min' => 'Password minimal 6 karakter',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
        ]);

        $user = User::find(session('reset_user_id'));
        
        if (!$user) {
            return redirect()->route('password.request')->with('error', 'Silakan mulai proses reset password dari awal.');
        }

        $user->password = Hash::make($request->password);
        $user->clearResetCode();
        $user->save();

        // Clear session
        session()->forget(['reset_user_id', 'reset_code_verified']);

        Log::create([
            'user_id' => $user->id,
            'action' => 'reset_password',
            'description' => 'User ' . $user->name . ' berhasil reset password'
        ]);

        return redirect()->route('login')->with('success', 'Password berhasil diubah. Silakan login dengan password baru.');
    }

    // API Methods
    public function loginApi(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        Log::create([
            'user_id' => $user->id,
            'action' => 'api_login',
            'description' => 'User ' . $user->name . ' login via API'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ]);
    }

    public function logoutApi(Request $request)
    {
        Log::create([
            'user_id' => $request->user()->id,
            'action' => 'api_logout',
            'description' => 'User ' . $request->user()->name . ' logout via API'
        ]);

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil'
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()
        ]);
    }
}
