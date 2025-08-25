<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KodeResource\Pages;
use App\Filament\Resources\KodeResource\RelationManagers;
use App\Models\Kode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class KodeResource extends Resource
{
    protected static ?string $model = Kode::class;

    protected static ?string $navigationIcon = 'heroicon-s-numbered-list';

    protected static ?string $modelLabel = 'ID Item';

    protected static ?string $navigationLabel = 'ID Items';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('kode')
                    ->label('Kode')
                    ->required()
                    ->unique(Kode::class, 'kode', ignoreRecord: true)
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('kode', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('no')
                    ->label('No')
                    ->rowIndex(),

                Tables\Columns\TextColumn::make('kode')
                    ->searchable()
                    ->label('Kode'),

                Tables\Columns\TextColumn::make('jumlah')
                    ->label('Jumlah barang')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('jumlah_dipinjam')
                    ->label('Jumlah dipinjam')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('jumlah_rusak')
                    ->label('Jumlah rusak')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListKodes::route('/'),
            'create' => Pages\CreateKode::route('/create'),
            'edit' => Pages\EditKode::route('/{record}/edit'),
        ];
    }
}
