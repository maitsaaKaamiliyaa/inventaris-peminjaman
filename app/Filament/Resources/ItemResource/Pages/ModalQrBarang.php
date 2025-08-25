<?php

namespace App\Filament\Resources\ItemResource\Pages;

use App\Filament\Resources\ItemResource;
use Filament\Resources\Pages\Page;
use App\Models\Loan;
use App\Models\Item;
use Filament\Tables;
use Filament\Forms;

class ModalQrBarang extends Page implements Tables\Contracts\HasTable, Forms\Contracts\HasForms
{
    use Tables\Concerns\InteractsWithTable;
    
    use Forms\Concerns\InteractsWithForms;

    public $itemId;

    public ?array $data = []; //ini untuk menyimpan data yang akan ditampilkan di form

    protected static string $resource = ItemResource::class;

    protected static string $view = 'filament.resources.item-resource.pages.modal-qr-barang';

    public static ?string $title = 'Detail Barang';

    public function mount($itemId)
    {
        $this->itemId = $itemId;

        $item = Item::findOrFail($itemId);

        // mengisi data untuk form
        $this->form->fill($item->toArray());
    }

    // form untuk menampilkan detail barang
    public function form(Forms\Form $form): Forms\Form 
    {

        $item = Item::find($this->itemId);

        return $form
            ->schema([
                Forms\Components\TextInput::make('kode_barang')
                    ->label('Kode Barang')
                    ->disabled()
                    ->default($item->kode_barang), // menampilkan kode barang yang sudah digenerate

                Forms\Components\TextInput::make('kategori')
                    ->label('Kategori')
                    ->disabled(),

                Forms\Components\TextInput::make('harga')
                    ->label('Harga') 
                    ->disabled(),

                Forms\Components\TextInput::make('kondisi')
                    ->label('Kondisi')
                    ->disabled(),
                
                Forms\Components\TextInput::make('lokasi')
                    ->label('Lokasi Barang')
                    ->disabled(),
                
                Forms\Components\Textarea::make('notes')
                    ->label('Catatan')
                    ->disabled()
                    ->afterStateHydrated(fn ($component, $state) => $component->state(strip_tags($state))),
            ])
            ->columns(2)
            ->statePath('data') // mengikat form ke data yang akan ditampilkan
            ->model($item);
    }

    protected function getTableQuery()
    {
        // menggunakan query untuk mengambil data peminjaman terkait barang yang dipilih
        return Loan::query()
            ->where('item_id', $this->itemId)
            ->orderByRaw("CASE
                WHEN status = 'approved' THEN 0
                WHEN status = 'pending' THEN 1
                ELSE 2 END"); //agar status 'approved' muncul paling atas
    }

    // digunakan untuk mengatur tampilan tabel saat tidak ada data
    protected function getTableEmptyStateIcon(): ?string
    {
        return 'heroicon-o-inbox'; 
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'Tidak ada data peminjaman';
    }

    protected function getTableEmptyStateDescription(): ?string
    {
        return 'Barang ini belum pernah dipinjam.'; 
    }

    protected function getTableColumns(): array
    {
        return [
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
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'approved' => 'Borrowed',
                    default => ucfirst($state),
                })  
                ->color(fn (string $state): string => match ($state) {
                    'approved' => 'success',
                    'pending'  => 'warning',
                    'rejected' => 'danger', 
                    'returned' => 'info',
                }),
        ];
    }
}
