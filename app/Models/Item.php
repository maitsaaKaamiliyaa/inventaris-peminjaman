<?php

namespace App\Models;

use App\Models\Kode;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class Item extends Model
{
    use HasUuids, HasFactory;

    protected $table = 'items';

    protected $fillable = [
        'kode',
        'name',
        'kategori',
        'jumlah',
        'gambar',
        'qr_path',
        'kondisi',
        'harga',
        'lokasi',
        'notes',
        'kode_id',
    ];

    protected static function booted()
    {
        
        static::creating(function ($item) {
            self::generateKode($item);
        });

        static::created(function ($item) {
            if ($item->kodeRelasi) {
                $item->kodeRelasi->increment('jumlah'); // ditambahkan jumlah barang pada kode
            }

            if ($item->kondisi === 'rusak' && $item->kodeRelasi) {
                $item->kodeRelasi->increment('jumlah_rusak'); // ditambahkan jumlah rusak pada kode jika kondisi rusak
            }

            // rename file foto barang upload kode-kode (hanya setelah record tersedia)
            if ($item->gambar) {
                $kodeUtama = optional($item->kodeRelasi)->kode ?? 'ITEM';

                $kodeItem  = $item->kode ?? '000';

                $ext     = pathinfo($item->gambar, PATHINFO_EXTENSION);
                
                $newPath = "items/{$kodeUtama}-{$kodeItem}.{$ext}";

                Storage::disk('public')->move($item->gambar, $newPath);

                // update kolom gambar di database
                $item->updateQuietly([
                    'gambar' => $newPath,
                ]);
            }
        });

        static::deleted(function ($item) {
            if ($item->kodeRelasi && $item->kodeRelasi->jumlah > 0) {
                $item->kodeRelasi->decrement('jumlah'); // mengurangi jumlah barang pada kode jika barang dihapus
            }

            if ($item->kondisi === 'rusak' && $item->kodeRelasi && $item->kodeRelasi->jumlah_rusak > 0) {
                $item->kodeRelasi->decrement('jumlah_rusak'); // mengurangi jumlah rusak pada kode jika barang dihapus dan kondisinya rusak
            }
        });

        static::updating(function ($item) {
            // cek apakah ada perubahan pada data yang masuk QR
            if (
                $item->isDirty('kode')
                || $item->isDirty('name')
                || $item->isDirty('kategori')
                || $item->isDirty('jumlah')
                || $item->isDirty('gambar')
                || $item->isDirty('kondisi')
                || $item->isDirty('harga')
                || $item->isDirty('lokasi')
                || $item->isDirty('notes')

            ) {
                self::generateKode($item);
            }
        });

        static::updated(function ($item) {
            // ketika kondisi berubah jadi rusak, jumlah rusak akan bertambah
            if ($item->wasChanged('kondisi') && $item->kondisi === 'rusak') {
                $kode = $item->kodeRelasi;

                if ($kode) {
                    $kode->increment('jumlah_rusak', 1);
                }
            }

            // ketika kondisi berubah jadi selain rusak, jumlah rusak akan bertambah
            if ($item->wasChanged('kondisi') && $item->kondisi !== 'rusak') {
                $kode = $item->kodeRelasi;

                if ($kode) {
                    $kode->decrement('jumlah_rusak', 1);
                }
            }
        });

        static::deleting(function ($item) {
            if ($item->qr_path && Storage::exists('public/' . $item->qr_path)) {
                Storage::delete('public/' . $item->qr_path);
            }
        });

        static::deleting(function ($item) {
            if ($item->gambar && Storage::exists('public/' . $item->gambar)) {
                Storage::delete('public/' . $item->gambar);
            } 
        });
    }

    public static function generateKode($item)
    {
        $qrContent = route('filament.admin.resources.items.modal-qr-barang',
            ['itemId' => $item->getKey()]); // isi qr adalah URL untuk scan item

        // pastikan relasi kode ada
        $kodeUtama = optional($item->kodeRelasi)->kode ?? 'UNKNOWN';

        // gabungkan kode dari tabel kodes dan items
        $filename = "qrcodes/{$kodeUtama}-{$item->kode}.png";

        $qrImage = QrCode::format('png')->size(300)->generate($qrContent);

        // simpan ke storage/public/qrcodes/
        Storage::put('public/' . $filename, $qrImage);

        // simpan path-nya ke database (langsung tanpa save ulang)
        $item->qr_path = $filename;
    }

    public function kodeRelasi() : BelongsTo {
        return $this->belongsTo(Kode::class, 'kode_id', 'id');
    }

    public function getKodeBarangAttribute()
    {
        $kodeUtama = $this->kodeRelasi?->kode ?? 'UNKNOWN';
        return $kodeUtama . $this->kode;
    }

    protected $appends = ['kode_barang'];

    public function loans()
    {
        return $this->hasMany(Loan::class, 'item_id', 'id');
    }

}
