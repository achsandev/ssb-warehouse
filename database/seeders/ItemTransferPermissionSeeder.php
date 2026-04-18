<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeder isolasi — hanya menambahkan permission terkait Item Transfer.
 * Idempotent: aman dijalankan berkali-kali. Tidak menyentuh permission lain
 * atau role lain selain "admin" & "superadmin".
 *
 * Jalankan: php artisan db:seed --class=ItemTransferPermissionSeeder
 */
class ItemTransferPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'item_transfer.read',
            'item_transfer.create',
            'item_transfer.update',
            'item_transfer.delete',
            'item_transfer.approve',
        ];

        // 1. Buat permission (hanya yang belum ada)
        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // 2. Beri akses ke admin & superadmin (jika role ada) — idempotent
        foreach (['admin', 'superadmin'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if (! $role) continue;

            $role->givePermissionTo($permissions);
        }

        // 3. Flush cache Spatie agar perubahan langsung efektif
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
