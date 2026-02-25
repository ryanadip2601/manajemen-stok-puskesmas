<?php

namespace App\Http\Controllers;

use App\Models\KategoriAset;
use App\Models\Log;
use Illuminate\Http\Request;

class KategoriAsetController extends Controller
{
    public function index(Request $request)
    {
        $kategori = KategoriAset::withCount('barangAset')
            ->when($request->search, function ($query, $search) {
                $query->where('nama_kategori', 'like', "%{$search}%");
            })
            ->orderBy('nama_kategori')
            ->paginate(15);

        return view('kategori-aset.index', compact('kategori'));
    }

    public function create()
    {
        return view('kategori-aset.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_kategori' => 'required|string|max:100|unique:kategori_aset,nama_kategori',
            'kode_golongan' => 'nullable|string|max:20',
            'golongan' => 'required|in:tanah,peralatan_mesin,gedung_bangunan,jalan_irigasi,aset_tetap_lainnya,kdp',
            'umur_manfaat' => 'required|integer|min:0',
            'deskripsi' => 'nullable|string',
        ], [
            'nama_kategori.required' => 'Nama kategori harus diisi',
            'nama_kategori.unique' => 'Nama kategori sudah ada',
            'golongan.required' => 'Golongan harus dipilih',
            'umur_manfaat.required' => 'Umur manfaat harus diisi',
            'umur_manfaat.min' => 'Umur manfaat tidak boleh negatif',
        ]);

        // Set dapat_disusutkan berdasarkan golongan
        $validated['dapat_disusutkan'] = !in_array($validated['golongan'], ['tanah', 'kdp']);

        $kategori = KategoriAset::create($validated);

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'kategori_aset_created',
            'description' => 'Kategori Aset "' . $kategori->nama_kategori . '" dibuat dengan golongan ' . $kategori->golongan . ' (umur manfaat: ' . $kategori->umur_manfaat . ' tahun)'
        ]);

        return redirect()->route('kategori-aset.index')
            ->with('success', 'Kategori aset berhasil ditambahkan');
    }

    public function edit($id)
    {
        $kategori = KategoriAset::withCount('barangAset')->findOrFail($id);
        return view('kategori-aset.edit', compact('kategori'));
    }

    public function update(Request $request, $id)
    {
        $kategori = KategoriAset::findOrFail($id);

        $validated = $request->validate([
            'nama_kategori' => 'required|string|max:100|unique:kategori_aset,nama_kategori,' . $id,
            'kode_golongan' => 'nullable|string|max:20',
            'golongan' => 'required|in:tanah,peralatan_mesin,gedung_bangunan,jalan_irigasi,aset_tetap_lainnya,kdp',
            'umur_manfaat' => 'required|integer|min:0',
            'deskripsi' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Handle checkbox
        $validated['is_active'] = $request->has('is_active');
        
        // Set dapat_disusutkan berdasarkan golongan
        $validated['dapat_disusutkan'] = !in_array($validated['golongan'], ['tanah', 'kdp']);

        $kategori->update($validated);

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'kategori_aset_updated',
            'description' => 'Kategori Aset "' . $kategori->nama_kategori . '" diupdate'
        ]);

        return redirect()->route('kategori-aset.index')
            ->with('success', 'Kategori aset berhasil diupdate');
    }

    public function destroy($id)
    {
        $kategori = KategoriAset::findOrFail($id);
        
        if ($kategori->barangAset()->count() > 0) {
            return back()->with('error', 'Kategori tidak dapat dihapus karena masih memiliki aset');
        }

        $namaKategori = $kategori->nama_kategori;
        $kategori->delete();

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'kategori_aset_deleted',
            'description' => 'Kategori Aset "' . $namaKategori . '" dihapus'
        ]);

        return redirect()->route('kategori-aset.index')
            ->with('success', 'Kategori aset berhasil dihapus');
    }
}
