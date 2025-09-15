<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LoanPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'pegawai']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Loan $loan): bool
    {
        return $user->hasRole(['admin', 'pegawai']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('pegawai');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Loan $loan): bool
    {
        //pegawai hanya bisa update status pinjaman yang masih pending
        return $user->hasRole('pegawai') && $loan->status === 'pending' && $loan->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Loan $loan): bool
    {
        return $user->hasRole('pegawai');
    }

    public function return(User $user, Loan $loan): bool
    {
        return $user->hasRole('pegawai') && $loan->user_id === $user->id;
    }

    public function viewDetail(User $user, Loan $loan)
    {
        return $user->hasRole(['admin', 'pegawai']);
    }

    public function approve(User $user, Loan $loan): bool
    {
        return $user->hasRole('admin');
    }

    public function reject(User $user, Loan $loan): bool
    {
        return $user->hasRole('admin');
    }
    /**
     * Determine whether the user can restore the model.
     */
    // public function restore(User $user, Loan $loan): bool
    // {
    //     //
    // }

    /**
     * Determine whether the user can permanently delete the model.
     */
    // public function forceDelete(User $user, Loan $loan): bool
    // {
    //     //
    // }
}
