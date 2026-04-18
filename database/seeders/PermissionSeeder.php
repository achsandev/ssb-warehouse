<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ================================
        // 1. Definisikan semua permissions
        // ================================
        $permissions = [
            // Special permission for full access
            'all.manage',
            // Dashboard
            'dashboard.read',

            // Item Category
            'category.read',
            'category.create',
            'category.update',
            'category.delete',

            // Item Unit
            'unit.read',
            'unit.create',
            'unit.update',
            'unit.delete',

            // Movement Category
            'movement_category.read',
            'movement_category.create',
            'movement_category.update',
            'movement_category.delete',

            // Valuation Method
            'valuation_method.read',
            'valuation_method.create',
            'valuation_method.update',
            'valuation_method.delete',

            // request Type
            'request_type.read',
            'request_type.create',
            'request_type.update',
            'request_type.delete',

            // Payment Method
            'payment_method.read',
            'payment_method.create',
            'payment_method.update',
            'payment_method.delete',

            // Payment Duration
            'payment_duration.read',
            'payment_duration.create',
            'payment_duration.update',
            'payment_duration.delete',

            // Tax Type
            'tax_type.read',
            'tax_type.create',
            'tax_type.update',
            'tax_type.delete',

            // Rack
            'rack.read',
            'rack.create',
            'rack.update',
            'rack.delete',

            // Tank
            'tank.read',
            'tank.create',
            'tank.update',
            'tank.delete',

            // Item Request
            'item_request.read',
            'item_request.create',
            'item_request.update',
            'item_request.delete',
            'item_request.approve',

            // Receive Item
            'receive_item.read',
            'receive_item.create',
            'receive_item.update',
            'receive_item.delete',
            'receive_item.approve',

            // Item Transfer
            // - approve mencakup reject
            // - update mencakup cancel
            'item_transfer.read',
            'item_transfer.create',
            'item_transfer.update',
            'item_transfer.delete',
            'item_transfer.approve',

            // Item Usage
            'item_usage.read',
            'item_usage.create',
            'item_usage.update',
            'item_usage.delete',

            // Return Item
            'return_item.read',
            'return_item.create',
            'return_item.update',
            'return_item.delete',

            // Setting Approver Item Request
            'setting_approver_item_request.read',
            'setting_approver_item_request.create',
            'setting_approver_item_request.update',
            'setting_approver_item_request.delete',

            // Purchase Order
            'purchase_order.read',
            'purchase_order.create',
            'purchase_order.update',
            'purchase_order.delete',
            'purchase_order.approve',

            // Setting PO Approval
            'setting_po_approval.read',
            'setting_po_approval.create',
            'setting_po_approval.update',
            'setting_po_approval.delete',

            // Setting DPP Formula
            'setting_dpp_formula.read',
            'setting_dpp_formula.create',
            'setting_dpp_formula.update',
            'setting_dpp_formula.delete',

            // Stock Opname
            'stock_opname.read',
            'stock_opname.create',
            'stock_opname.update',
            'stock_opname.delete',
            'stock_opname.approve',

            // Stock Adjustment
            'stock_adjustment.read',
            'stock_adjustment.create',
            'stock_adjustment.update',
            'stock_adjustment.delete',
            'stock_adjustment.approve',

            // Dead Stock Management
            'dead_stock_management.read',
            'dead_stock_management.create',
            'dead_stock_management.update',
            'dead_stock_management.delete',

            // Reports
            'stock_usage_report.read',
            'stock_usage_report.export',
            'stock_adjustment_report.read',
            'stock_adjustment_report.export',
            'item_purchase_report.read',
            'item_purchase_report.export',
            'item_receipt_report.read',
            'item_receipt_report.export',
            'return_item_report.read',
            'return_item_report.export',
            'life_time_report.read',
            'life_time_report.export',
            'demand_rate_report.read',
            'demand_rate_report.export',
            'lead_time_report.read',
            'lead_time_report.export',

            // Warehouse Cash Request
            'warehouse_cash_request.read',
            'warehouse_cash_request.create',
            'warehouse_cash_request.update',
            'warehouse_cash_request.delete',
            'warehouse_cash_request.approve',

            // Cash Payment
            'cash_payment.read',
            'cash_payment.create',
            'cash_payment.update',
            'cash_payment.delete',
            'cash_payment.approve',

            // Cash Purchase
            'cash_purchase.read',
            'cash_purchase.create',
            'cash_purchase.update',
            'cash_purchase.delete',
            'cash_purchase.approve',
        ];

        // ================================
        // 2. Buat permission (jika belum ada)
        // ================================
        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // ================================
        // 3. Buat role "admin"
        // ================================
        $role = Role::firstOrCreate(['name' => 'admin']);

        // ================================
        // 4. Assign semua permission ke "admin" (including manage.all)
        // ================================
        $role->syncPermissions($permissions);

        // Optionally, create a separate role for manage.all
        $superRole = Role::firstOrCreate(['name' => 'superadmin']);
        $superRole->syncPermissions($permissions);

        // ================================
        // 5. Assign role admin ke user pertama
        // ================================
        $user = User::first();
        if ($user) {
            $user->assignRole('admin');
        }
    }
}
