<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Log;
use App\Models\LoginHistory;
use App\Models\StockOutRequest;
use Illuminate\Http\Request;

class UserMonitorController extends Controller
{
    // Daftar semua user dengan filter lengkap
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'all');
        $role = $request->get('role', 'all');
        $search = $request->get('search');
        
        $query = User::query();

        // Filter status
        if ($filter === 'online') {
            $query->where('is_approved', true)
                  ->where('last_activity_at', '>=', now()->subMinutes(5));
        } elseif ($filter === 'recent') {
            $query->where('is_approved', true)
                  ->where('last_activity_at', '>=', now()->subMinutes(30));
        } elseif ($filter === 'today') {
            $query->where('is_approved', true)
                  ->whereDate('last_login_at', today());
        } elseif ($filter === 'approved') {
            $query->where('is_approved', true);
        } elseif ($filter === 'pending') {
            $query->where('is_approved', false);
        }

        // Filter role
        if ($role !== 'all' && in_array($role, ['admin', 'admin_gudang', 'staff'])) {
            $query->where('role', $role);
        }

        // Search
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15);

        // Statistics
        $stats = [
            'total' => User::count(),
            'approved' => User::where('is_approved', true)->count(),
            'pending' => User::where('is_approved', false)->count(),
            'online' => User::where('is_approved', true)
                ->where('last_activity_at', '>=', now()->subMinutes(5))
                ->count(),
            'admin' => User::where('role', 'admin')->where('is_approved', true)->count(),
            'admin_gudang' => User::where('role', 'admin_gudang')->where('is_approved', true)->count(),
            'staff' => User::where('role', 'staff')->where('is_approved', true)->count(),
        ];

        return view('users.index', compact('users', 'filter', 'role', 'search', 'stats'));
    }

    // Detail user lengkap dengan data pendaftaran
    public function show($id)
    {
        $user = User::with(['approvedByUser'])->findOrFail($id);
        
        // Login histories
        $loginHistories = LoginHistory::where('user_id', $id)
            ->latest('created_at')
            ->limit(10)
            ->get();
        
        // Activity logs
        $activityLogs = Log::where('user_id', $id)
            ->latest()
            ->limit(20)
            ->get();

        // Stock out requests (untuk staff)
        $stockRequests = StockOutRequest::where('user_id', $id)
            ->with('item')
            ->latest()
            ->limit(10)
            ->get();

        // Statistics
        $userStats = [
            'total_logins' => LoginHistory::where('user_id', $id)->where('action', 'login')->count(),
            'total_activities' => Log::where('user_id', $id)->count(),
            'total_requests' => StockOutRequest::where('user_id', $id)->count(),
            'approved_requests' => StockOutRequest::where('user_id', $id)->where('status', 'completed')->count(),
        ];

        return view('users.show', compact('user', 'loginHistories', 'activityLogs', 'stockRequests', 'userStats'));
    }

    // Login history semua user atau per user
    public function loginHistory(Request $request)
    {
        $userId = $request->get('user_id');
        $action = $request->get('action');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = LoginHistory::with('user')
            ->latest('created_at');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($action && in_array($action, ['login', 'logout'])) {
            $query->where('action', $action);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $histories = $query->paginate(25);

        $users = User::where('is_approved', true)
            ->orderBy('name')
            ->get();

        // Summary stats
        $stats = [
            'total_logins' => LoginHistory::where('action', 'login')
                ->when($dateFrom, fn($q) => $q->whereDate('created_at', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->whereDate('created_at', '<=', $dateTo))
                ->count(),
            'total_logouts' => LoginHistory::where('action', 'logout')
                ->when($dateFrom, fn($q) => $q->whereDate('created_at', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->whereDate('created_at', '<=', $dateTo))
                ->count(),
            'unique_users' => LoginHistory::query()
                ->when($dateFrom, fn($q) => $q->whereDate('created_at', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->whereDate('created_at', '<=', $dateTo))
                ->distinct('user_id')
                ->count('user_id'),
        ];

        return view('users.login-history', compact('histories', 'users', 'userId', 'action', 'dateFrom', 'dateTo', 'stats'));
    }

    // Activity logs semua user atau per user
    public function activityLogs(Request $request)
    {
        $userId = $request->get('user_id');
        $action = $request->get('action');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = Log::with('user')
            ->latest();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($action) {
            $query->where('action', $action);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // Batasi maksimal 100 data activity logs
        $logs = $query->limit(100)->paginate(25);

        $users = User::where('is_approved', true)
            ->orderBy('name')
            ->get();

        // Get unique actions for filter
        $actions = Log::select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('users.activity-logs', compact('logs', 'users', 'actions', 'userId', 'action', 'dateFrom', 'dateTo'));
    }

    // Edit user role (Admin only)
    public function editRole($id)
    {
        $user = User::findOrFail($id);
        
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat mengubah role diri sendiri.');
        }

        return view('users.edit-role', compact('user'));
    }

    // Update user role
    public function updateRole(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat mengubah role diri sendiri.');
        }

        $request->validate([
            'role' => 'required|in:admin,admin_gudang,staff',
        ]);

        $oldRole = $user->getRoleName();
        $user->update(['role' => $request->role]);
        $newRole = $user->getRoleName();

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'user_role_changed',
            'description' => auth()->user()->name . ' mengubah role ' . $user->name . ' dari ' . $oldRole . ' menjadi ' . $newRole
        ]);

        return redirect()->route('users.show', $user->id)
            ->with('success', 'Role user berhasil diubah menjadi ' . $newRole);
    }

    // Delete user (soft - deactivate)
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $userName = $user->name;
        
        Log::create([
            'user_id' => auth()->id(),
            'action' => 'user_deleted',
            'description' => auth()->user()->name . ' menghapus akun ' . $userName
        ]);

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'Akun ' . $userName . ' berhasil dihapus.');
    }

    // Clear all login history
    public function clearLoginHistory()
    {
        $count = LoginHistory::count();
        LoginHistory::truncate();

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'login_history_cleared',
            'description' => auth()->user()->name . ' menghapus semua login history (' . $count . ' records)'
        ]);

        return redirect()->route('users.login-history')
            ->with('success', 'Semua login history berhasil dihapus (' . $count . ' records).');
    }

    // Clear all activity logs
    public function clearActivityLogs()
    {
        $count = Log::count();
        Log::truncate();

        // Buat log baru setelah clear
        Log::create([
            'user_id' => auth()->id(),
            'action' => 'activity_logs_cleared',
            'description' => auth()->user()->name . ' menghapus semua activity logs (' . $count . ' records)'
        ]);

        return redirect()->route('users.activity-logs')
            ->with('success', 'Semua activity logs berhasil dihapus (' . $count . ' records).');
    }
}
