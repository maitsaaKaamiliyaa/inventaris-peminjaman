<?php

namespace App\Filament\Resources\ItemResource\Pages;

use App\Filament\Resources\ItemResource;
use Filament\Resources\Pages\ViewRecord;

class ViewQrCode extends ViewRecord
{
    protected static string $resource = ItemResource::class;

    protected static string $view = 'filament.resources.item-resource.pages.view-qr-code';

    protected function getActions(): array
    {
        return [];
    }
}