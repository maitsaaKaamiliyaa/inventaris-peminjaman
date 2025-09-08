<?php

namespace App\Filament\Resources\LoanResource\Pages;

use App\Filament\Resources\LoanResource;
use Filament\Actions;
use App\Models\Loan;
use Filament\Resources\Pages\CreateRecord;

class CreateLoan extends CreateRecord
{
    protected static string $resource = LoanResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        return $data;
    }

    // walaupun memilih beberapa item, masuk ke loan terpisah
    protected function handleRecordCreation(array $data): Loan
    {
        $data = $this->form->getState(); // ambil data dari form
        $userId = auth()->id(); // ambil user id yang sedang login

        // ambil nilai sekali
        $loanDate   = $data['loan_date'];
        $returnDate = $data['return_date'] ?? null;
        $jumlah     = $data['jumlah'] ?? 1;
        $status     = 'pending';
        $alasan     = $data['alasan'];

        foreach ((array) $data['item_id'] as $itemId) {
            $lastLoan = Loan::create([ 
                'user_id'     => $userId,
                'item_id'     => $itemId,
                'jumlah'      => $jumlah,
                'loan_date'   => $loanDate,
                'return_date' => $returnDate,
                'status'      => $status,
                'alasan'      => $alasan,
            ]);
        }
        return $lastLoan; // mengembalikan pinjaman terakhir yang dibuat
    }
}
