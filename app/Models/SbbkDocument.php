<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SbbkDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomor_sbbk',
        'tanggal_sbbk',
        'unit_peminta',
        'perihal',
        'keterangan',
        'file_path',
        'file_name',
        'file_mime',
        'file_size',
        'uploaded_by',
    ];

    protected $casts = [
        'tanggal_sbbk' => 'date',
        'file_size' => 'integer',
    ];

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFileUrlAttribute()
    {
        return asset($this->file_path);
    }
}
