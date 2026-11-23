<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ItemController extends Controller
{
    // GET /api/items (publik)
    public function index(Request $request)
    {
        $request->validate([
            'q'           => 'nullable|string|max:100',
            'category_id' => 'nullable|integer',
            'condition'   => 'nullable|in:seperti_baru,baik,layak_pakai',
            'min_price'   => 'nullable|integer|min:0',
            'max_price'   => 'nullable|integer|min:0',
            'sort'        => 'nullable|in:termurah,terbaru',
            'per_page'    => 'nullable|integer|min:1|max:50',
        ]);

        $query = Item::with(['images', 'category', 'seller:id,name,campus,boarding_area'])
            ->where('status', 'tersedia');

        if ($request->filled('q')) {
            $keyword = $request->q;
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('condition')) {
            $query->where('condition', $request->condition);
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        if ($request->sort === 'termurah') {
            $query->orderBy('price', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $items = $query->paginate($request->input('per_page', 10))
                       ->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Daftar barang',
            'data'    => $items,
        ]);
    }

    // GET /api/items/{id} (publik)
    public function show($id)
    {
        $item = Item::with(['images', 'category', 'seller:id,name,phone,campus,boarding_area,photo'])
            ->find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Barang tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail barang',
            'data'    => $item,
        ]);
    }

    // GET /api/my-items (wajib login)
    public function myItems(Request $request)
    {
        $items = Item::with(['images', 'category'])
            ->where('seller_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Barang saya',
            'data'    => $items,
        ]);
    }

        // POST /api/items (wajib login, form-data)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|integer|exists:categories,id',
            'title'       => 'required|string|max:150',
            'description' => 'required|string',
            'price'       => 'required|integer|min:0',
            'condition'   => 'required|in:seperti_baru,baik,layak_pakai',
            'area'        => 'nullable|string|max:255',
            'latitude'    => 'nullable|numeric|between:-90,90',
            'longitude'   => 'nullable|numeric|between:-180,180',
            'images'      => 'required|array|min:1|max:5',
            'images.*'    => 'image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $item = DB::transaction(function () use ($request, $validated) {
            $item = Item::create([
                'seller_id'   => $request->user()->id,
                'category_id' => $validated['category_id'],
                'title'       => $validated['title'],
                'description' => $validated['description'],
                'price'       => $validated['price'],
                'condition'   => $validated['condition'],
                'status'      => 'tersedia',
                'area'        => $validated['area'] ?? null,
                'latitude'    => $validated['latitude'] ?? null,
                'longitude'   => $validated['longitude'] ?? null,
            ]);

            foreach ($request->file('images') as $file) {
                $path = $file->store('items', 'public');
                $item->images()->create(['path' => $path]);
            }

            return $item;
        });

        $item->load(['images', 'category', 'seller:id,name,campus,boarding_area']);

        return response()->json([
            'success' => true,
            'message' => 'Barang berhasil ditambahkan',
            'data'    => $item,
        ], 201);
    }

        // PUT /api/items/{id} (wajib login, hanya pemilik)
    public function update(Request $request, $id)
    {
        $item = Item::find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Barang tidak ditemukan',
            ], 404);
        }

        if ((int) $item->seller_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak berhak mengubah barang ini',
            ], 403);
        }

        $validated = $request->validate([
            'category_id' => 'sometimes|required|integer|exists:categories,id',
            'title'       => 'sometimes|required|string|max:150',
            'description' => 'sometimes|required|string',
            'price'       => 'sometimes|required|integer|min:0',
            'condition'   => 'sometimes|required|in:seperti_baru,baik,layak_pakai',
            'status'      => 'sometimes|required|in:tersedia,dipesan,terjual',
            'area'        => 'nullable|string|max:255',
            'latitude'    => 'nullable|numeric|between:-90,90',
            'longitude'   => 'nullable|numeric|between:-180,180',
        ]);

        $item->update($validated);
        $item->load(['images', 'category', 'seller:id,name,campus,boarding_area']);

        return response()->json([
            'success' => true,
            'message' => 'Barang berhasil diperbarui',
            'data'    => $item,
        ]);
    }

    // DELETE /api/items/{id} (wajib login, hanya pemilik)
    public function destroy(Request $request, $id)
    {
        $item = Item::with('images')->find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Barang tidak ditemukan',
            ], 404);
        }

        if ((int) $item->seller_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak berhak menghapus barang ini',
            ], 403);
        }

        // Hapus file foto dari storage, lalu hapus barang
        Storage::disk('public')->delete($item->images->pluck('path')->all());
        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Barang berhasil dihapus',
        ]);
    }
    }