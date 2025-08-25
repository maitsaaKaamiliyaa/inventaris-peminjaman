<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HistoryResource\Pages;
use App\Filament\Resources\HistoryResource\RelationManagers;
use App\Models\Loan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class HistoryResource extends Resource
{
    protected static ?string $model = Loan::class;

    protected static ?string $modelLabel = "History";

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    public static function canCreate(): bool
    {
        return false;
    }

    // getEloquentQuery method untuk mengatur query yang digunakan untuk mengambil data
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user(); // mengambil user yang sedang login

        $query = parent::getEloquentQuery()
            // untuk mengambil data yang sudah dikembalikan atau ditolak
            // whereIn digunakan untuk mengambil data yang statusnya sesuai dengan kondisi yang diberikan
            ->whereIn('status', ['returned', 'rejected']);

        // jika user bukan admin, maka hanya bisa melihat data dirinya sendiri
        if (! $user->hasRole('admin')){
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('no')
                    ->label('No')
                    ->rowIndex(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pengguna')
                    ->searchable(),

                Tables\Columns\TextColumn::make('item.name')
                    ->label('Barang')
                    ->searchable(),

                Tables\Columns\TextColumn::make('jumlah')
                    ->label('Jumlah Barang')
                    ->numeric(),

                Tables\Columns\TextColumn::make('loan_date')
                    ->date()
                    ->label('Tanggal Peminjaman'),

                Tables\Columns\TextColumn::make('return_date')
                    ->date()
                    ->label('Tanggal Pengembalian'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn($state) => ucwords($state))
                    ->color(fn(string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        'returned' => 'info',
                    })
                    ->searchable(),
            ])
            ->filters([
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from'),
                        DatePicker::make('created_until'),
                    ])
                    // memfilter data berdasarkan tanggal pembuatan
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHistories::route('/'),
            'create' => Pages\CreateHistory::route('/create'),
            'edit' => Pages\EditHistory::route('/{record}/edit'),
        ];
    }
}
