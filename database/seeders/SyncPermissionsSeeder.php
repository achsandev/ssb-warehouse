<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class SyncPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Permissions to ADD (used in routes/frontend but missing from DB)
        $toAdd = [
            'item_transfer.read',
            'item_transfer.create',
            'item_transfer.update',
            'item_transfer.delete',
            'item_transfer.approve',
            'item_usage.approve',
            'return_item.approve',
            'material_group.create',
        ];

        // Permissions to DELETE (exist in DB but not used anywhere)
        $toDelete = [
            'material_group.store',
            'setting_purchase_order.read',
            'setting_purchase_order.create',
            'setting_purchase_order.update',
            'setting_purchase_order.delete',
            'stock_unit.update',
            'stock_unit.delete',
        ];

        // Transfer role assignments from material_group.store to material_group.create
        $oldPerm = Permission::where('name', 'material_group.store')->first();
        if ($oldPerm) {
            $newPerm = Permission::firstOrCreate([
                'name' => 'material_group.create',
                'guard_name' => 'web',
            ]);
            // Copy role assignments
            $roles = $oldPerm->roles()->get();
            foreach ($roles as $role) {
                if (! $role->hasPermissionTo('material_group.create')) {
                    $role->givePermissionTo($newPerm);
                }
            }
        }

        // Add missing permissions
        foreach ($toAdd as $permName) {
            Permission::firstOrCreate([
                'name' => $permName,
                'guard_name' => 'web',
            ]);
            $this->command->info("Added: {$permName}");
        }

        // Delete unused permissions
        foreach ($toDelete as $permName) {
            $perm = Permission::where('name', $permName)->first();
            if ($perm) {
                // Detach from all roles first
                $perm->roles()->detach();
                $perm->delete();
                $this->command->info("Deleted: {$permName}");
            }
        }

        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('Permissions synced successfully.');
    }
}
