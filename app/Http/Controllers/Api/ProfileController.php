<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    // PUT /api/profile : ubah nama, nomor HP, kampus, area kos
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'campus' => 'nullable|string|max:255',
            'boarding_area' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'data' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        $user->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui',
            'data' => $user->fresh(),
        ], 200);
    }

    // POST /api/profile/photo : unggah foto profil
    public function updatePhoto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'data' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        // hapus foto lama kalau ada
        if ($user->photo && Storage::disk('public')->exists($user->photo)) {
            Storage::disk('public')->delete($user->photo);
        }

        $path = $request->file('photo')->store('profiles', 'public');

        $user->update(['photo' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Foto profil berhasil diunggah',
            'data' => [
                'photo_url' => asset('storage/' . $path),
                'user' => $user->fresh(),
            ],
        ], 200);
    }
}