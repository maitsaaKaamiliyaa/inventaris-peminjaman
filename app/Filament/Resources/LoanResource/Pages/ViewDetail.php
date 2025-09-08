<?php

namespace App\Filament\Resources\LoanResource\Pages;

use App\Models\Loan;
use App\Filament\Resources\LoanResource;
use Filament\Resources\Pages\Page;
use Filament\Infolists;
use Filament\Actions;

class ViewDetail extends Page implements Infolists\Contracts\HasInfolists
{
    use Infolists\Concerns\InteractsWithInfolists;

    public Loan $loan;

    protected static string $resource = LoanResource::class;

    protected static string $view = 'filament.resources.loan-resource.pages.view-detail';

    public static ?string $title = 'Detail Peminjaman';

    public function mount($record): void
    {
        $this->loan = Loan::with('item.kodeRelasi', 'user')->findOrFail($record);

        // kalau pakai policy
        $this->authorize('view', $this->loan);
    }

    public function infolist(Infolists\Infolist $infolist): Infolists\Infolist
    {
        return $infolist
            ->record($this->loan)
            ->schema([
                Infolists\Components\TextEntry::make('user.name')
                    ->label('Pengguna'),

                Infolists\Components\TextEntry::make('item_detail')
                    ->label('Barang')
                    ->state(fn(Loan $record) =>
                        $record->item
                            ? ($record->item->kodeRelasi?->kode ?? 'Tanpa Kode')
                                . $record->item->kode . ' - ' . $record->item->name
                            : '-'
                    ),

                Infolists\Components\TextEntry::make('jumlah')
                    ->label('Jumlah Barang'),

                Infolists\Components\TextEntry::make('item.kondisi')
                    ->label('Kondisi'),

                Infolists\Components\TextEntry::make('loan_date')
                    ->label('Tanggal Peminjaman')
                    ->date('d-m-Y'),

                Infolists\Components\TextEntry::make('alasan')
                    ->label('Alasan Peminjaman'),

                Infolists\Components\TextEntry::make('return_date')
                    ->label('Tanggal Pengembalian')
                    ->date('d-m-Y')
                    ->placeholder('-'),

                Infolists\Components\TextEntry::make('status')
                    ->label('Status')
                    ->state(fn(Loan $record) => ucfirst($record->status)),
            ])
            ->columns(2);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('edit')
                ->label('Edit')
                ->icon('heroicon-o-pencil-square')
                ->visible(fn ($record) => auth()->user()->hasRole('pegawai'))
                ->url(LoanResource::getUrl('edit', ['record' => $this->loan])),
        ];
    }
}
