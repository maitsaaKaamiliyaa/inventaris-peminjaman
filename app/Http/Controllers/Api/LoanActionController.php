<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LoanActionController extends Controller
{
    /**
     * PATCH /api/loans/{loan}/status
     * Body: { status: 'approved'|'rejected'|'returned', alasan_admin?: string }
     */
    public function updateStatus(Request $request, Loan $loan)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected,returned',
            'alasan_admin' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $oldStatus = $loan->status;
        $loan->status = $request->status;

        if ($request->filled('alasan_admin')) {
            $loan->alasan_admin = $request->alasan_admin;
        }

        $loan->save(); // trigger event booted di model Loan untuk update stok otomatis

        return response()->json([
            'message' => "Status loan dari {$oldStatus} menjadi {$loan->status}",
            'data'    => $loan->load('item', 'user'),
        ]);
    }
}
