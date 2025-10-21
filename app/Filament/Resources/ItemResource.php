<?php

namespace App\Filament\Resources;

use Filament\Facades\Filament;
use App\Filament\Resources\ItemResource\Pages;
use App\Filament\Resources\ItemResource\RelationManagers;
use App\Models\Item;
use App\Models\Kode;
use App\Models\Loan;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\FormsComponent;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Filament\Support\RawJs;


class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('kode_id')
                    ->label('Kode')
                    ->options(Kode::pluck('kode', 'id'))
                    ->required(),

                Forms\Components\TextInput::make('name')
                    ->label('Merek Barang')
                    ->placeholder('Merek Barang')
                    ->required(),

                Forms\Components\Select::make('kategori_type')
                    ->label('Kategori')
                    ->options([
                        'laptop' => 'Laptop',
                        'proyektor' => 'Proyektor',
                        'printer' => 'Printer',
                        'alat uji' => 'Alat Uji',
                        'lainnya' => 'Lainnya',
                    ])
                    ->placeholder('Pilih Kategori')
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state !== 'lainnya') {
                            $set('barang_lain', null);
                        }
                    })
                    ->afterStateHydrated(function (callable $set, $state, $record) {
                        if (!$record) return;

                        $preset = ['laptop', 'proyektor', 'printer', 'alat uji'];
                        $set('kategori_type', in_array($record->kategori, $preset) ? $record->kategori : 'lainnya');
                    })
                    ->required(),

                Forms\Components\TextInput::make('barang_lain')
                    ->label('Perlengkapan Kerja Lainnya')
                    ->placeholder('Tulis kategori manual...')
                    ->reactive()
                    ->hidden(fn($get) => $get('kategori_type') !== 'lainnya')
                    ->required(fn($get) => $get('kategori_type') === 'lainnya')
                    ->afterStateHydrated(function (callable $set, $state, $record) {
                        $preset = ['laptop', 'proyektor', 'printer', 'alat uji'];
                        if ($record && !in_array($record->kategori, $preset)) {
                            $set('barang_lain', $record->kategori);
                        }
                    }),

                Forms\Components\Hidden::make('kategori')
                    ->dehydrated()
                    ->dehydrateStateUsing(
                        fn(callable $get) =>
                        $get('kategori_type') === 'lainnya'
                            ? $get('barang_lain')
                            : $get('kategori_type')
                    ),

                Forms\Components\TextInput::make('harga')
                    ->label('Harga')
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters([','])
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(999999999)
                    ->prefix('Rp. ')
                    ->required(),

                Forms\Components\Select::make('kondisi')
                    ->label('Kondisi')
                    ->options([
                        'normal' => 'Normal',
                        'layak' => 'Layak',
                        'kurang layak' => 'Kurang Layak',
                        'rusak' => 'Rusak',
                    ])
                    ->placeholder('Pilih Kondisi')
                    ->required(),

                Forms\Components\Select::make('lokasi_type')
                    ->label('Lokasi Barang')
                    ->options([
                        'lemari 1' => 'Lemari 1',
                        'lemari 2' => 'Lemari 2',
                        'lemari 3' => 'Lemari 3',
                        'lokasi lainnya' => 'Lokasi Lainnya',
                    ])
                    ->placeholder('Pilih Lokasi')
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state !== 'lokasi lainnya') {
                            $set('lokasi_lain', null);
                        }
                    })
                    ->afterStateHydrated(function (callable $set, $state, $record) {
                        if (!$record) return;

                        $preset = ['lemari 1', 'lemari 2', 'lemari 3'];
                        $set('lokasi_type', in_array($record->lokasi, $preset) ? $record->lokasi : 'lokasi lainnya');
                    })
                    ->required(),

                Forms\Components\TextInput::make('lokasi_lain')
                    ->label('Lokasi Lainnya')
                    ->placeholder('Tulis lokasi manual')
                    ->reactive()
                    ->hidden(fn($get) => $get('lokasi_type') !== 'lokasi lainnya')
                    ->required(fn($get) => $get('lokasi_type') === 'lokasi lainnya')
                    ->afterStateHydrated(function (callable $set, $state, $record) {
                        $preset = ['lemari 1', 'lemari 2', 'lemari 3'];
                        if ($record && !in_array($record->lokasi, $preset)) {
                            $set('lokasi_lain', $record->lokasi);
                        }
                    }),

                Forms\Components\Hidden::make('lokasi')
                    ->dehydrated()
                    ->dehydrateStateUsing(
                        fn(callable $get) =>
                        $get('lokasi_type') === 'lokasi lainnya'
                            ? $get('lokasi_lain')
                            : $get('lokasi_type')
                    ),

                Forms\Components\Hidden::make('qr-path'),

                Forms\Components\RichEditor::make('notes')
                    ->label('Catatan')
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('gambar')
                    ->label('Gambar Barang')
                    ->image()
                    ->disk('public')
                    ->directory('items')
                    ->maxSize(1024) // 1MB
                    ->acceptedFileTypes(['image/jpeg', 'image/png'])
                    ->columnSpanFull()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
        ->defaultSort('kode_id', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('no')
                    ->label('No')
                    ->rowIndex(),

                Tables\Columns\TextColumn::make('kode')
                    ->label('Kode')
                    ->getStateUsing(function ($record){
                        return "{$record->kodeRelasi->kode}{$record->kode}";
                    }),

                Tables\Columns\TextColumn::make('name')
                    ->label('Merek')
                    ->searchable(),

                Tables\Columns\TextColumn::make('kategori')
                    ->searchable()
                    ->formatStateUsing(fn($state) => ucwords($state)),

                Tables\Columns\TextColumn::make('kondisi')
                    ->badge()
                    ->formatStateUsing(fn($state) => ucwords($state))
                    ->color(fn(string $state): string => match ($state) {
                        'normal' => 'success',
                        'layak' => 'info',
                        'kurang layak' => 'warning',
                        'rusak' => 'danger',
                    }),

                Tables\Columns\TextColumn::make('harga')
                    ->money('idr'),

                Tables\Columns\TextColumn::make('lokasi')
                    ->searchable(),

                Tables\Columns\TextColumn::make('notes')
                    ->formatStateUsing(fn($state) => strip_tags($state))
                    ->limit(100),

                Tables\Columns\ImageColumn::make('gambar')
                    ->disk('public')
                    ->size(50, 50),
            ])
            ->filters([
                SelectFilter::make('kategori')
                    ->searchable()
                    ->options(function () {
                        return \App\Models\Item::query()
                            ->distinct()
                            ->pluck('kategori', 'kategori') // ['laptop' => 'laptop', 'kursi' => 'kursi']
                            ->mapWithKeys(fn($v, $k) => [$k => ucwords($v)]);
                    })

            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('qr_path')
                        ->label('QR')
                        ->icon('heroicon-o-qr-code')
                        ->modalHeading('QR Barang')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalWidth('md')
                        ->modalContent(function ($record) {
                            $url = route('filament.admin.resources.items.modal-qr-barang', [
                                'itemId' => $record->getKey(),
                            ]);
                            $png = QrCode::format('png')->size(200)->generate($url);
                            $dataUri = 'data:image/png;base64,' . base64_encode($png);

                            return view('filament.resources.item-resource.pages.lihat-qr', [
                                'dataUri' => $dataUri,
                                'record'  => $record,     // <–– kirim record ke Blade
                                'url'     => $url
                            ]);
                        }),
                    Tables\Actions\DeleteAction::make(),
                    
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
            'index' => Pages\ListItems::route('/'),
            'create' => Pages\CreateItem::route('/create'),
            'edit' => Pages\EditItem::route('/{record}/edit'),
            'modal-qr-barang' => Pages\ModalQrBarang::route('/{itemId}/modal-qr-barang'),
        ];
    }
}
