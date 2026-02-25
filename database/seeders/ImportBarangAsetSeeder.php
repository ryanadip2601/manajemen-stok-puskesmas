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

class ImportBarangAsetSeeder extends Seeder
{
    private $dataPath = 'C:/Users/Ryana/Downloads/BARANG ASSET';
    private $kategoriMap = [];
    private $ruanganMap = [];
    private $importedCount = 0;
    private $skippedCount = 0;

    public function run(): void
    {
        $this->command->info('Memulai import data Barang Aset dari file Excel...');
        
        DB::beginTransaction();
        
        try {
            // Create default categories based on kode barang patterns
            $this->createDefaultKategori();
            
            // Import from file02.xls (main inventory data with multiple rooms)
            $this->importFile02();
            
            // Import from file01.xlsx
            $this->importFile01();
            
            DB::commit();
            
            $this->command->info("========================================");
            $this->command->info("Import selesai!");
            $this->command->info("Total barang terimport: {$this->importedCount}");
            $this->command->info("Total dilewati: {$this->skippedCount}");
            $this->command->info("Total Ruangan: " . Ruangan::count());
            $this->command->info("Total Kategori: " . KategoriAset::count());
            $this->command->info("========================================");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error: ' . $e->getMessage());
            Log::error('Import Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        }
    }

    private function createDefaultKategori(): void
    {
        $categories = [
            ['nama' => 'Alat Kesehatan', 'kode' => '02.03', 'golongan' => 'peralatan_mesin', 'umur' => 5],
            ['nama' => 'Mebel/Furnitur', 'kode' => '02.04', 'golongan' => 'peralatan_mesin', 'umur' => 10],
            ['nama' => 'Rak & Penyimpanan', 'kode' => '02.05', 'golongan' => 'peralatan_mesin', 'umur' => 10],
            ['nama' => 'Peralatan Kantor', 'kode' => '02.06', 'golongan' => 'peralatan_mesin', 'umur' => 5],
            ['nama' => 'Komputer & IT', 'kode' => '02.07', 'golongan' => 'peralatan_mesin', 'umur' => 4],
            ['nama' => 'Elektronik', 'kode' => '02.08', 'golongan' => 'peralatan_mesin', 'umur' => 5],
            ['nama' => 'Kendaraan', 'kode' => '02.01', 'golongan' => 'peralatan_mesin', 'umur' => 8],
            ['nama' => 'Gedung & Bangunan', 'kode' => '03.01', 'golongan' => 'gedung_bangunan', 'umur' => 50],
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
        
        $this->command->info('Kategori aset dibuat: ' . count($categories));
    }

    private function getKategoriId(?string $kodeBarang): int
    {
        if (!$kodeBarang) {
            return $this->kategoriMap['02.99'] ?? KategoriAset::first()->id;
        }

        // Match prefix pattern like "02.03", "02.04", etc.
        foreach ($this->kategoriMap as $prefix => $id) {
            if (str_starts_with($kodeBarang, $prefix)) {
                return $id;
            }
        }

        return $this->kategoriMap['02.99'] ?? KategoriAset::first()->id;
    }

    private function getOrCreateRuangan(string $namaRuangan): int
    {
        $namaRuangan = trim($namaRuangan);
        
        if (isset($this->ruanganMap[$namaRuangan])) {
            return $this->ruanganMap[$namaRuangan];
        }

        // Clean room name
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
        // Remove "RUANG " prefix if exists
        $name = preg_replace('/^RUANG\s+/i', '', $name);
        $name = ucwords(strtolower($name));
        return $name;
    }

    private function importFile02(): void
    {
        $filePath = $this->dataPath . '/file02.xls';
        
        if (!file_exists($filePath)) {
            $this->command->warn("File not found: $filePath");
            return;
        }
        
        $this->command->info("Importing: $filePath");
        
        $spreadsheet = IOFactory::load($filePath);
        $sheetNames = $spreadsheet->getSheetNames();
        
        foreach ($sheetNames as $index => $sheetName) {
            if (empty(trim($sheetName)) || $sheetName === 'Sheet1' || $sheetName === 'Sheet2') {
                continue;
            }
            
            $this->command->info("  Processing sheet: $sheetName");
            $sheet = $spreadsheet->getSheet($index);
            $this->processKIRSheet($sheet, $sheetName);
        }
    }

    private function importFile01(): void
    {
        $filePath = $this->dataPath . '/file01.xlsx';
        
        if (!file_exists($filePath)) {
            $this->command->warn("File not found: $filePath");
            return;
        }
        
        $this->command->info("Importing: $filePath");
        
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheet(0);
        
        // Try to get room name from cell D8
        $ruanganName = $this->getCellValue($sheet, 'D8');
        if (empty($ruanganName)) {
            $ruanganName = 'Ruang Umum';
        }
        $this->processKIRSheet($sheet, $ruanganName);
    }

    private function processKIRSheet($sheet, string $ruanganName): void
    {
        $ruanganId = $this->getOrCreateRuangan($ruanganName);
        $highestRow = $sheet->getHighestRow();
        
        // Find the data start row (after header)
        $dataStartRow = $this->findDataStartRow($sheet);
        
        if ($dataStartRow === 0) {
            $this->command->warn("    No data rows found in sheet: $ruanganName");
            return;
        }

        $sheetImported = 0;
        
        for ($row = $dataStartRow; $row <= $highestRow; $row++) {
            $noUrut = $this->getCellValue($sheet, "A$row");
            $namaBarang = $this->getCellValue($sheet, "B$row");
            
            // Skip if no item name or if it's a summary row
            if (empty($namaBarang) || 
                stripos($namaBarang, 'JUMLAH') !== false || 
                stripos($namaBarang, 'MENGETAHUI') !== false ||
                stripos($namaBarang, 'KEPALA') !== false ||
                stripos($namaBarang, 'PENGURUS') !== false ||
                !is_numeric($noUrut)) {
                continue;
            }

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
            if (!empty($kondisiRB) && strtoupper(trim($kondisiRB)) !== '-' && !empty(trim($kondisiRB))) {
                $kondisi = 'rusak_berat';
            } elseif (!empty($kondisiKB) && strtoupper(trim($kondisiKB)) !== '-' && !empty(trim($kondisiKB))) {
                $kondisi = 'rusak_ringan';
            }

            // Parse quantity
            $qty = $this->parseQuantity($jumlah, $keterangan);
            
            // Parse price
            $hargaValue = $this->parsePrice($harga);
            
            // Parse year
            $tahunPerolehan = $this->parseYear($tahun);

            // Build spesifikasi from ukuran and bahan
            $spesifikasi = trim(implode(', ', array_filter([$ukuran, $bahan]))) ?: null;

            // Create the asset entry
            $this->createBarangAset([
                'ruangan_id' => $ruanganId,
                'kategori_aset_id' => $this->getKategoriId($kodeBarang),
                'nama_aset' => trim($namaBarang),
                'merk' => $merkModel ?: null,
                'no_sertifikat' => $noSeri ?: null,
                'ukuran_konstruksi' => $spesifikasi,
                'spesifikasi' => $spesifikasi,
                'tahun_perolehan' => $tahunPerolehan,
                'tanggal_masuk' => $tahunPerolehan ? Carbon::createFromDate($tahunPerolehan, 1, 1) : Carbon::now(),
                'jumlah' => $qty,
                'harga_perolehan' => $hargaValue,
                'kondisi' => $kondisi,
                'keterangan' => $keterangan,
                'sumber_dana' => $this->parseSumberDana($keterangan),
                'asal_perolehan' => $this->parseAsalPerolehan($keterangan),
            ]);
            
            $sheetImported++;
        }
        
        $this->command->info("    Imported $sheetImported items from: $ruanganName");
    }

    private function findDataStartRow($sheet): int
    {
        $highestRow = min($sheet->getHighestRow(), 30);
        
        for ($row = 1; $row <= $highestRow; $row++) {
            $colA = $this->getCellValue($sheet, "A$row");
            $colB = $this->getCellValue($sheet, "B$row");
            
            // Look for first numeric row after headers
            if (is_numeric($colA) && $colA == 1 && !empty($colB) && 
                stripos($colB, 'Nama Barang') === false && 
                stripos($colB, 'Jenis barang') === false &&
                $colB !== '2') {
                return $row;
            }
        }
        
        return 0;
    }

    private function getCellValue($sheet, string $coordinate): ?string
    {
        try {
            $cell = $sheet->getCell($coordinate);
            $value = $cell->getValue();
            
            if ($value === null) {
                return null;
            }
            
            return trim((string)$value);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parseQuantity(?string $jumlah, ?string $keterangan): int
    {
        if (!empty($jumlah) && is_numeric($jumlah)) {
            return max(1, (int)$jumlah);
        }
        
        // Try to parse from keterangan like "000001 s/d 000005" = 5 items
        if (!empty($keterangan) && preg_match('/(\d+)\s*s\/d\s*(\d+)/i', $keterangan, $matches)) {
            return max(1, (int)$matches[2] - (int)$matches[1] + 1);
        }
        
        return 1;
    }

    private function parsePrice(?string $harga): float
    {
        if (empty($harga)) {
            return 0;
        }
        
        // Handle formula
        if (str_starts_with($harga, '=')) {
            return 0;
        }
        
        // Remove non-numeric characters except decimal point
        $cleaned = preg_replace('/[^0-9.]/', '', $harga);
        return (float)$cleaned;
    }

    private function parseYear(?string $tahun): ?int
    {
        if (empty($tahun)) {
            return null;
        }
        
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
        if (empty($keterangan)) {
            return 'APBD';
        }
        
        $keterangan = strtoupper($keterangan);
        
        if (stripos($keterangan, 'DKK') !== false || stripos($keterangan, 'DROPING') !== false) {
            return 'Hibah';
        }
        if (stripos($keterangan, 'HIBAH') !== false) {
            return 'Hibah';
        }
        if (stripos($keterangan, 'DAK') !== false) {
            return 'DAK';
        }
        if (stripos($keterangan, 'BOK') !== false) {
            return 'BOK';
        }
        if (stripos($keterangan, 'APBN') !== false) {
            return 'APBN';
        }
        
        return 'APBD';
    }

    private function parseAsalPerolehan(?string $keterangan): string
    {
        if (empty($keterangan)) {
            return 'Pembelian';
        }
        
        $keterangan = strtoupper($keterangan);
        
        if (stripos($keterangan, 'DKK') !== false || stripos($keterangan, 'DROPING') !== false) {
            return 'Hibah dari Dinas Kesehatan';
        }
        if (stripos($keterangan, 'HIBAH') !== false) {
            return 'Hibah';
        }
        if (stripos($keterangan, 'BANTUAN') !== false) {
            return 'Bantuan';
        }
        
        return 'Pembelian';
    }

    private function createBarangAset(array $data): void
    {
        try {
            BarangAset::create([
                'nama_aset' => $data['nama_aset'],
                'kategori_aset_id' => $data['kategori_aset_id'],
                'ruangan_id' => $data['ruangan_id'],
                'kondisi' => $data['kondisi'],
                'tanggal_masuk' => $data['tanggal_masuk'],
                'tahun_perolehan' => $data['tahun_perolehan'],
                'sumber_dana' => $data['sumber_dana'],
                'asal_perolehan' => $data['asal_perolehan'],
                'ukuran_konstruksi' => $data['ukuran_konstruksi'],
                'no_sertifikat' => $data['no_sertifikat'],
                'jumlah' => $data['jumlah'],
                'harga_perolehan' => $data['harga_perolehan'],
                'merk' => $data['merk'],
                'spesifikasi' => $data['spesifikasi'],
                'keterangan' => $data['keterangan'],
                'status_aset' => 'aktif',
            ]);
            
            $this->importedCount++;
            
        } catch (\Exception $e) {
            $this->skippedCount++;
            Log::warning("Skipped item: {$data['nama_aset']} - " . $e->getMessage());
        }
    }
}
