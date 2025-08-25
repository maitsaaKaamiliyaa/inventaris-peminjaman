<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    // protected static ?string $modelLabel = "Pengguna";

    protected static ?string $navigationIcon = 'heroicon-o-users';

    // protected static ?string $navigationLabel = 'Pengguna';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(50),

                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(100)
                    ->unique(User::class, 'email'),

                // Forms\Components\TextInput::make('phone')
                //     ->label('Nomor Telpon')
                //     ->tel()
                //     ->maxLength(15)
                //     ->unique(User::class, 'phone'),

                Forms\Components\TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => bcrypt($state)) //password di-hash sebelum disimpan
                    ->dehydrated(fn ($state) => filled($state)) //hanya di-hash jika ada nilai
                    ->required()
                    ->maxLength(255)
                    ->confirmed()
                    ->revealable(),

                Forms\Components\TextInput::make('password_confirmation')
                    ->password()
                    ->dehydrated(false)
                    ->required()
                    ->maxLength(255)
                    ->revealable(),

                Forms\Components\Select::make('roles')
                    ->label('Role')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->required(),
            ]); 
    }

    public static function table(Table $table): Table
    {
        return $table
        ->defaultSort('name', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('no')
                    ->rowIndex()
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                    
                // Tables\Columns\TextColumn::make('phone')
                //     ->label('Nomor Telpon')
                //     ->searchable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Role'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // SelectFilter::make('roles')
                //     ->options([
                //         'Admin' => 'Admin',
                //         'Pegawai' => 'Pegawai',
                //     ])
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}