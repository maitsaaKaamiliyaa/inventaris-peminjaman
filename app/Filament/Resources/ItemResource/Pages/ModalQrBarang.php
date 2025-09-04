<?php

namespace App\Filament\Resources\ItemResource\Pages;

use App\Filament\Resources\ItemResource;
use Filament\Resources\Pages\Page;
use App\Models\Loan;
use App\Models\Item;
use Filament\Tables;
use Filament\Infolists;
use Filament\Actions;
use Filament\Notifications\Notification;

class ModalQrBarang extends Page implements Tables\Contracts\HasTable, Infolists\Contracts\HasInfolists
{
    use Tables\Concerns\InteractsWithTable;
    
    use Infolists\Concerns\InteractsWithInfolists;

    public $itemId;

    // public ?array $data = []; //ini untuk menyimpan data yang akan ditampilkan di form

    protected static string $resource = ItemResource::class;

    protected static string $view = 'filament.resources.item-resource.pages.modal-qr-barang';

    public static ?string $title = 'Detail Barang';

    public function mount($itemId)
    {
        $this->itemId = $itemId;

        $item = Item::findOrFail($itemId);

        // mengisi data untuk form
        // $this->form->fill($item->toArray());
    }

    // form untuk menampilkan detail barang
    public function infolist(Infolists\Infolist $infolist): Infolists\Infolist 
    {

        $item = Item::find($this->itemId);

        return $infolist
            ->record($item) // mengikat infolist ke model item
            ->schema([
                Infolists\Components\TextEntry::make('kode_barang')
                    ->label('Kode Barang')
                    ->default($item->kode_barang), // menampilkan kode barang yang sudah digenerate

                Infolists\Components\TextEntry::make('name')
                    ->label('Merek Barang'),

                Infolists\Components\TextEntry::make('kategori')
                    ->label('Kategori'),

                Infolists\Components\TextEntry::make('jumlah')
                    ->label('Jumlah')
                    ->state(1),

                Infolists\Components\TextEntry::make('harga')
                    ->label('Harga')
                    ->money('IDR', true),

                Infolists\Components\TextEntry::make('kondisi')
                    ->label('Kondisi'),
                
                Infolists\Components\TextEntry::make('lokasi')
                    ->label('Lokasi Barang'),
                
                Infolists\Components\TextEntry::make('notes')
                    ->label('Catatan')
                    ->markdown(), //biar ga ada tag html yang muncul
                
                Infolists\Components\TextEntry::make('created_at')
                    ->label('Tanggal dimasukkan')
                    ->dateTime(),

                Infolists\Components\TextEntry::make('updated_at')
                    ->label('Tanggal terakhir diubah')
                    ->dateTime(),

                Infolists\Components\ImageEntry::make('gambar')
                    ->label('Gambar')
                    ->disk('public')
                    ->default($item->gambar),
            ])
            ->columns(2);
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

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('create')
                ->label('New Item')
                ->url(ItemResource::getUrl('create')),

            Actions\Action::make('edit')
                ->label('Edit')
                ->icon('heroicon-o-pencil-square')
                ->url(ItemResource::getUrl('edit', ['record' => $this->itemId])),

            Actions\Action::make('delete')
                ->label('Delete')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    $item = Item::findOrFail($this->itemId);
                    $item->delete();

                    Notification::make()
                        ->title('Deleted')
                        ->success()
                        ->send();

                    return redirect(ItemResource::getUrl('index'));  // mengembalikan ke menu items setelah melakukan penghapusan
                }),
        ];
    }
}
