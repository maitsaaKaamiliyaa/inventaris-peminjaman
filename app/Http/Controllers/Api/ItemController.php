<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ItemController extends Controller
{
    /**
     * Ambil semua item + relasi kode
     */
    public function index()
    {
        $items = Item::with('kodeRelasi')->get()->map(function ($item) {
            if ($item->gambar && Storage::disk('public')->exists($item->gambar)) {
                $item->gambar_url = asset('storage/' . $item->gambar);
            } else {
                $item->gambar_url = null;
            }

            if ($item->qr_path && Storage::disk('public')->exists($item->qr_path)) {
                $item->qr_url = asset('storage/' . $item->qr_path);
            } else {
                $item->qr_url = null;
            }

            if (strtolower($item->kondisi) === 'rusak') {
                $item->is_borrowed = true;
            } else {
                $item->is_borrowed = $item->loans()
                    ->whereIn('status', ['approved', 'borrowed'])
                    ->exists();
            }

            return $item;
        });

        return response()->json($items);
    }

    /**
     * Tambah item baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_id'         => 'required|exists:kodes,id',
            'name'            => 'required|string|max:255',
            'kategori'        => 'nullable|string|max:255',
            'kategori_lainnya'=> 'nullable|string|max:255',
            'kondisi'         => 'nullable|string',
            'harga'           => 'nullable|numeric',
            'lokasi'          => 'nullable|string|max:255',
            'notes'           => 'nullable|string',
            'gambar'          => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // kategori lain
        $kategori = $request->kategori;
        if ($kategori === 'Lainnya' && $request->filled('kategori_lainnya')) {
            $kategori = $request->kategori_lainnya;
        }
        $validated['kategori'] = $kategori;

        // generate nomor urut per kode_id
        $lastNumber = Item::where('kode_id', $validated['kode_id'])
            ->selectRaw('MAX(CAST(kode AS UNSIGNED)) as max_number')
            ->value('max_number');

        $nextNumber = $lastNumber ? $lastNumber + 1 : 1;
        $validated['kode'] = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        // buat record dulu
        $item = Item::create($validated);
        $prefix = $item->kodeRelasi->kode;          
        $kodeBarang = $prefix . $item->kode;        

        // upload gambar (pakai uniqid biar gak ketimpa)
        if ($request->hasFile('gambar')) {
            $ext = $request->file('gambar')->getClientOriginalExtension();
            $filename = $kodeBarang . '_' . uniqid() . '.' . $ext;
            $path = $request->file('gambar')->storeAs('items', $filename, 'public');
            $item->update(['gambar' => $path]);
        }

        // tambahkan url penuh biar Flutter bisa tampil
        $item->gambar_url = $item->gambar ? asset('storage/' . $item->gambar) : null;
        $item->qr_url     = asset('storage/' . $item->qr_path);

        return response()->json([
            'message' => 'Item berhasil ditambahkan',
            'data'    => $item->load('kodeRelasi'),
        ], 201);
    }

    /**
     * Detail item
     */
    public function show(Item $item)
    {
        $item->load('kodeRelasi');

        $item->gambar_url = ($item->gambar && Storage::disk('public')->exists($item->gambar))
            ? asset('storage/' . $item->gambar)
            : null;

        $item->qr_url = ($item->qr_path && Storage::disk('public')->exists($item->qr_path))
            ? asset('storage/' . $item->qr_path)
            : null;

        if (strtolower($item->kondisi) === 'rusak') {
            $item->is_borrowed = true;
        } else {
            $item->is_borrowed = $item->loans()
                ->whereIn('status', ['approved', 'borrowed'])
                ->exists();
        }

        return response()->json($item);
    }

    /**
 * Detail item (versi publik untuk QR code)
 */
    public function showPublic($id)
    {
        $item = Item::with('kodeRelasi')->find($id);

        if (!$item) {
            return response()->json(['message' => 'Item tidak ditemukan'], 404);
        }

        $item->gambar_url = $item->gambar ? asset('storage/' . $item->gambar) : null;
        $item->qr_url     = $item->qr_path ? asset('storage/' . $item->qr_path) : null;

        return response()->json([
            'success' => true,
            'data' => $item,
        ]);
    }


    /**
     * Update item
     */
    public function update(Request $request, Item $item)
    {
        $validated = $request->validate([
            'kode_id'        => 'required|exists:kodes,id',
            'name'           => 'required|string|max:255',
            'kategori'       => 'required|string|max:255',
            'kategori_lainnya'=> 'nullable|string|max:255',
            'kondisi'        => 'required|string',
            'harga'          => 'required|integer|min:0',
            'lokasi'         => 'required|string',
            'notes'          => 'nullable|string',
            'gambar'         => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $kategori = $request->kategori;
        if ($kategori === 'Lainnya' && $request->filled('kategori_lainnya')) {
            $kategori = $request->kategori_lainnya;
        }
        $validated['kategori'] = $kategori;

        $kodeBarang = $item->kodeRelasi->kode . $item->kode;

        // ganti gambar (hapus lama, simpan baru pakai uniqid)
        if ($request->hasFile('gambar')) {
            if ($item->gambar && Storage::disk('public')->exists($item->gambar)) {
                Storage::disk('public')->delete($item->gambar);
            }
            $ext = $request->file('gambar')->getClientOriginalExtension();
            $filename = $kodeBarang . '_' . uniqid() . '.' . $ext;
            $path = $request->file('gambar')->storeAs('items', $filename, 'public');
            $validated['gambar'] = $path;
        }

        // ✅ update data, QR tidak dibuat ulang
        $item->update($validated);

        $item->gambar_url = $item->gambar ? asset('storage/' . $item->gambar) : null;
        $item->qr_url     = asset('storage/' . $item->qr_path);

        return response()->json([
            'message' => 'Item berhasil diperbarui',
            'data'    => $item->load('kodeRelasi'),
        ]);
    }

    /**
     * Hapus item
     */
    public function destroy(Item $item)
    {
        $isBorrowed = $item->loans()->whereIn('status', ['approved', 'borrowed'])->exists();
        if ($isBorrowed) {
            return response()->json([
                'success' => false,
                'message' => 'Item ini sedang dipinjam dan tidak bisa dihapus.'
            ], 400);
        }

        $kode = $item->kodeRelasi;
        if ($item->kondisi === 'Rusak') {
            $kode->decrement('jumlah_rusak');
        } else {
            $kode->decrement('jumlah');
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item berhasil dihapus.'
        ]);
    }

}
