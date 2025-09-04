<?php

namespace App\Filament\Widgets;

use App\Models\Loan;
use App\Filament\Resources\LoanResource;
use App\Filament\Resources\LoanResource\Pages\ReturnLoan;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestLoans extends BaseWidget
{
    
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {

        return $table
            ->query(LoanResource::getEloquentQuery()
                ->where('loan_date', '>=', now()->subMonth())
            )
            ->defaultPaginationPageOption(5)
            ->defaultSort('loan_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('no')
                    ->rowIndex(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Peminjam'),

                Tables\Columns\TextColumn::make('item.name')
                    ->label('Barang'),

                Tables\Columns\TextColumn::make('loan_date')
                    ->label('Tanggal Peminjaman')
                    ->date(),

                Tables\Columns\TextColumn::make('return_date')
                    ->label('Tanggal Pengembalian')
                    ->date(),
                    
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn($state) => ucwords($state))
                    ->color(fn(string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        'returned' => 'info',
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        // tombol edit hanya ada ketika status pinjaman adalah 'pending' di akun pegawai
                        ->visible(fn (Loan $record) => $record->status === 'pending' && auth()->user()->hasRole('pegawai')),

                    Tables\Actions\Action::make('return')
                        ->label('Return')
                        ->url(fn (Loan $record) => ReturnLoan::getUrl([$record]))
                        ->icon('heroicon-o-arrow-uturn-left')
                        // tombol return hanya ada ketika status pinjaman adalah 'approved' di akun pegawai
                        ->visible(fn (Loan $record) => $record->status === 'approved'  && auth()->user()->hasRole('pegawai')),
                    
                    // Tables\Actions\DeleteAction::make()
                    //     // tombol delete tdak ada ketika status pinjaman adalah 'pending' di akun pegawai
                    //     ->visible(fn (Loan $record) => $record->status !== 'pending' && auth()->user()->hasRole('pegawai')),

                    // tombol approve dan reject hanya ada ketika status pinjaman adalah 'pending' di akun admin
                    Tables\Actions\Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->visible(fn ($record) => $record->status === 'pending' && auth()->user()->hasRole('admin'))
                        // akan mengirimkan data ke server untuk mengubah status pinjaman menjadi 'approved'
                        ->action(function ($record) {
                            // mengurangi stok barang
                            $record->status = 'approved';
                            $record->save();
                        }),

                    Tables\Actions\Action::make('reject')
                        ->label('Reject')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->visible(fn ($record) => $record->status === 'pending' && auth()->user()->hasRole('admin'))
                        ->action(function ($record) {
                            $record->status = 'rejected';
                            $record->save();
                        }),
                ])->icon('heroicon-m-ellipsis-horizontal')
            ]);
    }
}
