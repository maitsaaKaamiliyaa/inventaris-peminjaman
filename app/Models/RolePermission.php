<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class RolePermission extends Pivot
{
    use HasUuids;
    
    protected $table = 'role_has_permissions';

    public $incrementing = false; // UUID primary key
    protected $keyType = 'string'; // UUID type

    protected $fillable = [
        'role_id',
        'permission_id'
    ];

    public function role() : BelongsTo {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function permission() : BelongsTo {
        return $this->belongsTo(Permission::class, 'permission_id', 'id');
    }
}