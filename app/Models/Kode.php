<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Kode extends Model
{
    use HasUuids;

    protected $table = 'kodes';

    protected $fillable = [
        'kode',
        'jumlah',
        'jumlah_rusak',
        'jumlah_dipinjam',
    ];

    protected $casts = [
        'jumlah' => 'integer',
        'jumlah_rusak' => 'integer',
        'jumlah_dipinjam' => 'integer',
    ];

    /**
     * Relasi ke Item
     */
    public function items()
    {
        return $this->hasMany(Item::class, 'kode_id', 'id');
    }

    /**
     * Relasi ke Loan
     */
    public function loans()
    {
        return $this->hasMany(Loan::class, 'kode_id', 'id');
    }

    public function safeIncrement($field, $amount = 1)
    {
        if (!in_array($field, ['jumlah', 'jumlah_rusak', 'jumlah_dipinjam'])) {
            return;
        }

        $this->increment($field, $amount);
    }

    public function safeDecrement($field, $amount = 1)
    {
        if (!in_array($field, ['jumlah', 'jumlah_rusak', 'jumlah_dipinjam'])) {
            return;
        }

        if ($this->{$field} > 0) {
            $this->decrement($field, $amount);
        }
    }
}
