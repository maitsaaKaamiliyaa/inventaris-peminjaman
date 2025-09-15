<?php

namespace App\Filament\Resources\LoanResource\Pages;

use App\Filament\Resources\LoanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms;
use App\Models\Item;

class EditLoan extends EditRecord
{
    protected static string $resource = LoanResource::class;

    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('item_id')
                    ->label('Barang')
                    ->required()
                    ->options(function ($get, $record) {
                        return Item::where('kondisi', '!=', 'rusak')
                            ->where(function ($query) use ($record) {
                                $query->whereDoesntHave('loans', function ($query) {
                                    // menyembunyikan items yang punya status ini
                                    $query->whereIn('status', ['pending', 'approved']);
                                })
                                // menampilkan item yang sedang dipinjam
                                ->orWhere('id', $record?->item_id);
                            })
                            ->with('kodeRelasi')
                            ->get()
                            ->mapWithKeys(function ($item) {
                                $kode = $item->kodeRelasi?->kode ?? 'Tanpa Kode';
                                return [$item->id => "{$kode}{$item->kode} - {$item->name}"];
                            });
                    })
                    ->searchable(),

                Forms\Components\DatePicker::make('loan_date')
                    ->label('Tanggal Pinjam'),

                Forms\Components\TextArea::make('alasan')
                    ->label('Alasan Peminjaman')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
