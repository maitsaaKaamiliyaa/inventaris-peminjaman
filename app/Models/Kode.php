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

    public function items()
    {
        return $this->hasMany(Item::class, 'kode_id', 'id');
    }

    public function loans()
    {
        return $this->hasMany(Loan::class, 'kode_id', 'id');
    }

}
