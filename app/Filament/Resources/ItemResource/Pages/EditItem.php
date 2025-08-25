<?php

namespace App\Filament\Resources\ItemResource\Pages;

use App\Filament\Resources\ItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditItem extends EditRecord
{
    protected static string $resource = ItemResource::class;

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         Actions\DeleteAction::make(),
    //     ];
    // }
    
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (isset($data['kategori']) && is_string($data['kategori']) && (str_starts_with($data['kategori'], 'laptop')
        || str_starts_with($data['kategori'], 'printer') || str_starts_with($data['kategori'], 'proyektor') || str_starts_with($data['kategori'], 'alat uji'))) {
            $data['kategori'] = $data['kategori'];
        } else {
            $data['kategori'] = 'lainnya';
            $data['barang_lain'] = $data['kategori'];
        }

        return $data;
    }
    
    protected function getRedirectUrl(): string

    {
    return $this->getResource()::getUrl('index');
    }
}
