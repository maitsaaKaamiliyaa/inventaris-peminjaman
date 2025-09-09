<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanResource\Pages;
use App\Filament\Resources\LoanResource\RelationManagers;
use App\Filament\Resources\LoanResource\Pages\ReturnLoan;
use App\Filament\Resources\LoanResource\Pages\ViewDetail;
use App\Models\Loan;
use App\Models\User;
use App\Models\Item;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LoanResource extends Resource
{
    protected static ?string $model = Loan::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    //getEloquentQuery method untuk mengatur query yang digunakan untuk mengambil data
    public static function getEloquentQuery(): Builder
    {
        $user = Filament::auth()->user();

        // parent digunakan untuk mengambil query dari model Loan
        $query = parent::getEloquentQuery()
        ->whereIn('status', ['pending', 'approved']);

        if (! $user->hasRole('admin')){
            $query->where('user_id', $user->id);
        }
        return $query; // mengembalikan query yang sudah diatur
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                    ->default(auth()
                    ->id()),

                Forms\Components\Select::make('item_id')
                    ->label('Barang')
                    ->multiple()
                    ->options(function () {
                        return Item::where('kondisi', '!=', 'rusak') // mnyembunyikan item yang rusak, agar tidak bisa dipinjam
                        ->whereDoesntHave('loans', function ($query) {
                            // mnyembunyikan item yang punya status ini
                            $query->whereIn('status', ['pending', 'approved']);
                        })
                        ->with('kodeRelasi')
                        ->orderBy('kode_id', 'asc')
                        ->get()
                        ->mapWithKeys(function ($item) {
                            $kode = $item->kodeRelasi?->kode ?? 'Tanpa Kode';
                            return [$item->id => "{$kode}{$item->kode} - {$item->name}"];
                        });
                    })
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\DatePicker::make('loan_date')
                    ->label('Tanggal Peminjaman')
                    ->required(),

                Forms\Components\TextArea::make('alasan')
                    ->label('Alasan Peminjaman')
                    ->required()
                    ->dehydrateStateUsing(fn ($state) => strip_tags($state)) // menghapus tag html
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\Select::make('status')
                    ->options([
                        'pending'  => 'Pending',
                        'approved' => 'Approved',
                        'returned' => 'Returned',
                        'rejected' => 'Rejected',
                    ])
                    ->default('pending')
                    ->required()
                    ->hidden(),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            // mengatur di loan admin yang paling atasnya pending, di pegawai yang paling atasnya approved
            ->defaultSort(
                'status',
                auth()->user()->hasRole('admin') ? 'asc' : 'desc'
            )
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

                Tables\Columns\TextColumn::make('item.kondisi')
                    ->label('Kondisi')
                    ->badge()
                    ->formatStateUsing(fn($state) => ucwords($state))
                    ->color(fn(string $state): string => match ($state) {
                        'normal' => 'success',
                        'layak' => 'info',
                        'kurang layak' => 'warning',
                        'rusak' => 'danger',
                    }),

                Tables\Columns\TextColumn::make('loan_date')
                    ->date()
                    ->label('Tanggal Peminjaman'),

                Tables\Columns\TextColumn::make('alasan')
                    ->label('Alasan Peminjaman')
                    ->formatStateUsing(fn($state) => strip_tags($state))
                    ->limit(100)
                    ->wrap(),

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
                //
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
                    
                    Tables\Actions\Action::make('viewDetail')
                        ->label('View')
                        ->url(fn (Loan $record) => ViewDetail::getUrl(['record' => $record]))
                        ->icon('heroicon-o-eye'),
                ])->icon('heroicon-m-ellipsis-horizontal')
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
            'index' => Pages\ListLoans::route('/'),
            'create' => Pages\CreateLoan::route('/create'),
            'return' => Pages\ReturnLoan::route('/{record}/return'),
            'edit' => Pages\EditLoan::route('/{record}/edit'),
            'viewDetail' => Pages\ViewDetail::route('/{record}/viewDetail')
        ];
    }
}
