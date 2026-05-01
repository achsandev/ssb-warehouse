<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ItemBrandsController;
use App\Http\Controllers\ItemCategoriesController;
use App\Http\Controllers\ItemRequestController;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\ItemTransferController;
use App\Http\Controllers\CashPaymentController;
use App\Http\Controllers\CashPurchaseController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\WarehouseCashRequestController;
use App\Http\Controllers\StockOpnameController;
use App\Http\Controllers\ItemUnitsController;
use App\Http\Controllers\ItemUsageController;
use App\Http\Controllers\MaterialGroupsController;
use App\Http\Controllers\MovementCategoriesController;
use App\Http\Controllers\PaymentDurationController;
use App\Http\Controllers\PaymentMethodsController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\RackController;
use App\Http\Controllers\ReceiveItemController;
use App\Http\Controllers\RequestTypesController;
use App\Http\Controllers\ReturnItemController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockUnitsController;
use App\Http\Controllers\SubMaterialGroupsController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TankController;
use App\Http\Controllers\TaxTypesController;
use App\Http\Controllers\UsageUnitsController;
use App\Http\Controllers\ValuationMethodsController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::prefix('nosu')->group(function () {
    Route::post('/signin', [AuthController::class, 'signin']);
    Route::post('/signup', [AuthController::class, 'signup']);

    // `check_token_idle` di-drop dari group ini — SPA sekarang pakai cookie
    // session yang idle-nya di-handle native oleh `SESSION_LIFETIME` (sliding).
    // Middleware tetap tersedia di alias untuk endpoint PAT third-party kalau
    // suatu saat dibutuhkan.
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/signout', [AuthController::class, 'signout']);

        // Common Material Groups
        Route::get('/material_groups', [MaterialGroupsController::class, 'index'])->middleware('custom_permission:material_group.read');
        Route::get('/material_groups/basic-info/{uid}', [MaterialGroupsController::class, 'get_basic_info_by_uid'])->middleware('custom_permission:material_group.read');
        Route::post('/material_groups', [MaterialGroupsController::class, 'store'])->middleware('custom_permission:material_group.create');
        Route::put('/material_groups/{uid}', [MaterialGroupsController::class, 'update'])->middleware('custom_permission:material_group.update');
        Route::delete('/material_groups/{uid}', [MaterialGroupsController::class, 'destroy'])->middleware('custom_permission:material_group.delete');

        // Common Sub Material Groups
        Route::get('/sub_material_groups', [SubMaterialGroupsController::class, 'index'])->middleware('custom_permission:sub_material_group.read');
        Route::get('/sub_material_groups/{uid}', [SubMaterialGroupsController::class, 'get_by_uid'])->middleware('custom_permission:sub_material_group.read');
        Route::get('/sub_material_groups/get_by_material_group_uid/{material_group_uid}', [SubMaterialGroupsController::class, 'get_by_material_group_uid'])->middleware('custom_permission:sub_material_group.read');
        Route::post('/sub_material_groups', [SubMaterialGroupsController::class, 'store'])->middleware('custom_permission:sub_material_group.create');
        Route::put('/sub_material_groups/{uid}', [SubMaterialGroupsController::class, 'update'])->middleware('custom_permission:sub_material_group.update');
        Route::delete('/sub_material_groups/{uid}', [SubMaterialGroupsController::class, 'destroy'])->middleware('custom_permission:sub_material_group.delete');

        // Common Item Brands
        Route::get('/item_brands', [ItemBrandsController::class, 'index'])->middleware('custom_permission:brands.read');
        Route::post('/item_brands', [ItemBrandsController::class, 'store'])->middleware('custom_permission:brands.create');
        Route::put('/item_brands/{uid}', [ItemBrandsController::class, 'update'])->middleware('custom_permission:brands.update');
        Route::delete('/item_brands/{uid}', [ItemBrandsController::class, 'destroy'])->middleware('custom_permission:brands.delete');

        // Common Item Categories
        Route::get('/item_categories', [ItemCategoriesController::class, 'index'])->middleware('custom_permission:category.read');
        Route::post('/item_categories', [ItemCategoriesController::class, 'store'])->middleware('custom_permission:category.create');
        Route::put('/item_categories/{uid}', [ItemCategoriesController::class, 'update'])->middleware('custom_permission:category.update');
        Route::delete('/item_categories/{uid}', [ItemCategoriesController::class, 'destroy'])->middleware('custom_permission:category.delete');

        // Common Item Units
        Route::get('/item_units', [ItemUnitsController::class, 'index'])->middleware('custom_permission:unit.read');
        Route::post('/item_units', [ItemUnitsController::class, 'store'])->middleware('custom_permission:unit.create');
        Route::put('/item_units/{uid}', [ItemUnitsController::class, 'update'])->middleware('custom_permission:unit.update');
        Route::delete('/item_units/{uid}', [ItemUnitsController::class, 'destroy'])->middleware('custom_permission:unit.delete');

        // Common Usage Units
        Route::get('/usage_units', [UsageUnitsController::class, 'index'])->middleware('custom_permission:unit.read');
        Route::post('/usage_units', [UsageUnitsController::class, 'store'])->middleware('custom_permission:unit.create');
        Route::put('/usage_units/{uid}', [UsageUnitsController::class, 'update'])->middleware('custom_permission:unit.update');
        Route::delete('/usage_units/{uid}', [UsageUnitsController::class, 'destroy'])->middleware('custom_permission:unit.delete');

        // Common Movement Categories
        Route::get('/movement_categories', [MovementCategoriesController::class, 'index'])->middleware('custom_permission:movement_category.read');
        Route::post('/movement_categories', [MovementCategoriesController::class, 'store'])->middleware('custom_permission:movement_category.create');
        Route::put('/movement_categories/{uid}', [MovementCategoriesController::class, 'update'])->middleware('custom_permission:movement_category.update');
        Route::delete('/movement_categories/{uid}', [MovementCategoriesController::class, 'destroy'])->middleware('custom_permission:movement_category.delete');

        // Common Valuation Methods
        Route::get('/valuation_methods', [ValuationMethodsController::class, 'index'])->middleware('custom_permission:valuation_method.read');
        Route::post('/valuation_methods', [ValuationMethodsController::class, 'store'])->middleware('custom_permission:valuation_method.create');
        Route::put('/valuation_methods/{uid}', [ValuationMethodsController::class, 'update'])->middleware('custom_permission:valuation_method.update');
        Route::delete('/valuation_methods/{uid}', [ValuationMethodsController::class, 'destroy'])->middleware('custom_permission:valuation_method.delete');

        // Common Request Types
        Route::get('/request_types', [RequestTypesController::class, 'index'])->middleware('custom_permission:request_type.read');
        Route::post('/request_types', [RequestTypesController::class, 'store'])->middleware('custom_permission:request_type.create');
        Route::put('/request_types/{uid}', [RequestTypesController::class, 'update'])->middleware('custom_permission:request_type.update');
        Route::delete('/request_types/{uid}', [RequestTypesController::class, 'destroy'])->middleware('custom_permission:request_type.delete');

        // Common Payment Methods
        Route::get('/payment_methods', [PaymentMethodsController::class, 'index'])->middleware('custom_permission:payment_method.read');
        Route::post('/payment_methods', [PaymentMethodsController::class, 'store'])->middleware('custom_permission:payment_method.create');
        Route::put('/payment_methods/{uid}', [PaymentMethodsController::class, 'update'])->middleware('custom_permission:payment_method.update');
        Route::delete('/payment_methods/{uid}', [PaymentMethodsController::class, 'destroy'])->middleware('custom_permission:payment_method.delete');

        // Common Payment Duration
        Route::get('/payment_duration', [PaymentDurationController::class, 'index'])->middleware('custom_permission:payment_duration.read');
        Route::post('/payment_duration', [PaymentDurationController::class, 'store'])->middleware('custom_permission:payment_duration.create');
        Route::put('/payment_duration/{uid}', [PaymentDurationController::class, 'update'])->middleware('custom_permission:payment_duration.update');
        Route::delete('/payment_duration/{uid}', [PaymentDurationController::class, 'destroy'])->middleware('custom_permission:payment_duration.delete');

        // Common Tax Types
        Route::get('/tax_types', [TaxTypesController::class, 'index'])->middleware('custom_permission:tax_type.read');
        Route::post('/tax_types', [TaxTypesController::class, 'store'])->middleware('custom_permission:tax_type.create');
        Route::put('/tax_types/{uid}', [TaxTypesController::class, 'update'])->middleware('custom_permission:tax_type.update');
        Route::delete('/tax_types/{uid}', [TaxTypesController::class, 'destroy'])->middleware('custom_permission:tax_type.delete');

        // Master Supplier
        Route::get('/supplier', [SupplierController::class, 'index'])->middleware('custom_permission:supplier.read');
        Route::post('/supplier', [SupplierController::class, 'store'])->middleware('custom_permission:supplier.create');
        Route::put('/supplier/{uid}', [SupplierController::class, 'update'])->middleware('custom_permission:supplier.update');
        Route::delete('/supplier/{uid}', [SupplierController::class, 'destroy'])->middleware('custom_permission:supplier.delete');

        // Master Warehouse
        Route::get('/warehouse', [WarehouseController::class, 'index'])->middleware('custom_permission:warehouse.read');
        Route::get('/warehouse/basic-info/{uid}', [WarehouseController::class, 'get_basic_info_by_uid'])->middleware('custom_permission:warehouse.read');
        Route::post('/warehouse', [WarehouseController::class, 'store'])->middleware('custom_permission:warehouse.create');
        Route::put('/warehouse/{uid}', [WarehouseController::class, 'update'])->middleware('custom_permission:warehouse.update');
        Route::delete('/warehouse/{uid}', [WarehouseController::class, 'destroy'])->middleware('custom_permission:warehouse.delete');

        // Master Rack
        Route::get('/rack', [RackController::class, 'index'])->middleware('custom_permission:rack.read');
        Route::get('/rack/{uid}', [RackController::class, 'get_by_warehouse_uid'])->middleware('custom_permission:rack.read');
        Route::post('/rack', [RackController::class, 'store'])->middleware('custom_permission:rack.create');
        Route::put('/rack/{uid}', [RackController::class, 'update'])->middleware('custom_permission:rack.update');
        Route::delete('/rack/{uid}', [RackController::class, 'destroy'])->middleware('custom_permission:rack.delete');
        // Master Tank
        Route::get('/tank', [TankController::class, 'index'])->middleware('custom_permission:tank.read');
        Route::get('/tank/{uid}', [TankController::class, 'get_by_warehouse_uid'])->middleware('custom_permission:tank.read');
        Route::post('/tank', [TankController::class, 'store'])->middleware('custom_permission:tank.create');
        Route::put('/tank/{uid}', [TankController::class, 'update'])->middleware('custom_permission:tank.update');
        Route::delete('/tank/{uid}', [TankController::class, 'destroy'])->middleware('custom_permission:tank.delete');

        // Master Item
        Route::get('/items', [ItemsController::class, 'index'])->middleware('custom_permission:items.read');
        Route::get('/items/{uid}', [ItemsController::class, 'get_by_uid'])->middleware('custom_permission:items.read');
        Route::post('/items', [ItemsController::class, 'store'])->middleware('custom_permission:items.create');
        Route::put('/items/{uid}', [ItemsController::class, 'update'])->middleware('custom_permission:items.update');
        Route::delete('/items/{uid}', [ItemsController::class, 'destroy'])->middleware('custom_permission:items.delete');
        // Master Stock
        Route::get('/stock', [StockController::class, 'index'])->middleware('custom_permission:items.read');
        Route::get('/stock/get_by_item_uid/{item_uid}', [StockController::class, 'getByItemUid'])->middleware('custom_permission:items.read');
        Route::post('/stock', [StockController::class, 'store'])->middleware('custom_permission:items.create');
        Route::put('/stock/{uid}', [StockController::class, 'update'])->middleware('custom_permission:items.update');
        Route::delete('/stock/{uid}', [StockController::class, 'destroy'])->middleware('custom_permission:items.delete');

        // Master Stock Units
        Route::get('/stock_unit', [StockUnitsController::class, 'index'])->middleware('custom_permission:stock_unit.read');
        Route::get('/stock_unit/get_by_stock_uid/{uid}', [StockUnitsController::class, 'getByStockUid'])->middleware('custom_permission:stock_unit.read');
        Route::post('/stock_unit', [StockUnitsController::class, 'store'])->middleware('custom_permission:stock_unit.create');
        // Route::post('/stock', [StockController::class, 'store']);
        // Route::put('/stock/{uid}', [StockController::class, 'update']);
        // Route::delete('/stock/{uid}', [StockController::class, 'destroy']);

        // Transaction Item Request
        Route::get('/item_request', [ItemRequestController::class, 'index'])->middleware('custom_permission:item_request.read,item_request.approve');
        Route::get('/item_request/{uid}', [ItemRequestController::class, 'show'])->middleware('custom_permission:item_request.read,item_request.approve');
        Route::post('/item_request', [ItemRequestController::class, 'store'])->middleware('custom_permission:item_request.create');
        Route::put('/item_request/{uid}', [ItemRequestController::class, 'update'])->middleware('custom_permission:item_request.update,item_request.approve');
        Route::delete('/item_request/{uid}', [ItemRequestController::class, 'destroy'])->middleware('custom_permission:item_request.delete');

        // Purchase Order
        Route::get('/purchase_order', [PurchaseOrderController::class, 'index'])->middleware('custom_permission:purchase_order.read,purchase_order.approve');
        Route::get('/purchase_order/{uid}', [PurchaseOrderController::class, 'show'])->middleware('custom_permission:purchase_order.read,purchase_order.approve');
        Route::post('/purchase_order', [PurchaseOrderController::class, 'store'])->middleware('custom_permission:purchase_order.create');
        Route::put('/purchase_order/{uid}', [PurchaseOrderController::class, 'update'])->middleware('custom_permission:purchase_order.update');
        Route::delete('/purchase_order/{uid}', [PurchaseOrderController::class, 'delete'])->middleware('custom_permission:purchase_order.delete');
        Route::post('/purchase_order/{uid}/approve', [PurchaseOrderController::class, 'approve'])->middleware('custom_permission:purchase_order.approve');

        // Receive Item
        Route::get('/receive_item', [ReceiveItemController::class, 'index'])->middleware('custom_permission:receive_item.read,receive_item.approve');
        Route::post('/receive_item', [ReceiveItemController::class, 'store'])->middleware('custom_permission:receive_item.create');
        Route::put('/receive_item/{uid}', [ReceiveItemController::class, 'update'])->middleware('custom_permission:receive_item.update,receive_item.approve');
        Route::delete('/receive_item/{uid}', [ReceiveItemController::class, 'destroy'])->middleware('custom_permission:receive_item.delete');

        // Item Usage
        Route::get('/item_usage', [ItemUsageController::class, 'index'])->middleware('custom_permission:item_usage.read');
        Route::get('/item_usage/{uid}', [ItemUsageController::class, 'show'])->middleware('custom_permission:item_usage.read');
        Route::post('/item_usage', [ItemUsageController::class, 'store'])->middleware('custom_permission:item_usage.create');
        Route::put('/item_usage/{uid}', [ItemUsageController::class, 'update'])->middleware('custom_permission:item_usage.update');
        Route::delete('/item_usage/{uid}', [ItemUsageController::class, 'destroy'])->middleware('custom_permission:item_usage.delete');

        // Item Transfer
        Route::get('/item_transfer', [ItemTransferController::class, 'index'])->middleware('custom_permission:item_transfer.read,item_transfer.approve,items.read');
        Route::get('/item_transfer/{uid}', [ItemTransferController::class, 'show'])->middleware('custom_permission:item_transfer.read,item_transfer.approve');
        Route::post('/item_transfer', [ItemTransferController::class, 'store'])->middleware('custom_permission:item_transfer.create');
        Route::put('/item_transfer/{uid}', [ItemTransferController::class, 'update'])->middleware('custom_permission:item_transfer.update');
        Route::delete('/item_transfer/{uid}', [ItemTransferController::class, 'destroy'])->middleware('custom_permission:item_transfer.delete');
        // Approval & rejection — keduanya butuh permission item_transfer.approve
        Route::post('/item_transfer/{uid}/approve', [ItemTransferController::class, 'approve'])->middleware('custom_permission:item_transfer.approve');
        Route::post('/item_transfer/{uid}/reject', [ItemTransferController::class, 'reject'])->middleware('custom_permission:item_transfer.approve');
        // Cancel — butuh permission item_transfer.update
        Route::post('/item_transfer/{uid}/cancel', [ItemTransferController::class, 'cancel'])->middleware('custom_permission:item_transfer.update');

        // Stock Opname
        Route::get('/stock_opname', [StockOpnameController::class, 'index'])->middleware('custom_permission:stock_opname.read');
        Route::get('/stock_opname/{uid}', [StockOpnameController::class, 'show'])->middleware('custom_permission:stock_opname.read');
        Route::post('/stock_opname', [StockOpnameController::class, 'store'])->middleware('custom_permission:stock_opname.create');
        Route::put('/stock_opname/{uid}', [StockOpnameController::class, 'update'])->middleware('custom_permission:stock_opname.update');
        Route::put('/stock_opname/{uid}/count', [StockOpnameController::class, 'enterCount'])->middleware('custom_permission:stock_opname.update');
        Route::put('/stock_opname/{uid}/revise', [StockOpnameController::class, 'revise'])->middleware('custom_permission:stock_opname.update');
        Route::delete('/stock_opname/{uid}', [StockOpnameController::class, 'destroy'])->middleware('custom_permission:stock_opname.delete');

        // Stock Adjustment
        Route::get('/stock_adjustment', [StockAdjustmentController::class, 'index'])->middleware('custom_permission:stock_adjustment.read');
        Route::get('/stock_adjustment/{uid}', [StockAdjustmentController::class, 'show'])->middleware('custom_permission:stock_adjustment.read');
        Route::post('/stock_adjustment', [StockAdjustmentController::class, 'store'])->middleware('custom_permission:stock_adjustment.create');
        Route::put('/stock_adjustment/{uid}', [StockAdjustmentController::class, 'update'])->middleware('custom_permission:stock_adjustment.update');
        Route::delete('/stock_adjustment/{uid}', [StockAdjustmentController::class, 'destroy'])->middleware('custom_permission:stock_adjustment.delete');

        // Cash Payment
        Route::get('/cash_payment/warehouses', [CashPaymentController::class, 'warehousesLookup'])->middleware('custom_permission:cash_payment.read');
        Route::get('/cash_payment', [CashPaymentController::class, 'index'])->middleware('custom_permission:cash_payment.read');
        Route::get('/cash_payment/{uid}', [CashPaymentController::class, 'show'])->middleware('custom_permission:cash_payment.read');
        Route::post('/cash_payment', [CashPaymentController::class, 'store'])->middleware('custom_permission:cash_payment.create');
        Route::put('/cash_payment/{uid}', [CashPaymentController::class, 'update'])->middleware('custom_permission:cash_payment.update');
        Route::delete('/cash_payment/{uid}', [CashPaymentController::class, 'destroy'])->middleware('custom_permission:cash_payment.delete');

        // Cash Purchase
        Route::get('/cash_purchase/warehouses', [CashPurchaseController::class, 'warehousesLookup'])->middleware('custom_permission:cash_purchase.read');
        Route::get('/cash_purchase/purchase_orders', [CashPurchaseController::class, 'purchaseOrdersLookup'])->middleware('custom_permission:cash_purchase.read');
        Route::get('/cash_purchase', [CashPurchaseController::class, 'index'])->middleware('custom_permission:cash_purchase.read');
        Route::get('/cash_purchase/{uid}', [CashPurchaseController::class, 'show'])->middleware('custom_permission:cash_purchase.read');
        Route::post('/cash_purchase', [CashPurchaseController::class, 'store'])->middleware('custom_permission:cash_purchase.create');
        Route::put('/cash_purchase/{uid}', [CashPurchaseController::class, 'update'])->middleware('custom_permission:cash_purchase.update');
        Route::delete('/cash_purchase/{uid}', [CashPurchaseController::class, 'destroy'])->middleware('custom_permission:cash_purchase.delete');

        // Warehouse Cash Request
        Route::get('/warehouse_cash_request/warehouses', [WarehouseCashRequestController::class, 'warehousesLookup'])->middleware('custom_permission:warehouse_cash_request.read');
        Route::get('/warehouse_cash_request', [WarehouseCashRequestController::class, 'index'])->middleware('custom_permission:warehouse_cash_request.read');
        Route::get('/warehouse_cash_request/{uid}', [WarehouseCashRequestController::class, 'show'])->middleware('custom_permission:warehouse_cash_request.read');
        Route::post('/warehouse_cash_request', [WarehouseCashRequestController::class, 'store'])->middleware('custom_permission:warehouse_cash_request.create');
        Route::put('/warehouse_cash_request/{uid}', [WarehouseCashRequestController::class, 'update'])->middleware('custom_permission:warehouse_cash_request.update');
        Route::post('/warehouse_cash_request/{uid}/attachment', [WarehouseCashRequestController::class, 'uploadAttachment'])->middleware('custom_permission:warehouse_cash_request.update');
        Route::delete('/warehouse_cash_request/{uid}', [WarehouseCashRequestController::class, 'destroy'])->middleware('custom_permission:warehouse_cash_request.delete');

        // Return Item
        Route::get('/return_item', [ReturnItemController::class, 'index'])->middleware('custom_permission:return_item.read');
        Route::get('/return_item/{uid}', [ReturnItemController::class, 'show'])->middleware('custom_permission:return_item.read');
        Route::post('/return_item', [ReturnItemController::class, 'store'])->middleware('custom_permission:return_item.create');
        Route::put('/return_item/{uid}', [ReturnItemController::class, 'update'])->middleware('custom_permission:return_item.update');
        Route::delete('/return_item/{uid}', [ReturnItemController::class, 'destroy'])->middleware('custom_permission:return_item.delete');

        // Dashboard
        Route::get('/dashboard/summary', [\App\Http\Controllers\DashboardController::class, 'summary']);

        // Reports
        Route::get('/reports/stock_usage', [\App\Http\Controllers\Reports\StockUsageReportController::class, 'index'])->middleware('custom_permission:stock_usage_report.read');
        Route::get('/reports/stock_usage/export', [\App\Http\Controllers\Reports\StockUsageReportController::class, 'export'])->middleware('custom_permission:stock_usage_report.export');

        Route::get('/reports/stock_adjustment', [\App\Http\Controllers\Reports\StockAdjustmentReportController::class, 'index'])->middleware('custom_permission:stock_adjustment_report.read');
        Route::get('/reports/stock_adjustment/export', [\App\Http\Controllers\Reports\StockAdjustmentReportController::class, 'export'])->middleware('custom_permission:stock_adjustment_report.export');

        Route::get('/reports/item_purchase', [\App\Http\Controllers\Reports\ItemPurchaseReportController::class, 'index'])->middleware('custom_permission:item_purchase_report.read');
        Route::get('/reports/item_purchase/export', [\App\Http\Controllers\Reports\ItemPurchaseReportController::class, 'export'])->middleware('custom_permission:item_purchase_report.export');

        Route::get('/reports/item_receipt', [\App\Http\Controllers\Reports\ItemReceiptReportController::class, 'index'])->middleware('custom_permission:item_receipt_report.read');
        Route::get('/reports/item_receipt/export', [\App\Http\Controllers\Reports\ItemReceiptReportController::class, 'export'])->middleware('custom_permission:item_receipt_report.export');

        Route::get('/reports/return_item', [\App\Http\Controllers\Reports\ReturnItemReportController::class, 'index'])->middleware('custom_permission:return_item_report.read');
        Route::get('/reports/return_item/export', [\App\Http\Controllers\Reports\ReturnItemReportController::class, 'export'])->middleware('custom_permission:return_item_report.export');

        Route::get('/reports/life_time', [\App\Http\Controllers\Reports\LifeTimeReportController::class, 'index'])->middleware('custom_permission:life_time_report.read');
        Route::get('/reports/life_time/export', [\App\Http\Controllers\Reports\LifeTimeReportController::class, 'export'])->middleware('custom_permission:life_time_report.export');

        Route::get('/reports/demand_rate', [\App\Http\Controllers\Reports\DemandRateReportController::class, 'index'])->middleware('custom_permission:demand_rate_report.read');
        Route::get('/reports/demand_rate/export', [\App\Http\Controllers\Reports\DemandRateReportController::class, 'export'])->middleware('custom_permission:demand_rate_report.export');

        Route::get('/reports/lead_time', [\App\Http\Controllers\Reports\LeadTimeReportController::class, 'index'])->middleware('custom_permission:lead_time_report.read');
        Route::get('/reports/lead_time/export', [\App\Http\Controllers\Reports\LeadTimeReportController::class, 'export'])->middleware('custom_permission:lead_time_report.export');

        // Role & Permission Management
        Route::get('/roles', [\App\Http\Controllers\RolePermissionController::class, 'index'])->middleware('custom_permission:role_management.read');
        Route::post('/roles', [\App\Http\Controllers\RolePermissionController::class, 'store'])->middleware('custom_permission:role_management.create');
        Route::get('/roles/{role}', [\App\Http\Controllers\RolePermissionController::class, 'show'])->middleware('custom_permission:role_management.read');
        Route::put('/roles/{role}', [\App\Http\Controllers\RolePermissionController::class, 'update'])->middleware('custom_permission:role_management.update');
        Route::delete('/roles/{role}', [\App\Http\Controllers\RolePermissionController::class, 'destroy'])->middleware('custom_permission:role_management.delete');
        Route::get('/permissions', [\App\Http\Controllers\RolePermissionController::class, 'permissions'])->middleware('custom_permission:role_management.read');

        // User Detail Management
        Route::get('/user_details', [\App\Http\Controllers\UserDetailController::class, 'index'])->middleware('custom_permission:user_management.read');
        Route::post('/user_details', [\App\Http\Controllers\UserDetailController::class, 'store'])->middleware('custom_permission:user_management.create');
        Route::get('/user_details/{userDetail}', [\App\Http\Controllers\UserDetailController::class, 'show'])->middleware('custom_permission:user_management.read');
        Route::put('/user_details/{userDetail}', [\App\Http\Controllers\UserDetailController::class, 'update'])->middleware('custom_permission:user_management.update');
        Route::delete('/user_details/{userDetail}', [\App\Http\Controllers\UserDetailController::class, 'destroy'])->middleware('custom_permission:user_management.delete');
        // User Management
        Route::get('/users', [\App\Http\Controllers\UserController::class, 'index'])->middleware('custom_permission:user_management.read');
        Route::post('/users', [\App\Http\Controllers\UserController::class, 'store'])->middleware('custom_permission:user_management.create');
        Route::get('/users/{uid}', [\App\Http\Controllers\UserController::class, 'show'])->middleware('custom_permission:user_management.read');
        Route::put('/users/{uid}', [\App\Http\Controllers\UserController::class, 'update'])->middleware('custom_permission:user_management.update');
        Route::delete('/users/{uid}', [\App\Http\Controllers\UserController::class, 'destroy'])->middleware('custom_permission:user_management.delete');
        Route::get('/users/{uid}/detail', [\App\Http\Controllers\UserController::class, 'detail'])->middleware('custom_permission:user_management.read');

        // Setting Approver Item Request
        Route::get('/setting_approver_item_request', [\App\Http\Controllers\WhSettingApproverItemRequestController::class, 'index'])->middleware('custom_permission:setting_approver_item_request.read');
        Route::post('/setting_approver_item_request', [\App\Http\Controllers\WhSettingApproverItemRequestController::class, 'store'])->middleware('custom_permission:setting_approver_item_request.create');
        Route::get('/setting_approver_item_request/{id}', [\App\Http\Controllers\WhSettingApproverItemRequestController::class, 'show'])->middleware('custom_permission:setting_approver_item_request.read');
        Route::put('/setting_approver_item_request/{id}', [\App\Http\Controllers\WhSettingApproverItemRequestController::class, 'update'])->middleware('custom_permission:setting_approver_item_request.update');
        Route::delete('/setting_approver_item_request/{id}', [\App\Http\Controllers\WhSettingApproverItemRequestController::class, 'destroy'])->middleware('custom_permission:setting_approver_item_request.delete');

        // Setting DPP Formula
        Route::get('/setting_dpp_formula', [\App\Http\Controllers\SettingDppFormulaController::class, 'index'])->middleware('custom_permission:setting_dpp_formula.read');
        Route::get('/setting_dpp_formula/{uid}', [\App\Http\Controllers\SettingDppFormulaController::class, 'show'])->middleware('custom_permission:setting_dpp_formula.read');
        Route::post('/setting_dpp_formula', [\App\Http\Controllers\SettingDppFormulaController::class, 'store'])->middleware('custom_permission:setting_dpp_formula.create');
        Route::put('/setting_dpp_formula/{uid}', [\App\Http\Controllers\SettingDppFormulaController::class, 'update'])->middleware('custom_permission:setting_dpp_formula.update');
        Route::delete('/setting_dpp_formula/{uid}', [\App\Http\Controllers\SettingDppFormulaController::class, 'destroy'])->middleware('custom_permission:setting_dpp_formula.delete');

        // Setting PO Approval (tiered)
        Route::get('/setting_po_approval', [\App\Http\Controllers\SettingPoApprovalController::class, 'index'])->middleware('custom_permission:setting_po_approval.read');
        Route::get('/setting_po_approval/{uid}', [\App\Http\Controllers\SettingPoApprovalController::class, 'show'])->middleware('custom_permission:setting_po_approval.read');
        Route::post('/setting_po_approval', [\App\Http\Controllers\SettingPoApprovalController::class, 'store'])->middleware('custom_permission:setting_po_approval.create');
        Route::put('/setting_po_approval/{uid}', [\App\Http\Controllers\SettingPoApprovalController::class, 'update'])->middleware('custom_permission:setting_po_approval.update');
        Route::delete('/setting_po_approval/{uid}', [\App\Http\Controllers\SettingPoApprovalController::class, 'delete'])->middleware('custom_permission:setting_po_approval.delete');

        // Lookup Routes
        Route::get('/lookup/items', [\App\Http\Controllers\Lookup\ItemLookupController::class, 'index']);
        Route::get('/lookup/items/{uid}', [\App\Http\Controllers\Lookup\ItemLookupController::class, 'show']);

        Route::get('/lookup/units', [\App\Http\Controllers\Lookup\UnitLookupController::class, 'index']);
        Route::get('/lookup/units/{uid}', [\App\Http\Controllers\Lookup\UnitLookupController::class, 'show']);

        Route::get('/lookup/suppliers', [\App\Http\Controllers\Lookup\SupplierLookupController::class, 'index']);
        Route::get('/lookup/suppliers/{uid}', [\App\Http\Controllers\Lookup\SupplierLookupController::class, 'show']);

        Route::get('/lookup/stocks', [\App\Http\Controllers\Lookup\StockLookupController::class, 'index']);
        Route::get('/lookup/stocks/{item_uid}', [\App\Http\Controllers\Lookup\StockLookupController::class, 'show']);

        Route::get('/lookup/item_requests', [\App\Http\Controllers\Lookup\ItemRequestLookupController::class, 'index']);
        Route::get('/lookup/item_requests/{uid}', [\App\Http\Controllers\Lookup\ItemRequestLookupController::class, 'show']);

        Route::get('/lookup/purchase_orders', [\App\Http\Controllers\Lookup\PurchaseOrderLookupController::class, 'index']);
        Route::get('/lookup/purchase_orders/{uid}', [\App\Http\Controllers\Lookup\PurchaseOrderLookupController::class, 'show']);

        Route::get('/lookup/receive_items', [\App\Http\Controllers\Lookup\ReceiveItemLookupController::class, 'index']);
        Route::get('/lookup/receive_items/{uid}', [\App\Http\Controllers\Lookup\ReceiveItemLookupController::class, 'show']);

        Route::get('/lookup/item_usage', [\App\Http\Controllers\Lookup\ItemUsageLookupController::class, 'index']);
        Route::get('/lookup/item_usage/{uid}', [\App\Http\Controllers\Lookup\ItemUsageLookupController::class, 'show']);

        Route::get('/lookup/return_items', [\App\Http\Controllers\Lookup\ReturnItemLookupController::class, 'index']);
        Route::get('/lookup/return_items/{uid}', [\App\Http\Controllers\Lookup\ReturnItemLookupController::class, 'show']);

        Route::get('/lookup/warehouses', [\App\Http\Controllers\Lookup\WarehouseLookupController::class, 'index']);
        Route::get('/lookup/warehouses/{uid}', [\App\Http\Controllers\Lookup\WarehouseLookupController::class, 'show']);

        Route::get('/lookup/roles', [\App\Http\Controllers\Lookup\RoleLookupController::class, 'index']);
        Route::get('/lookup/roles/{id}', [\App\Http\Controllers\Lookup\RoleLookupController::class, 'show']);

        Route::get('/lookup/setting_po_approval', [\App\Http\Controllers\Lookup\SettingPoApprovalLookupController::class, 'index']);

        Route::get('/lookup/racks', [\App\Http\Controllers\Lookup\RackLookupController::class, 'index']);
        Route::get('/lookup/racks/{uid}', [\App\Http\Controllers\Lookup\RackLookupController::class, 'show']);

        Route::get('/lookup/tanks', [\App\Http\Controllers\Lookup\TankLookupController::class, 'index']);
        Route::get('/lookup/tanks/{uid}', [\App\Http\Controllers\Lookup\TankLookupController::class, 'show']);

        Route::get('/lookup/material_groups', [\App\Http\Controllers\Lookup\MaterialGroupLookupController::class, 'index']);
        Route::get('/lookup/material_groups/{uid}', [\App\Http\Controllers\Lookup\MaterialGroupLookupController::class, 'show']);

        Route::get('/lookup/sub_material_groups', [\App\Http\Controllers\Lookup\SubMaterialGroupLookupController::class, 'index']);
        Route::get('/lookup/sub_material_groups/{uid}', [\App\Http\Controllers\Lookup\SubMaterialGroupLookupController::class, 'show']);

        Route::get('/lookup/stock_units', [\App\Http\Controllers\Lookup\StockUnitLookupController::class, 'index']);
        Route::get('/lookup/stock_units/{stock_uid}', [\App\Http\Controllers\Lookup\StockUnitLookupController::class, 'show']);

        Route::get('/lookup/setting_approver_item_request', [\App\Http\Controllers\Lookup\SettingApproverItemRequestLookupController::class, 'index']);

        Route::get('/lookup/setting_dpp_formula', [\App\Http\Controllers\Lookup\SettingDppFormulaLookupController::class, 'index']);

        // ── API Client management (admin only) ───────────────────────────
        Route::get('/api_clients', [\App\Http\Controllers\ApiClientController::class, 'index'])
            ->middleware('custom_permission:api_client.read');
        Route::get('/api_clients/{uid}', [\App\Http\Controllers\ApiClientController::class, 'show'])
            ->middleware('custom_permission:api_client.read');
        Route::post('/api_clients', [\App\Http\Controllers\ApiClientController::class, 'store'])
            ->middleware('custom_permission:api_client.create');
        Route::put('/api_clients/{uid}', [\App\Http\Controllers\ApiClientController::class, 'update'])
            ->middleware('custom_permission:api_client.update');
        Route::delete('/api_clients/{uid}', [\App\Http\Controllers\ApiClientController::class, 'destroy'])
            ->middleware('custom_permission:api_client.delete');

        // Token lifecycle — generate (replace lama) & delete (revoke).
        Route::post('/api_clients/{uid}/token', [\App\Http\Controllers\ApiClientTokenController::class, 'generate'])
            ->middleware('custom_permission:api_client.manage_token');
        Route::delete('/api_clients/{uid}/token', [\App\Http\Controllers\ApiClientTokenController::class, 'destroy'])
            ->middleware('custom_permission:api_client.manage_token');
    });

});

Route::view('{path}', 'app')
    ->where('path', '^(?!api|spa|dist|storage|fonts|img|i18n.json).*$')
    ->name('main');
