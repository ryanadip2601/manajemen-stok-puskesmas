<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;

class User extends Authenticatable implements CanResetPassword
{
    use HasFactory, Notifiable, CanResetPasswordTrait;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'is_approved',
        'approved_at',
        'approved_by',
        'last_login_at',
        'last_activity_at',
        'last_ip_address',
        'reset_code',
        'reset_code_expires_at',
    ];

    protected $hidden = [
        'password',
        'reset_code',
    ];

    protected function casts(): array
    {
        return [
            // Hapus 'hashed' cast - gunakan Hash::make() manual di controller
            'is_approved' => 'boolean',
            'approved_at' => 'datetime',
            'last_login_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'reset_code_expires_at' => 'datetime',
        ];
    }

    public function loginHistories()
    {
        return $this->hasMany(LoginHistory::class);
    }

    // Check if user is online (active in last 5 minutes)
    public function isOnline()
    {
        if (!$this->last_activity_at) return false;
        
        try {
            $lastActivity = $this->last_activity_at instanceof \Carbon\Carbon 
                ? $this->last_activity_at 
                : \Carbon\Carbon::parse($this->last_activity_at);
            return $lastActivity->diffInMinutes(now()) < 5;
        } catch (\Exception $e) {
            return false;
        }
    }

    // Check if user was recently active (in last 30 minutes)
    public function isRecentlyActive()
    {
        if (!$this->last_activity_at) return false;
        
        try {
            $lastActivity = $this->last_activity_at instanceof \Carbon\Carbon 
                ? $this->last_activity_at 
                : \Carbon\Carbon::parse($this->last_activity_at);
            return $lastActivity->diffInMinutes(now()) < 30;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function stockIns()
    {
        return $this->hasMany(StockIn::class);
    }

    public function stockOuts()
    {
        return $this->hasMany(StockOut::class);
    }

    public function logs()
    {
        return $this->hasMany(Log::class);
    }

    public function usulanPenerimaan()
    {
        return $this->hasMany(UsulanPenerimaanBarang::class);
    }

    public function approvedByUser()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isAdminGudang()
    {
        return $this->role === 'admin_gudang';
    }

    public function isStaff()
    {
        return $this->role === 'staff';
    }

    public function isApproved()
    {
        return $this->is_approved === true;
    }

    public function isPending()
    {
        return $this->is_approved === false;
    }

    // Check if user can manage warehouse (admin or admin_gudang)
    public function canManageWarehouse()
    {
        return in_array($this->role, ['admin', 'admin_gudang']);
    }

    // Check if user can manage users (admin only)
    public function canManageUsers()
    {
        return $this->role === 'admin';
    }

    // Get role display name
    public function getRoleName()
    {
        return match($this->role) {
            'admin' => 'Administrator',
            'admin_gudang' => 'Admin Gudang',
            'staff' => 'Pegawai',
            default => 'Unknown'
        };
    }

    // Get role badge color
    public function getRoleBadgeClass()
    {
        return match($this->role) {
            'admin' => 'bg-red-500',
            'admin_gudang' => 'bg-blue-500',
            'staff' => 'bg-green-500',
            default => 'bg-gray-500'
        };
    }

    public function approve($adminId)
    {
        $this->update([
            'is_approved' => true,
            'approved_at' => now(),
            'approved_by' => $adminId,
        ]);
    }

    public function reject()
    {
        $this->delete();
    }

    public function generateResetCode()
    {
        $this->reset_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->reset_code_expires_at = now()->addMinutes(15);
        $this->save();
        
        return $this->reset_code;
    }

    public function verifyResetCode($code)
    {
        return $this->reset_code === $code && 
               $this->reset_code_expires_at && 
               $this->reset_code_expires_at->isFuture();
    }

    public function clearResetCode()
    {
        $this->reset_code = null;
        $this->reset_code_expires_at = null;
        $this->save();
    }

    public function getWhatsAppLink($message)
    {
        $phone = preg_replace('/[^0-9]/', '', $this->phone);
        if (substr($phone, 0, 1) === '0') {
            $phone = '62' . substr($phone, 1);
        }
        return "https://wa.me/{$phone}?text=" . urlencode($message);
    }
}
