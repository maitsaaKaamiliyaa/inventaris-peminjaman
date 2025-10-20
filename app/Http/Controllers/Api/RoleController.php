<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        return response()->json(
            Role::withCount('users')->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|unique:roles,name'
        ]);

        // tambahkan guard_name default 'web'
        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => 'web'
        ]);

        return response()->json(
            Role::withCount('users')->find($role->id),
            201
        );
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|unique:roles,name,' . $role->id
        ]);

        $role->update([
            'name' => $data['name'],
            'guard_name' => 'web' // tetap pastikan diisi
        ]);

        return response()->json(
            Role::withCount('users')->find($role->id)
        );
    }



    public function destroy($id)
    {
    $role = Role::withCount('users')->findOrFail($id);

    if ($role->users_count > 0) {
        return response()->json([
            'message' => 'Role ini tidak dapat dihapus karena masih digunakan oleh user.'
        ], 400);
    }

    $role->delete();
    return response()->json(null, 204);
    }

}
