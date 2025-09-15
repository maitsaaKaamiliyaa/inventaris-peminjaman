<?php

namespace App\Filament\Resources\LoanResource\Pages;

use App\Models\Loan;
use App\Filament\Resources\LoanResource;
use Filament\Resources\Pages\Page;
use Filament\Forms;
use Filament\Forms\Components\TextArea;
use Filament\Notifications\Notification;
use Filament\Pages\Actions\Action;

class RejectLoan extends Page
{

    public Loan $record;

    protected static string $resource = LoanResource::class;

    protected static string $view = 'filament.resources.loan-resource.pages.reject-loan';

    public $alasan_admin;

    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\TextArea::make('alasan_admin')
                    ->label('Alasan Penolakan')
                    ->dehydrateStateUsing(fn ($state) => strip_tags($state))
                    ->placeholder('Tulis alasan...')
                    ->required(),
            ]);
    }

    public function mount(Loan $record): void
    {
        $this->authorize('reject', $record); // cekk apakah user bisa akses

        $this->record = $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reject')
                ->label('Reject')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Reject Loan')
                ->modalDescription('Are you sure you would like to reject this loan? This action cannot be undone.')
                ->modalSubmitActionLabel('Reject')
                ->modalCancelActionLabel('Cancel')
                ->action(function () {
                    $this->record->update([
                        'status' => 'rejected',
                        'alasan_admin' => $this->alasan_admin,
                    ]);

                    Notification::make()
                        ->title('Loan Rejected')
                        ->success()
                        ->send();

                    $this->redirect(LoanResource::getUrl('index'));
                }),
        ];
    }
}
