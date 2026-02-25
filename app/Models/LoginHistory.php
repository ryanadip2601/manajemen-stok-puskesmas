<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginHistory extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'user_id',
        'action',
        'ip_address',
        'user_agent',
        'device',
        'browser',
        'platform',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isLogin()
    {
        return $this->action === 'login';
    }

    public function isLogout()
    {
        return $this->action === 'logout';
    }

    public function getActionBadgeClass()
    {
        return $this->action === 'login' 
            ? 'bg-green-500/20 text-green-400' 
            : 'bg-red-500/20 text-red-400';
    }

    public function getActionLabel()
    {
        return $this->action === 'login' ? 'Login' : 'Logout';
    }

    // Parse user agent to get device info
    public static function parseUserAgent($userAgent)
    {
        $device = 'Unknown';
        $browser = 'Unknown';
        $platform = 'Unknown';

        // Detect platform
        if (preg_match('/windows/i', $userAgent)) {
            $platform = 'Windows';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            $platform = 'Mac OS';
        } elseif (preg_match('/linux/i', $userAgent)) {
            $platform = 'Linux';
        } elseif (preg_match('/android/i', $userAgent)) {
            $platform = 'Android';
        } elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
            $platform = 'iOS';
        }

        // Detect browser
        if (preg_match('/MSIE|Trident/i', $userAgent)) {
            $browser = 'Internet Explorer';
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Chrome/i', $userAgent)) {
            if (preg_match('/Edg/i', $userAgent)) {
                $browser = 'Edge';
            } else {
                $browser = 'Chrome';
            }
        } elseif (preg_match('/Safari/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Opera|OPR/i', $userAgent)) {
            $browser = 'Opera';
        }

        // Detect device type
        if (preg_match('/mobile|android|iphone|ipad|ipod/i', $userAgent)) {
            $device = 'Mobile';
        } elseif (preg_match('/tablet/i', $userAgent)) {
            $device = 'Tablet';
        } else {
            $device = 'Desktop';
        }

        return [
            'device' => $device,
            'browser' => $browser,
            'platform' => $platform,
        ];
    }

    // Record login
    public static function recordLogin($user, $request)
    {
        $userAgentInfo = self::parseUserAgent($request->userAgent());
        
        self::create([
            'user_id' => $user->id,
            'action' => 'login',
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent(), 0, 255),
            'device' => $userAgentInfo['device'],
            'browser' => $userAgentInfo['browser'],
            'platform' => $userAgentInfo['platform'],
            'created_at' => now(),
        ]);

        // Update user's last login info
        $user->update([
            'last_login_at' => now(),
            'last_activity_at' => now(),
            'last_ip_address' => $request->ip(),
        ]);
    }

    // Record logout
    public static function recordLogout($user, $request)
    {
        $userAgentInfo = self::parseUserAgent($request->userAgent());
        
        self::create([
            'user_id' => $user->id,
            'action' => 'logout',
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent(), 0, 255),
            'device' => $userAgentInfo['device'],
            'browser' => $userAgentInfo['browser'],
            'platform' => $userAgentInfo['platform'],
            'created_at' => now(),
        ]);
    }
}
