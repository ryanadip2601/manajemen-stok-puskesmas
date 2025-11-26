<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Category;
use App\Models\Unit;
use App\Models\Log;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $items = Item::with(['category', 'unit'])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('code', 'ILIKE', "%{$search}%");
            })
            ->when($request->category_id, function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($request->low_stock, function ($query) {
                $query->lowStock();
            })
            ->latest()
            ->paginate(10);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $items
            ]);
        }

        $categories = Category::all();
        return view('items.index', compact('items', 'categories'));
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
            'stock' => 'nullable|integer|min:0',
            'minimum_stock' => 'required|integer|min:0',
        ], [
            'category_id.required' => 'Kategori harus dipilih',
            'unit_id.required' => 'Satuan harus dipilih',
            'code.required' => 'Kode barang harus diisi',
            'code.unique' => 'Kode barang sudah ada',
            'name.required' => 'Nama barang harus diisi',
            'minimum_stock.required' => 'Stok minimum harus diisi',
        ]);

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
            'minimum_stock' => 'required|integer|min:0',
        ], [
            'category_id.required' => 'Kategori harus dipilih',
            'unit_id.required' => 'Satuan harus dipilih',
            'code.required' => 'Kode barang harus diisi',
            'code.unique' => 'Kode barang sudah ada',
            'name.required' => 'Nama barang harus diisi',
            'minimum_stock.required' => 'Stok minimum harus diisi',
        ]);

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

        return redirect()->route('items.index')->with('success', 'Barang berhasil dihapus');
    }
}
