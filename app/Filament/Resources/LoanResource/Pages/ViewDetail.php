<?php

namespace App\Filament\Resources\LoanResource\Pages;

use App\Models\Loan;
use App\Filament\Resources\LoanResource;
use Filament\Resources\Pages\Page;
use Filament\Infolists;
use Filament\Actions;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\TextEntry;

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
                Split::make([
                    Tabs::make('Tabs')
                        ->tabs([
                            Tabs\Tab::make('Loan')
                                ->icon('heroicon-o-archive-box')
                                ->schema([
                                    Grid::make([
                                        'default' => 1,
                                        'sm' => 1,
                                        'md' => 2,
                                        'lg' => 4,
                                    ])
                                        ->schema([
                                            TextEntry::make('user.name')
                                                ->label('Pengguna'),

                                            TextEntry::make('item_detail')
                                                ->label('Barang')
                                                ->state(
                                                    fn(Loan $record) =>
                                                    $record->item
                                                        ? ($record->item->kodeRelasi?->kode ?? 'Tanpa Kode')
                                                        . $record->item->kode . ' - ' . $record->item->name
                                                        : '-'
                                                ),

                                            TextEntry::make('jumlah')
                                                ->label('Jumlah Barang'),

                                            TextEntry::make('item.kondisi')
                                                ->label('Kondisi')
                                                ->badge()
                                                ->formatStateUsing(fn($state) => ucwords($state))
                                                ->color(fn(string $state): string => match ($state) {
                                                    'normal' => 'success',
                                                    'layak' => 'info',
                                                    'kurang layak' => 'warning',
                                                    'rusak' => 'danger',
                                                }),

                                            TextEntry::make('loan_date')
                                                ->label('Tanggal Peminjaman')
                                                ->date('d-m-Y'),

                                            TextEntry::make('return_date')
                                                ->label('Tanggal Pengembalian')
                                                ->date('d-m-Y')
                                                ->placeholder('-'),

                                            TextEntry::make('status')
                                                ->label('Status')
                                                ->badge()
                                                ->formatStateUsing(fn($state) => ucwords($state))
                                                ->color(fn(string $state): string => match ($state) {
                                                    'approved' => 'success',
                                                    'pending' => 'warning',
                                                    'rejected' => 'danger',
                                                    'returned' => 'info',
                                                })
                                        ]),

                                    TextEntry::make('alasan')
                                        ->label('Alasan Peminjaman'),
                                ]),
                            Tabs\Tab::make('Barang')
                                ->icon('heroicon-o-rectangle-stack')
                                ->schema([]),
                        ]),
                    Section::make([
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                    ])->grow(false),
                ]),

            ])
            ->columns(1);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('edit')
                ->label('Edit')
                ->icon('heroicon-o-pencil-square')
                ->visible(fn($record) => auth()->user()->hasRole('pegawai'))
                ->url(LoanResource::getUrl('edit', ['record' => $this->loan])),
        ];
    }
}
