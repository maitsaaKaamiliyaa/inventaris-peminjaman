<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    use HasUuids;
    
    protected $table = 'loans';

    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'item_id',
        'jumlah',
        'jumlah_rusak',
        'jumlah_dipinjam',
        'loan_date',
        'return_date',
        'status'
    ];

    protected static function booted(): void // otomatis mengupdate stok barang saat status pinjaman berubah
    {
        static::updated(function ($loan) {
            // ketika status berubah jadi approved, jumlah dipinjam akan bertambah
            if ($loan->wasChanged('status') && $loan->status === 'approved') {
                $kode = $loan->item?->kodeRelasi;

                if ($kode) {
                    $kode->increment('jumlah_dipinjam', $loan->jumlah);
                }
            }

            // ketika status berubah jadi returned, jumlah dipinjam akan berkurang
            if ($loan->wasChanged('status') && $loan->status === 'returned') {
                $kode = $loan->item?->kodeRelasi;

                if ($kode) {
                    $kode->decrement('jumlah_dipinjam', $loan->jumlah);
                }
            }
        });
    }

    public function user() : BelongsTo {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function item() : BelongsTo {
        return $this->belongsTo(Item::class, 'item_id', 'id');

    }
}
