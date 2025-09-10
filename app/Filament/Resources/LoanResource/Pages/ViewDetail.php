<?php

namespace App\Filament\Resources\LoanResource\Pages;

use App\Models\Loan;
use App\Models\Item;
use App\Filament\Resources\LoanResource;
use Filament\Resources\Pages\Page;
use Filament\Infolists;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Tabs\Tab;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\Section;

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
                Split::make([
                    Tabs::make('Tabs')
                        ->tabs([
                            Tab::make('Loan')
                                ->icon('heroicon-o-archive-box')
                                ->columns(4)
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
                                        ->label('Kondisi')
                                        ->badge()
                                        ->formatStateUsing(fn($state) => ucwords($state))
                                        ->color(fn(string $state): string => match ($state) {
                                            'normal' => 'success',
                                            'layak' => 'info',
                                            'kurang layak' => 'warning',
                                            'rusak' => 'danger',
                                        }),

                                    Infolists\Components\TextEntry::make('loan_date')
                                        ->label('Tanggal Peminjaman')
                                        ->date('d-m-Y'),

                                    Infolists\Components\TextEntry::make('return_date')
                                        ->label('Tanggal Pengembalian')
                                        ->date('d-m-Y')
                                        ->placeholder('-'),

                                    Infolists\Components\TextEntry::make('status')
                                        ->badge()
                                        ->formatStateUsing(fn($state) => ucwords($state))
                                        ->color(fn(string $state): string => match ($state) {
                                            'approved' => 'success',
                                            'pending' => 'warning',
                                            'rejected' => 'danger',
                                            'returned' => 'info',
                                        }),

                                    Infolists\Components\TextEntry::make('alasan')
                                        ->label('Alasan Peminjaman')
                                        ->columnSpanFull(),
                                ]),
                            Tab::make('Barang')
                                ->icon('heroicon-o-rectangle-stack')
                                ->columns(4)
                                ->schema([
                                    Infolists\Components\TextEntry::make('item.kode_barang')
                                        ->label('Kode Barang'),

                                    Infolists\Components\TextEntry::make('item.name')
                                        ->label('Merek Barang'),

                                    Infolists\Components\TextEntry::make('item.kategori')
                                        ->label('Kategori'),

                                    Infolists\Components\TextEntry::make('jumlah')
                                        ->label('Jumlah')
                                        ->state(1),

                                    Infolists\Components\TextEntry::make('item.harga')
                                        ->label('Harga')
                                        ->money('IDR', true),

                                    Infolists\Components\TextEntry::make('item.kondisi')
                                        ->label('Kondisi'),
                                    
                                    Infolists\Components\TextEntry::make('item.lokasi')
                                        ->label('Lokasi Barang'),

                                    Infolists\Components\TextEntry::make('item.created_at')
                                        ->label('Tanggal Dimasukkan')
                                        ->dateTime(),
                                    
                                    Infolists\Components\TextEntry::make('item.updated_at')
                                        ->label('Tanggal Diubah')
                                        ->dateTime(),

                                    Infolists\Components\ImageEntry::make('item.gambar')
                                        ->label('Gambar')
                                        ->disk('public'),

                                    Infolists\Components\TextEntry::make('item.notes')
                                        ->label('Catatan')
                                        ->markdown() //biar ga ada tag html yang muncul
                                        ->columnSpanFull(),

                                ]),
                        ]),
                    Section::make()
                        ->schema([
                            TextEntry::make('created_at')
                                ->dateTime(),

                            TextEntry::make('updated_at')
                                ->dateTime(),
                        ])->grow(false),
                ])->from('md')
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('edit')
                ->label('Edit')
                ->icon('heroicon-o-pencil-square')
                ->visible(fn () => $this->loan->status === 'pending' && auth()->user()->hasRole('pegawai'))
                ->url(LoanResource::getUrl('edit', ['record' => $this->loan])),

            Actions\Action::make('return')
                ->label('Return')
                ->url(LoanResource::getUrl('return', ['record' => $this->loan]))
                ->icon('heroicon-o-arrow-uturn-left')
                // tombol return hanya ada ketika status pinjaman adalah 'approved' di akun pegawai
                ->visible(fn () => $this->loan->status === 'approved'  && auth()->user()->hasRole('pegawai')),
                    
            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check')
                ->color('success')
                ->visible(fn () => $this->loan->status === 'pending' && auth()->user()->hasRole('admin'))
                ->url(LoanResource::getUrl('approve', ['record' => $this->loan])),

            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->visible(fn () => $this->loan->status === 'pending' && auth()->user()->hasRole('admin'))
                ->url(LoanResource::getUrl('reject', ['record' => $this->loan])),
        ];
    }
}
