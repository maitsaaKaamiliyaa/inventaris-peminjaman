<?php

namespace App\Filament\Resources\KodeResource\Pages;

use App\Filament\Resources\KodeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKodes extends ListRecords
{
    protected static string $resource = KodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
