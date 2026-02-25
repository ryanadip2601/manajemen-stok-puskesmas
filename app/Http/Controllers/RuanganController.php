<?php

namespace App\Http\Controllers;

use App\Models\Ruangan;
use App\Models\Log;
use Illuminate\Http\Request;

class RuanganController extends Controller
{
    public function index(Request $request)
    {
        $ruangan = Ruangan::withCount('barangAset')
            ->when($request->search, function ($query, $search) {
                $query->where('nama_ruangan', 'like', "%{$search}%")
                      ->orWhere('lokasi', 'like', "%{$search}%");
            })
            ->when($request->status, function ($query, $status) {
                $query->where('is_active', $status === 'active');
            })
            ->orderBy('nama_ruangan')
            ->paginate(15);

        return view('ruangan.index', compact('ruangan'));
    }

    public function create()
    {
        return view('ruangan.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_ruangan' => 'required|string|max:100|unique:ruangan,nama_ruangan',
            'lokasi' => 'nullable|string|max:255',
            'keterangan' => 'nullable|string',
        ], [
            'nama_ruangan.required' => 'Nama ruangan harus diisi',
            'nama_ruangan.unique' => 'Nama ruangan sudah ada',
        ]);

        $ruangan = Ruangan::create($validated);

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'ruangan_created',
            'description' => 'Ruangan "' . $ruangan->nama_ruangan . '" dibuat'
        ]);

        return redirect()->route('ruangan.index')
            ->with('success', 'Ruangan berhasil ditambahkan');
    }

    public function show($id)
    {
        $ruangan = Ruangan::with(['barangAset.kategoriAset', 'pemeliharaan'])
            ->withCount('barangAset')
            ->findOrFail($id);

        $asetByKondisi = $ruangan->barangAset->groupBy('kondisi')->map->count();

        return view('ruangan.show', compact('ruangan', 'asetByKondisi'));
    }

    public function edit($id)
    {
        $ruangan = Ruangan::findOrFail($id);
        return view('ruangan.edit', compact('ruangan'));
    }

    public function update(Request $request, $id)
    {
        $ruangan = Ruangan::findOrFail($id);

        $validated = $request->validate([
            'nama_ruangan' => 'required|string|max:100|unique:ruangan,nama_ruangan,' . $id,
            'lokasi' => 'nullable|string|max:255',
            'keterangan' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $ruangan->update($validated);

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'ruangan_updated',
            'description' => 'Ruangan "' . $ruangan->nama_ruangan . '" diupdate'
        ]);

        return redirect()->route('ruangan.index')
            ->with('success', 'Ruangan berhasil diupdate');
    }

    public function destroy($id)
    {
        $ruangan = Ruangan::findOrFail($id);
        
        if ($ruangan->barangAset()->count() > 0) {
            return back()->with('error', 'Ruangan tidak dapat dihapus karena masih memiliki aset');
        }

        $namaRuangan = $ruangan->nama_ruangan;
        $ruangan->delete();

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'ruangan_deleted',
            'description' => 'Ruangan "' . $namaRuangan . '" dihapus'
        ]);

        return redirect()->route('ruangan.index')
            ->with('success', 'Ruangan berhasil dihapus');
    }
}
