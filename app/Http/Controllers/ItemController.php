<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Category;
use App\Models\Unit;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        // Simpan filter ke session jika ada parameter baru
        if ($request->has('search') || $request->has('perolehan') || $request->has('category_id') || $request->has('low_stock')) {
            session([
                'items_filter' => [
                    'search' => $request->search,
                    'perolehan' => $request->perolehan,
                    'category_id' => $request->category_id,
                    'low_stock' => $request->low_stock,
                ]
            ]);
        }

        // Ambil filter dari session
        $filters = session('items_filter', []);
        $search = $request->search ?? ($filters['search'] ?? null);
        $perolehan = $request->perolehan ?? ($filters['perolehan'] ?? null);
        $category_id = $request->category_id ?? ($filters['category_id'] ?? null);
        $low_stock = $request->low_stock ?? ($filters['low_stock'] ?? null);

        $items = Item::with(['category', 'unit'])
            ->when($search, function ($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('code', 'LIKE', "%{$search}%");
                });
            })
            ->when($perolehan, function ($query, $perolehan) {
                $query->where('perolehan', $perolehan);
            })
            ->when($category_id, function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($low_stock, function ($query) {
                $query->lowStock();
            })
            ->orderBy('name')
            ->paginate(15);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $items
            ]);
        }

        $categories = Category::all();
        $activeFilters = compact('search', 'perolehan', 'category_id', 'low_stock');
        return view('items.index', compact('items', 'categories', 'activeFilters'));
    }

    public function clearFilter()
    {
        session()->forget('items_filter');
        return redirect()->route('items.index');
    }

    public function show($id)
    {
        $item = Item::with(['category', 'unit', 'stockIns.user', 'stockOuts.user'])->findOrFail($id);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $item
            ]);
        }

        return view('items.show', compact('item'));
    }

    public function create()
    {
        $categories = Category::all();
        $units = Unit::all();
        return view('items.create', compact('categories', 'units'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'unit_id' => 'required|exists:units,id',
            'code' => 'required|string|max:50|unique:items,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'perolehan' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'minimum_stock' => 'required|integer|min:0',
            'foto_tipe' => 'nullable|in:upload,url',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'foto_url' => 'nullable|url|max:2000',
        ], [
            'category_id.required' => 'Jenis barang harus dipilih',
            'unit_id.required' => 'Satuan harus dipilih',
            'code.required' => 'Kode barang harus diisi',
            'code.unique' => 'Kode barang sudah ada',
            'name.required' => 'Nama barang harus diisi',
            'perolehan.required' => 'Perolehan harus dipilih',
            'price.required' => 'Harga satuan harus diisi',
            'minimum_stock.required' => 'Stok minimum harus diisi',
            'photo.image' => 'File harus berupa gambar',
            'photo.mimes' => 'Format gambar harus jpeg, png, jpg, atau webp',
            'photo.max' => 'Ukuran gambar maksimal 5MB',
            'foto_url.url' => 'URL gambar tidak valid',
        ]);

        if ($request->foto_tipe === 'url' && $request->filled('foto_url')) {
            $validated['photo'] = $this->normalizePhotoUrl($request->foto_url);
        } elseif ($request->foto_tipe === 'upload' && $request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('items', $filename, 'public');
            $validated['photo'] = $path;
        }

        // Hapus field temporary yang tidak ada di DB
        unset($validated['foto_tipe'], $validated['foto_url']);

        $item = Item::create($validated);

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'item_created',
            'description' => 'Barang "' . $item->name . '" dibuat'
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Barang berhasil dibuat',
                'data' => $item->load(['category', 'unit'])
            ], 201);
        }

        return redirect()->route('items.index')->with('success', 'Barang berhasil dibuat');
    }

    public function edit($id)
    {
        $item = Item::findOrFail($id);
        $categories = Category::all();
        $units = Unit::all();
        return view('items.edit', compact('item', 'categories', 'units'));
    }

    public function update(Request $request, $id)
    {
        $item = Item::findOrFail($id);

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'unit_id' => 'required|exists:units,id',
            'code' => 'required|string|max:50|unique:items,code,' . $id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'perolehan' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'minimum_stock' => 'required|integer|min:0',
            'foto_tipe' => 'nullable|in:upload,url',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'foto_url' => 'nullable|url|max:2000',
            'delete_photo' => 'nullable|boolean',
        ], [
            'category_id.required' => 'Jenis barang harus dipilih',
            'unit_id.required' => 'Satuan harus dipilih',
            'code.required' => 'Kode barang harus diisi',
            'code.unique' => 'Kode barang sudah ada',
            'name.required' => 'Nama barang harus diisi',
            'perolehan.required' => 'Perolehan harus dipilih',
            'price.required' => 'Harga satuan harus diisi',
            'minimum_stock.required' => 'Stok minimum harus diisi',
            'photo.image' => 'File harus berupa gambar',
            'photo.mimes' => 'Format gambar harus jpeg, png, jpg, atau webp',
            'photo.max' => 'Ukuran gambar maksimal 5MB',
            'foto_url.url' => 'URL gambar tidak valid',
        ]);

        if ($request->has('delete_photo') && $request->delete_photo == 1) {
            // Cek apakah photo adalah file lokal (bukan URL) sebelum menghapus dari storage
            if ($item->photo && !str_starts_with($item->photo, 'http') && Storage::disk('public')->exists($item->photo)) {
                Storage::disk('public')->delete($item->photo);
            }
            $validated['photo'] = null;
        } else {
            if ($request->foto_tipe === 'url' && $request->filled('foto_url')) {
                // Hapus foto lokal lama jika ada
                if ($item->photo && !str_starts_with($item->photo, 'http') && Storage::disk('public')->exists($item->photo)) {
                    Storage::disk('public')->delete($item->photo);
                }
                $validated['photo'] = $this->normalizePhotoUrl($request->foto_url);
            } elseif ($request->foto_tipe === 'upload' && $request->hasFile('photo')) {
                // Hapus foto lokal lama jika ada
                if ($item->photo && !str_starts_with($item->photo, 'http') && Storage::disk('public')->exists($item->photo)) {
                    Storage::disk('public')->delete($item->photo);
                }
                
                $file = $request->file('photo');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('items', $filename, 'public');
                $validated['photo'] = $path;
            }
        }

        // Hapus field temporary yang tidak ada di DB
        unset($validated['foto_tipe'], $validated['foto_url'], $validated['delete_photo']);

        $item->update($validated);

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'item_updated',
            'description' => 'Barang "' . $item->name . '" diupdate'
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Barang berhasil diupdate',
                'data' => $item->load(['category', 'unit'])
            ]);
        }

        return redirect()->route('items.index')->with('success', 'Barang berhasil diupdate');
    }

    public function destroy(Request $request, $id)
    {
        $item = Item::findOrFail($id);
        $itemName = $item->name;
        $item->delete();

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'item_deleted',
            'description' => 'Barang "' . $itemName . '" dihapus'
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Barang berhasil dihapus'
            ]);
        }

        // Ambil parameter untuk mempertahankan state pagination & filter
        $params = $request->only(['page', 'search', 'category_id', 'perolehan', 'low_stock']);

        return redirect()->route('items.index', $params)->with('success', 'Barang berhasil dihapus');
    }

    public function scanBarcode()
    {
        return view('items.scan');
    }

    public function searchByBarcode(Request $request)
    {
        $request->validate([
            'barcode' => 'required|string'
        ]);

        $item = Item::with(['category', 'unit'])
            ->where('barcode', $request->barcode)
            ->orWhere('code', $request->barcode)
            ->first();

        if ($request->wantsJson()) {
            if ($item) {
                return response()->json([
                    'success' => true,
                    'data' => $item
                ]);
            }
            return response()->json([
                'success' => false,
                'message' => 'Barang tidak ditemukan'
            ], 404);
        }

        if ($item) {
            return redirect()->route('items.show', $item->id);
        }

        return back()->with('error', 'Barang dengan barcode tersebut tidak ditemukan');
    }

    public function showImport()
    {
        return view('items.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:10240',
        ], [
            'file.required' => 'File Excel harus diupload',
            'file.mimes' => 'File harus berformat .xlsx atau .xls',
            'file.max' => 'Ukuran file maksimal 10MB',
        ]);

        try {
            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Mapping perolehan dari Excel ke sistem
            $perolehanMap = [
                'BLUD' => 'BLUD',
                'APBD BALIKPAPAN' => 'APBD Balikpapan',
                'APBD' => 'APBD Balikpapan',
            ];

            $imported = 0;
            $skipped = 0;
            $errors = [];

            // Skip header row (row 0 and 1)
            for ($i = 2; $i < count($rows); $i++) {
                $row = $rows[$i];

                // Kolom Excel:
                // [8] = Spesifikasi Barang (Nama Barang)
                // [9] = Spesifikasi Nama Barang (bisa untuk deskripsi)
                // [7] = KATEGORI PERSEDIAAN
                // [10] = Jumlah
                // [11] = Satuan barang
                // [12] = Harga Satuan
                // [14] = Keterangan Sumber Dana (Perolehan)

                $namaBarang = trim($row[8] ?? '');
                $spesifikasi = trim($row[9] ?? '');
                $kategori = trim($row[7] ?? '');
                $jumlah = floatval($row[10] ?? 0);
                $satuan = trim($row[11] ?? '');
                $harga = floatval($row[12] ?? 0);
                $perolehan = trim($row[14] ?? '');

                // Skip jika nama barang kosong atau header
                if (empty($namaBarang) || $namaBarang === 'Nama Barang' || $namaBarang === 'Spesifikasi Barang') {
                    continue;
                }

                // Cari atau buat kategori
                $categoryModel = null;
                if (!empty($kategori)) {
                    $categoryModel = Category::firstOrCreate(
                        ['name' => $kategori],
                        ['description' => 'Kategori ' . $kategori]
                    );
                }

                // Cari atau buat unit/satuan
                $unitModel = null;
                if (!empty($satuan)) {
                    $satuanNormalized = ucfirst(strtolower($satuan));
                    $unitModel = Unit::firstOrCreate(
                        ['name' => $satuanNormalized],
                        ['symbol' => strtolower($satuan)]
                    );
                }

                // Map perolehan
                $perolehanMapped = $perolehanMap[strtoupper($perolehan)] ?? $perolehan;
                if (empty($perolehanMapped)) {
                    $perolehanMapped = 'BLUD'; // Default
                }

                // Generate kode barang
                $kodePrefix = 'BRG';
                $lastItem = Item::where('code', 'LIKE', $kodePrefix . '%')
                    ->orderBy('code', 'desc')
                    ->first();
                
                if ($lastItem) {
                    $lastNumber = intval(substr($lastItem->code, strlen($kodePrefix)));
                    $newNumber = $lastNumber + 1;
                } else {
                    $newNumber = 1;
                }
                $kodeBarang = $kodePrefix . str_pad($newNumber, 5, '0', STR_PAD_LEFT);

                // Cek apakah barang sudah ada (berdasarkan nama yang sama)
                $existingItem = Item::where('name', $namaBarang)
                    ->where('category_id', $categoryModel?->id)
                    ->first();

                if ($existingItem) {
                    // Update stok jika sudah ada
                    $existingItem->stock += $jumlah;
                    $existingItem->save();
                    $skipped++;
                } else {
                    // Buat barang baru
                    Item::create([
                        'code' => $kodeBarang,
                        'name' => $namaBarang,
                        'description' => $spesifikasi,
                        'category_id' => $categoryModel?->id,
                        'unit_id' => $unitModel?->id,
                        'stock' => $jumlah,
                        'minimum_stock' => 5,
                        'price' => $harga,
                        'perolehan' => $perolehanMapped,
                    ]);
                    $imported++;
                }
            }

            Log::create([
                'user_id' => auth()->id(),
                'action' => 'items_imported',
                'description' => "Import Excel: {$imported} barang baru, {$skipped} barang diupdate stoknya"
            ]);

            return redirect()->route('items.index')
                ->with('success', "Import berhasil! {$imported} barang baru ditambahkan, {$skipped} barang sudah ada (stok diupdate).");

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengimpor file: ' . $e->getMessage());
        }
    }

    private function normalizePhotoUrl(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        $cleanUrl = trim($url);
        $parts = parse_url($cleanUrl);

        if (!is_array($parts)) {
            return $cleanUrl;
        }

        parse_str($parts['query'] ?? '', $query);

        if (!empty($query['imgurl']) && filter_var($query['imgurl'], FILTER_VALIDATE_URL)) {
            return $query['imgurl'];
        }

        if (!empty($query['mediaurl']) && filter_var($query['mediaurl'], FILTER_VALIDATE_URL)) {
            return $query['mediaurl'];
        }

        if (!empty($query['url']) && filter_var($query['url'], FILTER_VALIDATE_URL)) {
            return $query['url'];
        }

        return $cleanUrl;
    }
}
