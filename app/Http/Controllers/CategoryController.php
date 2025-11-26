<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Log;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::withCount('items')
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'ILIKE', "%{$search}%");
            })
            ->latest()
            ->paginate(10);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $categories
            ]);
        }

        return view('categories.index', compact('categories'));
    }

    public function show($id)
    {
        $category = Category::withCount('items')->findOrFail($id);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $category
            ]);
        }

        return view('categories.show', compact('category'));
    }

    public function create()
    {
        return view('categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name',
            'description' => 'nullable|string',
        ], [
            'name.required' => 'Nama kategori harus diisi',
            'name.unique' => 'Nama kategori sudah ada',
            'name.max' => 'Nama kategori maksimal 100 karakter',
        ]);

        $category = Category::create($validated);

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'category_created',
            'description' => 'Kategori "' . $category->name . '" dibuat'
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Kategori berhasil dibuat',
                'data' => $category
            ], 201);
        }

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil dibuat');
    }

    public function edit($id)
    {
        $category = Category::findOrFail($id);
        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name,' . $id,
            'description' => 'nullable|string',
        ], [
            'name.required' => 'Nama kategori harus diisi',
            'name.unique' => 'Nama kategori sudah ada',
            'name.max' => 'Nama kategori maksimal 100 karakter',
        ]);

        $category->update($validated);

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'category_updated',
            'description' => 'Kategori "' . $category->name . '" diupdate'
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Kategori berhasil diupdate',
                'data' => $category
            ]);
        }

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil diupdate');
    }

    public function destroy(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        if ($category->items()->count() > 0) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kategori tidak dapat dihapus karena masih memiliki barang'
                ], 400);
            }
            return back()->with('error', 'Kategori tidak dapat dihapus karena masih memiliki barang');
        }

        $categoryName = $category->name;
        $category->delete();

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'category_deleted',
            'description' => 'Kategori "' . $categoryName . '" dihapus'
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Kategori berhasil dihapus'
            ]);
        }

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil dihapus');
    }
}
