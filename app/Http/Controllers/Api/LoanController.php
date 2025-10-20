<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Loan;

class LoanController extends Controller
{
    // 🔹 Semua peminjaman (jika dibutuhkan)
    public function index()
    {
        return Loan::with(['user','item'])
            ->whereIn('status', ['pending','approved'])
            ->latest()
            ->get();
    }
    
    public function all()
    {
        return \App\Models\Loan::with(['user', 'item'])->latest()->get();
    }



    // 🔹 Active Loans (pending + approved)
    public function active()
    {
        return Loan::with(['user','item'])
            ->whereIn('status',['pending','approved'])
            ->latest()
            ->get();
    }

    // App\Http\Controllers\Api\LoanController.php
    public function history(Request $request)
    {
        $query = Loan::with(['user','item'])
            ->whereIn('status', ['returned','rejected'])
            ->latest();

        // hanya admin boleh lihat semua
        if (! $request->user()->hasRole('admin')) {
            $query->where('user_id', $request->user()->id);
        }

        return $query->get();
    }
    
    // 🔹 Buat peminjaman baru (pegawai)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_id'   => 'required|exists:items,id',
            'loan_date' => 'required|date',
            'alasan'    => 'required|string',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['status']  = 'pending';

        $loan = Loan::create($validated);

        return response()->json([
            'status'  => true,
            'message' => 'Peminjaman berhasil diajukan',
            'data'    => $loan->load(['user','item']),
        ], 201);
    }

    // 🔹 Admin approve + optional gambar & alasan
    public function approve(Request $request, $id)
    {
        $validated = $request->validate([
            'alasan_admin' => 'nullable|string',
            'reason'       => 'nullable|string',
            'gambar'       => 'nullable|image|max:2048',
        ]);

        $loan = Loan::findOrFail($id);

        // ✅ simpan gambar ke storage/loan-images
        if ($request->hasFile('gambar')) {
            $path = $request->file('gambar')->store('loan-images','public');
            $loan->gambar = $path; // simpan path ke DB
        }

        // ✅ update status & alasan
        $loan->status       = 'approved';
        $loan->alasan_admin = $validated['alasan_admin'] ?? $validated['reason'] ?? null;
        $loan->save();

        return response()->json([
            'message' => 'Loan approved',
            'data'    => $loan->load(['user','item']),
        ]);
    }

    // 🔹 Admin reject
    public function reject(Request $request, $id)
    {
        $validated = $request->validate([
            'alasan_admin' => 'required|string',
        ]);

        $loan = Loan::findOrFail($id);
        $loan->update([
            'status'       => 'rejected',
            'alasan_admin' => $validated['alasan_admin'],
        ]);

        return response()->json([
            'message' => 'Loan rejected',
            'data'    => $loan->load(['user','item']),
        ]);
    }

    // 🔹 Pegawai mengembalikan barang
    public function returned(Request $request, $id)
    {
        $validated = $request->validate([
            'kondisi' => 'required|string', // kondisi wajib
        ]);

        $loan = Loan::findOrFail($id);

        // update status loan
        $loan->update([
            'status'      => 'returned',
            'return_date' => now(),
            'kondisi'     => $validated['kondisi'],
        ]);

        // update kondisi item terkait
        if ($loan->item) {
            $loan->item->update([
                'kondisi' => $validated['kondisi'],
            ]);
        }

        return response()->json([
            'message' => 'Barang dikembalikan & kondisi diperbarui',
            'data'    => $loan->load(['user','item']),
        ]);
    }
}
