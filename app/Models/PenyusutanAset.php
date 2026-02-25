<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenyusutanAset extends Model
{
    use HasFactory;

    protected $table = 'penyusutan_aset';

    protected $fillable = [
        'barang_aset_id',
        'tahun',
        'bulan',
        'tanggal_hitung',
        'nilai_perolehan',
        'nilai_residu',
        'umur_manfaat',
        'penyusutan_per_tahun',
        'penyusutan_per_bulan',
        'penyusutan_periode',
        'akumulasi_penyusutan_awal',
        'akumulasi_penyusutan_akhir',
        'nilai_buku_awal',
        'nilai_buku_akhir',
        'mutasi_penambahan',
        'mutasi_pengurangan',
        'mutasi_koreksi',
        'metode_penyusutan',
        'keterangan',
        'created_by',
    ];

    protected $casts = [
        'tanggal_hitung' => 'date',
        'tahun' => 'integer',
        'bulan' => 'integer',
        'umur_manfaat' => 'integer',
        'nilai_perolehan' => 'decimal:2',
        'nilai_residu' => 'decimal:2',
        'penyusutan_per_tahun' => 'decimal:2',
        'penyusutan_per_bulan' => 'decimal:2',
        'penyusutan_periode' => 'decimal:2',
        'akumulasi_penyusutan_awal' => 'decimal:2',
        'akumulasi_penyusutan_akhir' => 'decimal:2',
        'nilai_buku_awal' => 'decimal:2',
        'nilai_buku_akhir' => 'decimal:2',
        'mutasi_penambahan' => 'decimal:2',
        'mutasi_pengurangan' => 'decimal:2',
        'mutasi_koreksi' => 'decimal:2',
    ];

    // Relationships
    public function barangAset()
    {
        return $this->belongsTo(BarangAset::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeTahun($query, $tahun)
    {
        return $query->where('tahun', $tahun);
    }

    public function scopeBulan($query, $bulan)
    {
        return $query->where('bulan', $bulan);
    }

    public function scopeTahunan($query)
    {
        return $query->whereNull('bulan');
    }

    public function scopeBulanan($query)
    {
        return $query->whereNotNull('bulan');
    }

    // Accessors
    public function getPeriodeLabelAttribute()
    {
        if ($this->bulan) {
            $namaBulan = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
                4 => 'April', 5 => 'Mei', 6 => 'Juni',
                7 => 'Juli', 8 => 'Agustus', 9 => 'September',
                10 => 'Oktober', 11 => 'November', 12 => 'Desember',
            ];
            return $namaBulan[$this->bulan] . ' ' . $this->tahun;
        }
        return 'Tahun ' . $this->tahun;
    }

    public function getPersentasePenyusutanAttribute()
    {
        if ($this->nilai_perolehan > 0) {
            return round(($this->akumulasi_penyusutan_akhir / $this->nilai_perolehan) * 100, 2);
        }
        return 0;
    }

    public function getSisaUmurBulanAttribute()
    {
        $totalBulan = $this->umur_manfaat * 12;
        $bulanTerpakai = ($this->akumulasi_penyusutan_akhir / $this->penyusutan_per_bulan);
        return max(0, round($totalBulan - $bulanTerpakai));
    }

    // Static methods
    public static function getMetodePenyusutanOptions()
    {
        return [
            'garis_lurus' => 'Garis Lurus (Straight Line)',
            'saldo_menurun' => 'Saldo Menurun (Declining Balance)',
            'satuan_produksi' => 'Satuan Produksi (Units of Production)',
        ];
    }

    // Summary methods
    public static function getSummaryTahun($tahun)
    {
        return self::where('tahun', $tahun)
            ->whereNull('bulan')
            ->selectRaw('
                COUNT(*) as total_aset,
                SUM(nilai_perolehan) as total_nilai_perolehan,
                SUM(penyusutan_periode) as total_penyusutan,
                SUM(akumulasi_penyusutan_akhir) as total_akumulasi,
                SUM(nilai_buku_akhir) as total_nilai_buku
            ')
            ->first();
    }
}
