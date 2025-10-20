<?php

namespace App\Observers;

use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Support\Facades\DB;


class RoleObserver
{
    /**
     * Handle the Role "created" event.
     */
    public function created(Role $role): void
    {
        //
    }

    /**
     * Handle the Role "updated" event.
     */
    public function updated(Role $role): void
    {
        //
    }

    /**
     * Handle the Role "deleting" event.
     */
    public function deleting(Role $role): void
    {
        // Hapus relasi role dengan permission
        DB::table('role_has_permissions')->where('role_id', $role->id)->delete();

        // Hapus relasi role dengan user (model_has_roles)
        DB::table('model_has_roles')->where('role_id', $role->id)->delete();
    }

    /**
     * Handle the Role "deleted" event.
     */
    public function deleted(Role $role): void
    {
        //
    }

    /**
     * Handle the Role "restored" event.
     */
    public function restored(Role $role): void
    {
        //
    }

    /**
     * Handle the Role "force deleted" event.
     */
    public function forceDeleted(Role $role): void
    {
        //
    }
}