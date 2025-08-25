<?php

namespace App\Observers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Collection;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void{
    //     $role = $this->createRole($user);
    //     $permissions = $this->getPermissions();
        
    //     $this->assignRoleToPermission($role, $permissions);
    //     $this->assignRoleToUser($user, $role);
    }

    public function createRole($user) : Role {
        return Role::create([
            'name' => explode(' ', $user->name, 2)[0]."'s"
        ]);
    }

    public function getPermissions() : Collection {
        $requiredPermission = [
            '/admin',
        ];

        return Permission::whereIn('http_path', $requiredPermission)->get();
    }

    public function assignRoleToPermission($role, $permissions) : void {
        foreach ($permissions as $key => $item) {
            RolePermission::create([
                'role_id' => $role->id,
                'permission_id' => $item->id
            ]);
        }
    }

    public function assignRoleToUser($user, $role) : void {
        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id
        ]);
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}