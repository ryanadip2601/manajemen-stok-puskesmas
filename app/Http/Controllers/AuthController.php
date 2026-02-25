<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Log;
use App\Models\LoginHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

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
            'role' => 'staff',
            'is_approved' => false,
        ]);

        Log::create([
            'user_id' => $user->id,
            'action' => 'register',
            'description' => 'User baru ' . $user->name . ' mendaftar, menunggu persetujuan admin'
        ]);

        return redirect()->route('login')->with('success', 'Pendaftaran berhasil! Akun Anda sedang menunggu persetujuan dari administrator. Anda akan menerima notifikasi setelah akun disetujui.');
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

        // Case-insensitive email lookup
        $user = User::whereRaw('LOWER(email) = ?', [strtolower($request->email)])->first();

        // Cek apakah email/username ditemukan
        if (!$user) {
            throw ValidationException::withMessages([
                'email' => 'Email tidak terdaftar dalam sistem.',
            ]);
        }

        // Cek apakah password benar
        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Password yang Anda masukkan salah.',
            ]);
        }

        // Cek apakah akun sudah disetujui
        if (!$user->is_approved) {
            throw ValidationException::withMessages([
                'email' => 'Akun Anda belum disetujui oleh administrator. Silakan tunggu persetujuan atau hubungi admin.',
            ]);
        }

        Auth::login($user, $request->filled('remember'));
        $request->session()->regenerate();

        // Record login history
        LoginHistory::recordLogin($user, $request);

        Log::create([
            'user_id' => Auth::id(),
            'action' => 'login',
            'description' => 'User ' . Auth::user()->name . ' berhasil login'
        ]);

        return redirect()->intended(route('dashboard'));
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
        $user = Auth::user();
        
        // Record logout history
        LoginHistory::recordLogout($user, $request);

        Log::create([
            'user_id' => $user->id,
            'action' => 'logout',
            'description' => 'User ' . $user->name . ' logout'
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    // ==================== PASSWORD RESET VIA EMAIL ====================

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'Email harus diisi',
            'email.email' => 'Format email tidak valid',
        ]);

        // Case-insensitive email lookup
        $user = User::whereRaw('LOWER(email) = ?', [strtolower($request->email)])->first();

        if (!$user) {
            return back()->with('error', 'Email tidak terdaftar dalam sistem.');
        }

        // Generate token
        $token = Str::random(64);

        // Delete existing token for this email
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // Insert new token
        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => Hash::make($token),
            'created_at' => Carbon::now()
        ]);

        // Send email
        $resetLink = route('password.reset.form', ['token' => $token, 'email' => $request->email]);
        
        try {
            Mail::send('emails.password-reset', ['resetLink' => $resetLink, 'user' => $user], function($message) use ($user) {
                $message->to($user->email);
                $message->subject('Reset Password - UPTD Puskesmas Karang Rejo');
            });

            Log::create([
                'user_id' => $user->id,
                'action' => 'password_reset_requested',
                'description' => 'User ' . $user->name . ' meminta reset password via email'
            ]);

            return back()->with('success', 'Link reset password telah dikirim ke email Anda. Silakan cek inbox atau folder spam.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengirim email. Silakan coba lagi atau hubungi administrator. Error: ' . $e->getMessage());
        }
    }

    public function showResetPassword(Request $request)
    {
        $token = $request->query('token');
        $email = $request->query('email');

        if (!$token || !$email) {
            return redirect()->route('password.request')->with('error', 'Link reset password tidak valid.');
        }

        // Verify token exists
        $resetRecord = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$resetRecord) {
            return redirect()->route('password.request')->with('error', 'Link reset password tidak valid atau sudah kadaluarsa.');
        }

        // Check if token is expired (60 minutes)
        if (Carbon::parse($resetRecord->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return redirect()->route('password.request')->with('error', 'Link reset password sudah kadaluarsa. Silakan minta link baru.');
        }

        // Verify token hash
        if (!Hash::check($token, $resetRecord->token)) {
            return redirect()->route('password.request')->with('error', 'Link reset password tidak valid.');
        }

        return view('auth.reset-password', ['token' => $token, 'email' => $email]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
        ], [
            'password.required' => 'Password baru harus diisi',
            'password.min' => 'Password minimal 6 karakter',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
        ]);

        $resetRecord = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (!$resetRecord) {
            return back()->with('error', 'Token reset password tidak valid.');
        }

        if (Carbon::parse($resetRecord->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return redirect()->route('password.request')->with('error', 'Token sudah kadaluarsa. Silakan minta link baru.');
        }

        if (!Hash::check($request->token, $resetRecord->token)) {
            return back()->with('error', 'Token reset password tidak valid.');
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->with('error', 'User tidak ditemukan.');
        }

        $user->password = Hash::make($request->password);
        $user->save();

        // Delete used token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        Log::create([
            'user_id' => $user->id,
            'action' => 'password_reset_success',
            'description' => 'User ' . $user->name . ' berhasil reset password via email'
        ]);

        return redirect()->route('login')->with('success', 'Password berhasil diubah! Silakan login dengan password baru Anda.');
    }

    // ==================== ADMIN: USER APPROVAL ====================

    public function pendingUsers()
    {
        $pendingUsers = User::where('is_approved', false)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('auth.pending-users', compact('pendingUsers'));
    }

    public function approveUser($id)
    {
        $user = User::findOrFail($id);

        if ($user->is_approved) {
            return back()->with('error', 'User sudah disetujui sebelumnya.');
        }

        $user->approve(auth()->id());

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'user_approved',
            'description' => 'Admin ' . auth()->user()->name . ' menyetujui akun ' . $user->name
        ]);

        // Send approval notification email
        try {
            Mail::send('emails.account-approved', ['user' => $user], function($message) use ($user) {
                $message->to($user->email);
                $message->subject('Akun Anda Telah Disetujui - UPTD Puskesmas Karang Rejo');
            });
        } catch (\Exception $e) {
            // Email failed but approval succeeded
        }

        return back()->with('success', 'Akun ' . $user->name . ' berhasil disetujui.');
    }

    public function rejectUser($id)
    {
        $user = User::findOrFail($id);

        if ($user->is_approved) {
            return back()->with('error', 'Tidak dapat menolak user yang sudah disetujui.');
        }

        $userName = $user->name;
        $userEmail = $user->email;

        // Send rejection notification email before deleting
        try {
            Mail::send('emails.account-rejected', ['userName' => $userName], function($message) use ($userEmail) {
                $message->to($userEmail);
                $message->subject('Pendaftaran Akun Ditolak - UPTD Puskesmas Karang Rejo');
            });
        } catch (\Exception $e) {
            // Email failed but rejection proceeds
        }

        $user->reject();

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'user_rejected',
            'description' => 'Admin ' . auth()->user()->name . ' menolak pendaftaran ' . $userName
        ]);

        return back()->with('success', 'Pendaftaran ' . $userName . ' ditolak dan dihapus dari sistem.');
    }

    // API Methods
    public function loginApi(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Case-insensitive email lookup
        $user = User::whereRaw('LOWER(email) = ?', [strtolower($request->email)])->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah'
            ], 401);
        }

        if (!$user->is_approved) {
            return response()->json([
                'success' => false,
                'message' => 'Akun belum disetujui oleh administrator'
            ], 403);
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
