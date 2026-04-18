<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeder isolasi — hanya menambahkan permission Item Request (termasuk approve).
 * Idempotent: aman dijalankan berkali-kali. Tidak menyentuh permission/role lain
 * selain "admin" & "superadmin".
 *
 * Jalankan: php artisan db:seed --class=ItemRequestPermissionSeeder
 */
class ItemRequestPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'item_request.read',
            'item_request.create',
            'item_request.update',
            'item_request.delete',
            'item_request.approve',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        foreach (['admin', 'superadmin'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if (! $role) continue;
            $role->givePermissionTo($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
