<?php

namespace App\Filament\Resources\KodeResource\Pages;

use App\Filament\Resources\KodeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateKode extends CreateRecord
{
    protected static string $resource = KodeResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
