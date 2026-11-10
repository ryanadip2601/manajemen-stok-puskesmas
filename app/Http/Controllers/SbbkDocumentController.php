<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\SbbkDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SbbkDocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = SbbkDocument::with('uploadedBy')->latest('tanggal_sbbk')->latest('id');

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('nomor_sbbk', 'like', "%{$search}%")
                    ->orWhere('unit_peminta', 'like', "%{$search}%")
                    ->orWhere('perihal', 'like', "%{$search}%");
            });
        }

        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal_sbbk', '>=', $request->input('tanggal_mulai'));
        }

        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal_sbbk', '<=', $request->input('tanggal_selesai'));
        }

        $documents = $query->paginate(12)->withQueryString();

        return view('sbbk.index', [
            'documents' => $documents,
            'filters' => [
                'search' => $request->input('search'),
                'tanggal_mulai' => $request->input('tanggal_mulai'),
                'tanggal_selesai' => $request->input('tanggal_selesai'),
            ],
        ]);
    }

    public function create()
    {
        return view('sbbk.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nomor_sbbk' => 'required|string|max:100|unique:sbbk_documents,nomor_sbbk',
            'tanggal_sbbk' => 'required|date',
            'unit_peminta' => 'required|string|max:150',
            'perihal' => 'nullable|string|max:255',
            'keterangan' => 'nullable|string|max:2000',
            'dokumen' => 'required|file|mimes:jpeg,jpg,png,pdf|max:5120',
        ], [
            'dokumen.required' => 'Dokumen SBBK wajib diunggah.',
            'dokumen.mimes' => 'Format dokumen harus jpeg, jpg, png, atau pdf.',
            'dokumen.max' => 'Ukuran dokumen maksimal 5MB.',
        ]);

        DB::beginTransaction();

        $savedPath = null;

        try {
            $file = $request->file('dokumen');
            $uploadPath = public_path('uploads/sbbk');

            if (! file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            $filename = now()->format('Ymd_His').'_'.uniqid().'.'.strtolower($file->getClientOriginalExtension());
            $file->move($uploadPath, $filename);
            $savedPath = 'uploads/sbbk/'.$filename;

            $document = SbbkDocument::create([
                'nomor_sbbk' => trim($validated['nomor_sbbk']),
                'tanggal_sbbk' => $validated['tanggal_sbbk'],
                'unit_peminta' => trim($validated['unit_peminta']),
                'perihal' => $validated['perihal'] ?? null,
                'keterangan' => $validated['keterangan'] ?? null,
                'file_path' => $savedPath,
                'file_name' => $file->getClientOriginalName(),
                'file_mime' => $file->getMimeType() ?? 'application/octet-stream',
                'file_size' => $file->getSize(),
                'uploaded_by' => auth()->id(),
            ]);

            Log::create([
                'user_id' => auth()->id(),
                'action' => 'sbbk_uploaded',
                'description' => 'Upload dokumen SBBK: '.$document->nomor_sbbk,
            ]);

            DB::commit();

            return redirect()->route('sbbk.index')->with('success', 'Dokumen SBBK berhasil diunggah.');
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($savedPath) {
                $fullPath = public_path($savedPath);
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }

            return back()->withInput()->with('error', 'Gagal mengunggah dokumen SBBK: '.$e->getMessage());
        }
    }

    public function show($id)
    {
        $document = SbbkDocument::with('uploadedBy')->findOrFail($id);

        return view('sbbk.show', compact('document'));
    }
}
