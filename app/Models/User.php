<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
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
            'password' => 'hashed',
            'reset_code_expires_at' => 'datetime',
        ];
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

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isStaff()
    {
        return $this->role === 'staff';
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
