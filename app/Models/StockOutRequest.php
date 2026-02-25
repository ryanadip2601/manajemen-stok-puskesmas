<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockOutRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'item_id',
        'quantity',
        'notes',
        'status',
        'request_date',
        'pickup_date',
        'processed_by',
        'processed_at',
        'reject_reason',
    ];

    protected $casts = [
        'request_date' => 'date',
        'pickup_date' => 'date',
        'processed_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function processedByUser()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // Status checks
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    // Get status badge class
    public function getStatusBadgeClass()
    {
        return match($this->status) {
            'pending' => 'bg-yellow-500/20 text-yellow-400',
            'approved' => 'bg-blue-500/20 text-blue-400',
            'rejected' => 'bg-red-500/20 text-red-400',
            'cancelled' => 'bg-slate-500/20 text-slate-400',
            'completed' => 'bg-green-500/20 text-green-400',
            default => 'bg-slate-500/20 text-slate-400'
        };
    }

    // Get status label
    public function getStatusLabel()
    {
        return match($this->status) {
            'pending' => 'Menunggu',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'cancelled' => 'Dibatalkan',
            'completed' => 'Selesai',
            default => 'Unknown'
        };
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('request_date', today());
    }
}
