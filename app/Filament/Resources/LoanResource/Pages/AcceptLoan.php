<?php

namespace App\Filament\Resources\LoanResource\Pages;

use App\Models\Loan;
use App\Filament\Resources\LoanResource;
use Filament\Resources\Pages\Page;
use Filament\Forms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Actions\Action;

class AcceptLoan extends Page
{

    public Loan $record;

    protected static string $resource = LoanResource::class;

    protected static string $view = 'filament.resources.loan-resource.pages.accept-loan';

    public static ?string $title = 'Approve Loan';

    public ?array $formData = [];

    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form->schema([
            Forms\Components\FileUpload::make('gambar')
                ->label('Bukti Penyerahan')
                ->image()
                ->disk('public')
                ->directory('loan-images')
                ->maxSize(1024)
                ->acceptedFileTypes(['image/jpeg', 'image/png'])
                ->columnSpanFull()
                ->required(),

            Forms\Components\Textarea::make('alasan_admin')
                ->label('Alasan Penyetujuan')
                ->dehydrateStateUsing(fn ($state) => strip_tags($state))
                ->placeholder('Tulis alasan...'),
        ])
        ->statepath('formData');
    }

    public function mount(Loan $record): void
    {
        $this->authorize('approve', $record); // cekk apakah user bisa akses

        $this->recordId = $record->getKey();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve')
                ->color('success')
                ->icon('heroicon-o-check')
                ->requiresConfirmation()
                ->modalHeading('Approve Loan')
                ->modalDescription('Are you sure you would like to approve this loan?')
                ->modalSubmitActionLabel('Approve')
                ->modalCancelActionLabel('Cancel')
                ->action(function () {
                    $data = $this->form->getState();

                    $this->record->update([
                        'status' => 'approved',
                        'alasan_admin' => $data['alasan_admin'] ?? null,
                        'gambar' => $data['gambar'] ?? null,
                    ]);

                    Notification::make()
                        ->title('Loan Approved')
                        ->success()
                        ->send();

                    $this->redirect(LoanResource::getUrl('index'));
                }),
        ];
    }
}

