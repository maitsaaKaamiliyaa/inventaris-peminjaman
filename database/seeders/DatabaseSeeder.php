<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    
    public function run()
    {
        $admin = Role::create(['name' => 'admin']);
        $pegawai = Role::create(['name' => 'pegawai']);

        Permission::create(['name' => 'lihat dashboard']);
        Permission::create(['name' => 'kelola barang']);

        $admin->givePermissionTo(['lihat dashboard', 'kelola barang']);
        $pegawai->givePermissionTo(['lihat dashboard']);
    }
}
