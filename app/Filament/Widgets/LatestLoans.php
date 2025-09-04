<?php

namespace App\Filament\Widgets;

use App\Models\Loan;
use App\Filament\Resources\LoanResource;
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
            ]);
    }
}
