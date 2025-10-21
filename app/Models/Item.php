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
    
    // ✅ tambahin gambar_url biar API kirim link gambar full
    protected $appends = ['kode_barang', 'qr_url', 'gambar_url'];

    // ...

public function getGambarUrlAttribute()
    {
        if (!$this->gambar) {
            return null;
        }

        // Pastikan path 'storage/' hanya sekali
        $path = str_starts_with($this->gambar, 'items/')
            ? $this->gambar
            : "items/{$this->gambar}";

        // Gunakan url() supaya sesuai dengan host (APP_URL)
        return url("storage/{$path}");
    }


    protected static function booted()
    {
        /**
         * Saat membuat item baru
         */
        static::created(function ($item) {
            if ($item->kodeRelasi) {
                $item->kodeRelasi->increment('jumlah');

                if (strtolower($item->kondisi) === 'rusak') {
                    $item->kodeRelasi->increment('jumlah_rusak');
                }
            }

            // rename file gambar
            if ($item->gambar) {
                $kodeUtama = optional($item->kodeRelasi)->kode ?? 'ITEM';
                $kodeItem  = $item->kode ?? '000';
                $ext       = pathinfo($item->gambar, PATHINFO_EXTENSION);

                $newPath = "items/{$kodeUtama}-{$kodeItem}.{$ext}";
                if (Storage::disk('public')->exists($item->gambar)) {
                    Storage::disk('public')->move($item->gambar, $newPath);
                }

                $item->updateQuietly([
                    'gambar' => $newPath,
                ]);
            }

            // generate QR code
            self::generateKode($item);
            $item->saveQuietly();

            
        });

        /**
         * Saat update kondisi
         */
        static::updated(function ($item) {
            if ($item->wasChanged('kondisi') && $item->kodeRelasi) {
                $kode = $item->kodeRelasi;

                // normal → rusak
                if (strtolower($item->getOriginal('kondisi')) !== 'rusak'
                    && strtolower($item->kondisi) === 'rusak') {
                    $kode->increment('jumlah_rusak');
                }

                // rusak → normal
                if (strtolower($item->getOriginal('kondisi')) === 'rusak'
                    && strtolower($item->kondisi) !== 'rusak') {
                    if ($kode->jumlah_rusak > 0) {
                        $kode->decrement('jumlah_rusak');
                    }
                }
            }
        });

        /**
         * Saat hapus item
         */
        static::deleted(function ($item) {
            if ($item->kodeRelasi) {
                // Hitung ulang jumlah normal
                $totalNormal = Item::where('kode_id', $item->kode_id)
                    ->where('kondisi', '!=', 'Rusak')
                    ->count();

                // Hitung ulang jumlah rusak
                $totalRusak = Item::where('kode_id', $item->kode_id)
                    ->where('kondisi', 'Rusak')
                    ->count();

                $item->kodeRelasi->update([
                    'jumlah' => $totalNormal,
                    'jumlah_rusak' => $totalRusak,
                ]);
            }
        });

        /**
         * Hapus file ketika item dihapus
         */
        static::deleting(function ($item) {

            $sedangDipinjam = $item->loans()
            ->whereIn('status', ['approved', 'borrowed', 'dipinjam'])
            ->exists();

            if ($sedangDipinjam) {
                throw new \Exception('Item ini sedang dipinjam dan tidak dapat dihapus.');
            }

            if ($item->qr_path && Storage::exists($item->qr_path)) {
                Storage::delete($item->qr_path);
            }
            if ($item->gambar && Storage::exists($item->gambar)) {
                Storage::delete($item->gambar);
            }
        });
    }

    /**
     * Generate QR code
     */
    public static function generateKode($item)
    {
        $qrContent = 'http://127.0.0.1:8080/#/item-detail/' . $item->id;
        
        $kodeUtama = optional($item->kodeRelasi)->kode ?? 'UNKNOWN';
        $filename  = "qrcodes/{$kodeUtama}-{$item->kode}.png";

        $qrImage = QrCode::format('png')->size(300)->generate($qrContent);
        Storage::put($filename, $qrImage);

        $item->qr_path = $filename;
    }

    public function kodeRelasi(): BelongsTo
    {
        return $this->belongsTo(Kode::class, 'kode_id', 'id');
    }

    public function getKodeBarangAttribute()
    {
        $kodeUtama = $this->kodeRelasi?->kode ?? 'UNKNOWN';
        return $kodeUtama . $this->kode;
    }

    // ✅ accessor untuk kirim URL QR
    public function getQrUrlAttribute()
    {
        return $this->qr_path ? asset('storage/' . $this->qr_path) : null;
    }

    public function loans()
    {
        return $this->hasMany(Loan::class, 'item_id', 'id');
    }
}
