<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ruangan;
use App\Models\KategoriAset;
use App\Models\BarangAset;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncBarangAsetSeeder extends Seeder
{
    private $dataPath = 'C:/Users/Ryana/Downloads/BARANG ASSET';
    private $kategoriMap = [];
    private $ruanganMap = [];
    private $stats = [
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
    ];

    public function run(): void
    {
        $this->command->info('==============================================');
        $this->command->info('SINKRONISASI DATA BARANG ASET DARI EXCEL');
        $this->command->info('==============================================');
        
        DB::beginTransaction();
        
        try {
            // Step 1: Remove invalid entries
            $this->cleanupInvalidData();
            
            // Step 2: Load kategori map
            $this->loadKategoriMap();
            
            // Step 3: Process Excel files
            $this->processFile02(); // Main inventory data
            $this->processFile01(); // Additional data
            
            DB::commit();
            
            $this->command->info('');
            $this->command->info('==============================================');
            $this->command->info('SINKRONISASI SELESAI!');
            $this->command->info('==============================================');
            $this->command->info("Dibuat baru: {$this->stats['created']}");
            $this->command->info("Diperbarui: {$this->stats['updated']}");
            $this->command->info("Dilewati: {$this->stats['skipped']}");
            $this->command->info("Total Ruangan: " . Ruangan::count());
            $this->command->info("Total Barang Aset: " . BarangAset::count());
            $this->command->info('==============================================');
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error: ' . $e->getMessage());
            Log::error('Sync Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        }
    }

    private function cleanupInvalidData(): void
    {
        $this->command->info('');
        $this->command->info('Step 1: Membersihkan data invalid...');
        
        // Remove items with invalid names
        $deleted = BarangAset::where('nama_aset', 'like', '%Tidak Diketahui%')
            ->orWhere('nama_aset', 'like', '%#REF%')
            ->orWhere('nama_aset', 'like', '%#VALUE%')
            ->delete();
        
        $this->command->info("  Dihapus: $deleted item invalid");
    }

    private function loadKategoriMap(): void
    {
        $this->command->info('');
        $this->command->info('Step 2: Memuat kategori aset...');
        
        // Default categories based on kode barang patterns
        $categories = [
            ['nama' => 'Alat Kesehatan', 'kode' => '02.03', 'golongan' => 'peralatan_mesin', 'umur' => 5],
            ['nama' => 'Mebel/Furnitur', 'kode' => '02.04', 'golongan' => 'peralatan_mesin', 'umur' => 10],
            ['nama' => 'Rak & Penyimpanan', 'kode' => '02.05', 'golongan' => 'peralatan_mesin', 'umur' => 10],
            ['nama' => 'Peralatan Kantor', 'kode' => '02.06', 'golongan' => 'peralatan_mesin', 'umur' => 5],
            ['nama' => 'Komputer & IT', 'kode' => '02.07', 'golongan' => 'peralatan_mesin', 'umur' => 4],
            ['nama' => 'Elektronik', 'kode' => '02.08', 'golongan' => 'peralatan_mesin', 'umur' => 5],
            ['nama' => 'Kendaraan', 'kode' => '02.01', 'golongan' => 'peralatan_mesin', 'umur' => 8],
            ['nama' => 'Lainnya', 'kode' => '02.99', 'golongan' => 'aset_tetap_lainnya', 'umur' => 4],
        ];

        foreach ($categories as $cat) {
            $kategori = KategoriAset::firstOrCreate(
                ['nama_kategori' => $cat['nama']],
                [
                    'kode_golongan' => $cat['kode'],
                    'golongan' => $cat['golongan'],
                    'umur_manfaat' => $cat['umur'],
                    'dapat_disusutkan' => true,
                    'is_active' => true,
                ]
            );
            $this->kategoriMap[$cat['kode']] = $kategori->id;
        }
        
        $this->command->info("  Kategori dimuat: " . count($this->kategoriMap));
    }

    private function processFile02(): void
    {
        $filePath = $this->dataPath . '/file02.xls';
        
        if (!file_exists($filePath)) {
            $this->command->warn("File tidak ditemukan: file02.xls");
            return;
        }
        
        $this->command->info('');
        $this->command->info('Step 3: Memproses file02.xls...');
        
        $spreadsheet = IOFactory::load($filePath);
        $sheetNames = $spreadsheet->getSheetNames();
        
        foreach ($sheetNames as $index => $sheetName) {
            // Skip empty or generic sheet names
            if (empty(trim($sheetName)) || in_array($sheetName, ['Sheet1', 'Sheet2', 'Sheet3'])) {
                continue;
            }
            
            $sheet = $spreadsheet->getSheet($index);
            $this->processInventorySheet($sheet, $sheetName);
        }
    }

    private function processFile01(): void
    {
        $filePath = $this->dataPath . '/file01.xlsx';
        
        if (!file_exists($filePath)) {
            $this->command->warn("File tidak ditemukan: file01.xlsx");
            return;
        }
        
        $this->command->info('');
        $this->command->info('Step 4: Memproses file01.xlsx...');
        
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheet(0);
        
        // Try to get room name
        $ruanganName = $this->getCellValue($sheet, 'D8');
        if (empty($ruanganName)) {
            $ruanganName = 'Ruang Umum';
        }
        
        $this->processInventorySheet($sheet, $ruanganName);
    }

    private function processInventorySheet($sheet, string $sheetName): void
    {
        $ruanganId = $this->getOrCreateRuangan($sheetName);
        $highestRow = $sheet->getHighestRow();
        
        // Find data start row
        $dataStartRow = $this->findDataStartRow($sheet, $highestRow);
        
        if ($dataStartRow === 0) {
            return;
        }
        
        $sheetStats = ['created' => 0, 'updated' => 0, 'skipped' => 0];
        
        for ($row = $dataStartRow; $row <= $highestRow; $row++) {
            $noUrut = $this->getCellValue($sheet, "A$row");
            $namaBarang = $this->getCellValue($sheet, "B$row");
            
            // Skip invalid rows
            if (!$this->isValidDataRow($noUrut, $namaBarang)) {
                continue;
            }
            
            // Extract all columns
            $data = $this->extractRowData($sheet, $row, $ruanganId);
            
            if (empty($data['nama_aset']) || strlen($data['nama_aset']) < 2) {
                $sheetStats['skipped']++;
                continue;
            }
            
            // Try to find existing record to update
            $existing = $this->findExistingAsset($data, $ruanganId);
            
            if ($existing) {
                // Update existing
                $this->updateAsset($existing, $data);
                $sheetStats['updated']++;
            } else {
                // Create new
                $this->createAsset($data);
                $sheetStats['created']++;
            }
        }
        
        $this->stats['created'] += $sheetStats['created'];
        $this->stats['updated'] += $sheetStats['updated'];
        $this->stats['skipped'] += $sheetStats['skipped'];
        
        $total = $sheetStats['created'] + $sheetStats['updated'];
        if ($total > 0) {
            $this->command->info("  $sheetName: +{$sheetStats['created']} baru, ~{$sheetStats['updated']} update");
        }
    }

    private function findDataStartRow($sheet, int $maxRow): int
    {
        $checkRows = min(30, $maxRow);
        
        for ($row = 1; $row <= $checkRows; $row++) {
            $colA = $this->getCellValue($sheet, "A$row");
            $colB = $this->getCellValue($sheet, "B$row");
            
            // Look for first numeric row with actual item name (not header)
            if (is_numeric($colA) && $colA == 1 && !empty($colB)) {
                // Skip if it's a header row (column B contains header text)
                if ($this->isHeaderText($colB)) {
                    continue;
                }
                return $row;
            }
        }
        
        return 0;
    }

    private function isHeaderText(?string $text): bool
    {
        if (empty($text)) return false;
        
        $headers = ['nama barang', 'jenis barang', '2', 'no', 'urt'];
        $lower = strtolower(trim($text));
        
        foreach ($headers as $header) {
            if ($lower === $header || strpos($lower, $header) === 0) {
                return true;
            }
        }
        
        return false;
    }

    private function isValidDataRow($noUrut, $namaBarang): bool
    {
        if (!is_numeric($noUrut)) return false;
        if (empty($namaBarang)) return false;
        
        $skipPatterns = ['JUMLAH', 'MENGETAHUI', 'KEPALA', 'PENGURUS', 'BALIKPAPAN', 'dr.', 'Herawati'];
        
        foreach ($skipPatterns as $pattern) {
            if (stripos($namaBarang, $pattern) !== false) {
                return false;
            }
        }
        
        return true;
    }

    private function extractRowData($sheet, int $row, int $ruanganId): array
    {
        $namaBarang = $this->getCellValue($sheet, "B$row");
        $merkModel = $this->getCellValue($sheet, "C$row");
        $noSeri = $this->getCellValue($sheet, "D$row");
        $ukuran = $this->getCellValue($sheet, "E$row");
        $bahan = $this->getCellValue($sheet, "F$row");
        $tahun = $this->getCellValue($sheet, "G$row");
        $kodeBarang = $this->getCellValue($sheet, "H$row");
        $jumlah = $this->getCellValue($sheet, "I$row");
        $harga = $this->getCellValue($sheet, "J$row");
        $kondisiBaik = $this->getCellValue($sheet, "K$row");
        $kondisiKB = $this->getCellValue($sheet, "L$row");
        $kondisiRB = $this->getCellValue($sheet, "N$row");
        $keterangan = $this->getCellValue($sheet, "O$row");

        // Determine condition
        $kondisi = 'baik';
        if (!empty($kondisiRB) && $this->isConditionMarked($kondisiRB)) {
            $kondisi = 'rusak_berat';
        } elseif (!empty($kondisiKB) && $this->isConditionMarked($kondisiKB)) {
            $kondisi = 'rusak_ringan';
        }

        // Parse values
        $qty = $this->parseQuantity($jumlah, $keterangan);
        $hargaValue = $this->parsePrice($harga);
        $tahunPerolehan = $this->parseYear($tahun);
        
        // Build spesifikasi
        $spesifikasi = trim(implode(', ', array_filter([$ukuran, $bahan]))) ?: null;

        return [
            'nama_aset' => $this->formatNamaAset($namaBarang),
            'ruangan_id' => $ruanganId,
            'kategori_aset_id' => $this->getKategoriId($kodeBarang),
            'merk' => $this->cleanValue($merkModel),
            'no_sertifikat' => $this->cleanValue($noSeri),
            'ukuran_konstruksi' => $spesifikasi,
            'spesifikasi' => $spesifikasi,
            'tahun_perolehan' => $tahunPerolehan,
            'tanggal_masuk' => $tahunPerolehan ? Carbon::createFromDate($tahunPerolehan, 1, 1) : Carbon::now(),
            'jumlah' => $qty,
            'harga_perolehan' => $hargaValue,
            'kondisi' => $kondisi,
            'keterangan' => $this->cleanValue($keterangan),
            'sumber_dana' => $this->parseSumberDana($keterangan),
            'asal_perolehan' => $this->parseAsalPerolehan($keterangan),
            'status_aset' => 'aktif',
        ];
    }

    private function isConditionMarked(?string $value): bool
    {
        if (empty($value)) return false;
        $value = strtoupper(trim($value));
        return $value !== '-' && $value !== '' && ($value === 'B' || $value === 'KB' || $value === 'RB' || is_numeric($value));
    }

    private function findExistingAsset(array $data, int $ruanganId): ?BarangAset
    {
        // Try to match by nama + ruangan + merk + tahun
        $query = BarangAset::where('ruangan_id', $ruanganId)
            ->where('nama_aset', $data['nama_aset']);
        
        if (!empty($data['merk'])) {
            $query->where('merk', $data['merk']);
        }
        
        if (!empty($data['tahun_perolehan'])) {
            $query->where('tahun_perolehan', $data['tahun_perolehan']);
        }
        
        return $query->first();
    }

    private function updateAsset(BarangAset $asset, array $data): void
    {
        // Only update if data is more complete
        $updates = [];
        
        if (empty($asset->merk) && !empty($data['merk'])) {
            $updates['merk'] = $data['merk'];
        }
        if (empty($asset->spesifikasi) && !empty($data['spesifikasi'])) {
            $updates['spesifikasi'] = $data['spesifikasi'];
        }
        if (empty($asset->harga_perolehan) && !empty($data['harga_perolehan'])) {
            $updates['harga_perolehan'] = $data['harga_perolehan'];
        }
        if (empty($asset->tahun_perolehan) && !empty($data['tahun_perolehan'])) {
            $updates['tahun_perolehan'] = $data['tahun_perolehan'];
        }
        if (empty($asset->no_sertifikat) && !empty($data['no_sertifikat'])) {
            $updates['no_sertifikat'] = $data['no_sertifikat'];
        }
        
        // Always update kondisi from source
        $updates['kondisi'] = $data['kondisi'];
        
        if (!empty($updates)) {
            $asset->update($updates);
        }
    }

    private function createAsset(array $data): void
    {
        try {
            BarangAset::create($data);
        } catch (\Exception $e) {
            Log::warning("Skip create: {$data['nama_aset']} - " . $e->getMessage());
            $this->stats['skipped']++;
        }
    }

    private function getOrCreateRuangan(string $namaRuangan): int
    {
        $namaRuangan = trim($namaRuangan);
        
        if (isset($this->ruanganMap[$namaRuangan])) {
            return $this->ruanganMap[$namaRuangan];
        }

        $cleanName = $this->cleanRoomName($namaRuangan);
        
        $ruangan = Ruangan::firstOrCreate(
            ['nama_ruangan' => $cleanName],
            [
                'lokasi' => 'Puskesmas Karang Rejo',
                'is_active' => true,
            ]
        );

        $this->ruanganMap[$namaRuangan] = $ruangan->id;
        return $ruangan->id;
    }

    private function cleanRoomName(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/\s+/', ' ', $name);
        $name = preg_replace('/^RUANG\s+/i', '', $name);
        return ucwords(strtolower($name));
    }

    private function getKategoriId(?string $kodeBarang): int
    {
        if (!$kodeBarang) {
            return $this->kategoriMap['02.99'] ?? KategoriAset::first()->id;
        }

        foreach ($this->kategoriMap as $prefix => $id) {
            if (str_starts_with($kodeBarang, $prefix)) {
                return $id;
            }
        }

        return $this->kategoriMap['02.99'] ?? KategoriAset::first()->id;
    }

    private function getCellValue($sheet, string $coordinate): ?string
    {
        try {
            $cell = $sheet->getCell($coordinate);
            $value = $cell->getValue();
            
            if ($value === null) return null;
            
            $value = trim((string)$value);
            
            // Skip Excel errors
            if (strpos($value, '#REF') !== false || strpos($value, '#VALUE') !== false) {
                return null;
            }
            
            return $value;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function cleanValue(?string $value): ?string
    {
        if (empty($value) || $value === '-') return null;
        return trim($value);
    }

    private function formatNamaAset(?string $nama): string
    {
        if (empty($nama)) return '';
        
        $nama = trim($nama);
        $nama = preg_replace('/\s+/', ' ', $nama);
        $nama = mb_convert_case(mb_strtolower($nama), MB_CASE_TITLE, 'UTF-8');
        
        // Fix common abbreviations
        $abbrevs = ['Ac', 'Pc', 'Cpu', 'Lcd', 'Led', 'Ups', 'Tv', 'Dvd', 'Vcd', 'Usb', 'Hp', 'Cctv'];
        $upper = ['AC', 'PC', 'CPU', 'LCD', 'LED', 'UPS', 'TV', 'DVD', 'VCD', 'USB', 'HP', 'CCTV'];
        $nama = str_replace($abbrevs, $upper, $nama);
        
        return $nama;
    }

    private function parseQuantity(?string $jumlah, ?string $keterangan): int
    {
        if (!empty($jumlah) && is_numeric($jumlah)) {
            return max(1, (int)$jumlah);
        }
        
        if (!empty($keterangan) && preg_match('/(\d+)\s*s\/d\s*(\d+)/i', $keterangan, $matches)) {
            return max(1, (int)$matches[2] - (int)$matches[1] + 1);
        }
        
        return 1;
    }

    private function parsePrice(?string $harga): float
    {
        if (empty($harga) || str_starts_with($harga, '=')) {
            return 0;
        }
        
        $cleaned = preg_replace('/[^0-9.]/', '', $harga);
        return (float)$cleaned;
    }

    private function parseYear(?string $tahun): ?int
    {
        if (empty($tahun)) return null;
        
        if (preg_match('/(\d{4})/', $tahun, $matches)) {
            $year = (int)$matches[1];
            if ($year >= 1990 && $year <= date('Y') + 1) {
                return $year;
            }
        }
        
        return null;
    }

    private function parseSumberDana(?string $keterangan): string
    {
        if (empty($keterangan)) return 'APBD';
        
        $keterangan = strtoupper($keterangan);
        
        if (stripos($keterangan, 'DKK') !== false || stripos($keterangan, 'DROPING') !== false) return 'Hibah';
        if (stripos($keterangan, 'HIBAH') !== false) return 'Hibah';
        if (stripos($keterangan, 'DAK') !== false) return 'DAK';
        if (stripos($keterangan, 'BOK') !== false) return 'BOK';
        if (stripos($keterangan, 'APBN') !== false) return 'APBN';
        
        return 'APBD';
    }

    private function parseAsalPerolehan(?string $keterangan): string
    {
        if (empty($keterangan)) return 'Pembelian';
        
        $keterangan = strtoupper($keterangan);
        
        if (stripos($keterangan, 'DKK') !== false || stripos($keterangan, 'DROPING') !== false) return 'Hibah dari Dinas Kesehatan';
        if (stripos($keterangan, 'HIBAH') !== false) return 'Hibah';
        if (stripos($keterangan, 'BANTUAN') !== false) return 'Bantuan';
        
        return 'Pembelian';
    }
}
