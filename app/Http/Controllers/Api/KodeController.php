<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kode;
use Illuminate\Http\Request;

class KodeController extends Controller
{
    public function index()
    {
        $kodes = Kode::with('items')->get();

        return response()->json($kodes->map(function ($kode) {
            return [
                'id' => $kode->id,
                'kode' => $kode->kode,
                'jumlah' => $kode->jumlah, // otomatis dari accessor
                'jumlah_rusak' => $kode->jumlah_rusak,
                'jumlah_dipinjam' => $kode->jumlah_dipinjam,
                'created_at' => $kode->created_at,
                'updated_at' => $kode->updated_at,
            ];
        }));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => 'required|string|unique:kodes,kode',
        ]);

        $kode = Kode::create($validated);

        return response()->json([
            'message' => 'Kode berhasil ditambahkan',
            'data' => [
                'id' => $kode->id,
                'kode' => $kode->kode,
                'jumlah' => $kode->jumlah,
                'jumlah_rusak' => $kode->jumlah_rusak,
                'jumlah_dipinjam' => $kode->jumlah_dipinjam,
                'created_at' => $kode->created_at,
                'updated_at' => $kode->updated_at,
            ]
        ], 201);
    }

    public function show(Kode $kode)
    {
        return response()->json([
            'id' => $kode->id,
            'kode' => $kode->kode,
            'jumlah' => $kode->jumlah,
            'jumlah_rusak' => $kode->jumlah_rusak,
            'jumlah_dipinjam' => $kode->jumlah_dipinjam,
            'items' => $kode->items, // biar item terkait juga kelihatan
            'created_at' => $kode->created_at,
            'updated_at' => $kode->updated_at,
        ]);
    }

    public function update(Request $request, Kode $kode)
    {
        $validated = $request->validate([
            'kode' => 'required|string|unique:kodes,kode,' . $kode->id,
        ]);

        $kode->update($validated);

        return response()->json([
            'message' => 'Kode berhasil diperbarui',
            'data' => [
                'id' => $kode->id,
                'kode' => $kode->kode,
                'jumlah' => $kode->jumlah,
                'jumlah_rusak' => $kode->jumlah_rusak,
                'jumlah_dipinjam' => $kode->jumlah_dipinjam,
                'created_at' => $kode->created_at,
                'updated_at' => $kode->updated_at,
            ]
        ]);
    }

    public function destroy(Kode $kode)
    {
        $kode->delete();

        return response()->json(['message' => 'Kode berhasil dihapus']);
    }
}
