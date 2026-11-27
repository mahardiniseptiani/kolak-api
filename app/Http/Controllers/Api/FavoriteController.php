<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    // GET /api/favorites : daftar barang favorit milik user
    public function index(Request $request)
    {
        $items = $request->user()
            ->favorites()
            ->with(['category', 'images', 'seller:id,name,campus,boarding_area'])
            ->orderByPivot('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar favorit berhasil diambil',
            'data' => $items,
        ], 200);
    }

    // POST /api/items/{id}/favorite : tambah ke favorit
    public function store(Request $request, $id)
    {
        $item = Item::find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Barang tidak ditemukan',
                'data' => null,
            ], 404);
        }

        $user = $request->user();

        $sudahFavorit = $user->favorites()->where('items.id', $item->id)->exists();

        if ($sudahFavorit) {
            return response()->json([
                'success' => false,
                'message' => 'Barang sudah ada di favorit',
                'data' => null,
            ], 409);
        }

        $user->favorites()->attach($item->id);

        return response()->json([
            'success' => true,
            'message' => 'Barang ditambahkan ke favorit',
            'data' => null,
        ], 201);
    }

    // DELETE /api/items/{id}/favorite : hapus dari favorit
    public function destroy(Request $request, $id)
    {
        $item = Item::find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Barang tidak ditemukan',
                'data' => null,
            ], 404);
        }

        $user = $request->user();

        $sudahFavorit = $user->favorites()->where('items.id', $item->id)->exists();

        if (!$sudahFavorit) {
            return response()->json([
                'success' => false,
                'message' => 'Barang tidak ada di daftar favorit',
                'data' => null,
            ], 404);
        }

        $user->favorites()->detach($item->id);

        return response()->json([
            'success' => true,
            'message' => 'Barang dihapus dari favorit',
            'data' => null,
        ], 200);
    }
}