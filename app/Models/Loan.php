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
    protected $appends = ['gambar_url'];

    public function getGambarUrlAttribute()
    {
        return $this->gambar ? asset('storage/' . $this->gambar) : null;
    }

    

    protected $fillable = [
        'user_id',
        'item_id',
        'jumlah',
        'jumlah_rusak',
        'jumlah_dipinjam',
        'loan_date',
        'return_date',
        'status',
        'alasan',
        'alasan_admin',
        'gambar'
    ];

    protected static function booted(): void
    {
        // Ketika loan baru dibuat
        static::created(function ($loan) {
            if ($loan->status === 'approved') {
                $kode = $loan->item?->kodeRelasi;
                if ($kode) {
                    $kode->safeIncrement('jumlah_dipinjam', $loan->jumlah);
                }
            }
        });

        // Ketika loan diperbarui (status berubah)
        static::updated(function ($loan) {
            if ($loan->wasChanged('status')) {
                $kode = $loan->item?->kodeRelasi;

                if ($loan->status === 'approved' && $kode) {
                    $kode->safeIncrement('jumlah_dipinjam', $loan->jumlah);
                }

                if ($loan->status === 'returned' && $kode) {
                    $kode->safeDecrement('jumlah_dipinjam', $loan->jumlah);
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
