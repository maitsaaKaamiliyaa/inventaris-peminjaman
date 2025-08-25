<?php

namespace App\Filament\Resources\LoanResource\Pages;

use App\Models\Loan;
use App\Models\Item;
use App\Filament\Resources\LoanResource;
use Filament\Forms;
use Filament\Pages\Actions\Action;
use Filament\Resources\Pages\Page;
// use Filament\Resources\Pages\PageRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

class ReturnLoan extends Page
{   
    
    public Loan $record;

    protected static string $resource = \App\Filament\Resources\LoanResource::class;

    protected static string $view = 'filament.resources.loan-resource.pages.return-loan';

    public $return_date;

    public $condition;

    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('return_date')
                    ->label('Tanggal Kembali')
                    ->required(),

                Forms\Components\Select::make('condition')
                    ->label('Kondisi')
                    ->options([
                        'normal' => 'Normal',
                        'layak' => 'Layak',
                        'kurang layak' => 'Kurang Layak',
                        'rusak' => 'Rusak',
                    ])
                    ->placeholder('Lihat Kondisi...')
                    ->required(),
            ])->columns(2);
    } 

    public function mount(Loan $record): void
    {
        $this->authorize('return', $record); // cekk apakah user bisa akses

        $this->record = $record;
    }

    public function submit(): void
    {
        $this->record->update([
            'return_date' => $this->return_date,
            'condition' => $this->condition,
            'status' => 'returned',
        ]);

        $item = $this->record->item;

        $kode = $item?->kodeRelasi;

        $conditionBefore = $this->record->condition;

        // jumlah_rusak bertambah jika saat return mengisi kondisi rusak
        if ($conditionBefore !== 'rusak' && $this->condition === 'rusak') {
            $kode->increment('jumlah_rusak', $this->record->jumlah);
        }

        $this->record->item->update([
            'kondisi' => $this->condition,
        ]);
        
        Notification::make()
            ->title('Barang berhasil dikembalikan.')
            ->success()
            ->send();

        $this->redirect(LoanResource::getUrl());
    }
}
