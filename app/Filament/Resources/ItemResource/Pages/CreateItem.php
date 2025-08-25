<?php

namespace App\Filament\Resources\ItemResource\Pages;


use App\Filament\Resources\ItemResource;
use App\Models\Item;
use App\Models\Kode;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateItem extends CreateRecord
{
    protected static string $resource = ItemResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function mutateFormDataBeforeCreate(array $data): array
    {
        // ambil nomor terbesar untuk kode_id ini
        $lastNumber = Item::where('kode_id', $data['kode_id'])
            ->selectRaw('MAX(CAST(kode AS UNSIGNED)) as max_number')
            ->value('max_number');

        // menentukan nomor berikutnya
        $nextNumber = $lastNumber ? $lastNumber + 1 : 1;

        // nomro diawali dari 0 jadi 001
        $nomor = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        // menyimpan kode
        $data['kode'] = $nomor;

        return $data;
    }
}
