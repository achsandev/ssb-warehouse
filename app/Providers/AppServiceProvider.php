<?php

namespace App\Providers;

// Models
use App\Models\ItemBrands;
use App\Models\ItemCategories;
use App\Models\ItemRequest;
use App\Models\ItemRequestDetail;
use App\Models\Items;
use App\Models\ItemTransfer;
use App\Models\ItemTransferDetail;
use App\Models\ItemUnits;
use App\Models\ItemUsage;
use App\Models\ItemUsageDetail;
use App\Models\MaterialGroups;
use App\Models\MovementCategories;
use App\Models\PaymentDuration;
use App\Models\PaymentMethods;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\Rack;
use App\Models\ReceiveItem;
use App\Models\ReceiveItemDetail;
use App\Models\RequestTypes;
use App\Models\ReturnItem;
use App\Models\ReturnItemDetail;
use App\Models\SettingPoApproval;
use App\Models\SettingPurchaseOrderApproval;
use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentDetail;
use App\Models\StockOpname;
use App\Models\StockOpnameDetail;
use App\Models\StockUnits;
use App\Models\SubMaterialGroups;
use App\Models\Supplier;
use App\Models\Tank;
use App\Models\TaxTypes;
use App\Models\UsageUnits;
use App\Models\UserDetail;
use App\Models\ValuationMethods;
use App\Models\Warehouse;
// Observer
use App\Models\CashPayment;
use App\Models\CashPurchase;
use App\Models\WarehouseCashRequest;
use App\Observers\ItemBrandsObserver;
use App\Observers\ItemCategoriesObserver;
use App\Observers\ItemRequestDetailObserver;
use App\Observers\ItemRequestObserver;
use App\Observers\ItemsObserver;
use App\Observers\ItemTransferDetailObserver;
use App\Observers\ItemTransferObserver;
use App\Observers\ItemUnitsObserver;
use App\Observers\ItemUsageDetailObserver;
use App\Observers\ItemUsageObserver;
use App\Observers\MaterialGroupsObserver;
use App\Observers\MovementCategoriesObserver;
use App\Observers\PaymentDurationObserver;
use App\Observers\PaymentMethodsObserver;
use App\Observers\PurchaseOrderDetailObserver;
use App\Observers\PurchaseOrderObserver;
use App\Observers\RackObserver;
use App\Observers\ReceiveItemDetailObserver;
use App\Observers\ReceiveItemObserver;
use App\Observers\RequestTypesObserver;
use App\Observers\ReturnItemDetailObserver;
use App\Observers\ReturnItemObserver;
use App\Observers\SettingPoApprovalObserver;
use App\Observers\SettingPurchaseOrderApprovalObserver;
use App\Observers\StockAdjustmentDetailObserver;
use App\Observers\StockAdjustmentObserver;
use App\Observers\StockObserver;
use App\Observers\StockOpnameDetailObserver;
use App\Observers\StockOpnameObserver;
use App\Observers\StockUnitsObserver;
use App\Observers\SubMaterialGroupsObserver;
use App\Observers\SupplierObserver;
use App\Observers\TankObserver;
use App\Observers\TaxTypesObserver;
use App\Observers\UsageUnitsObserver;
use App\Observers\UserDetailObserver;
use App\Observers\ValuationMethodsObserver;
use App\Observers\CashPaymentObserver;
use App\Observers\CashPurchaseObserver;
use App\Observers\WarehouseCashRequestObserver;
use App\Observers\WarehouseObserver;
// Others
use App\Models\ApiClient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ItemUnits::observe(ItemUnitsObserver::class);
        \App\Models\WhSettingApproverItemRequest::observe(\App\Observers\WhSettingApproverItemRequestObserver::class);
        \App\Models\User::observe(\App\Observers\UserObserver::class);
        ValuationMethods::observe(ValuationMethodsObserver::class);
        PaymentMethods::observe(PaymentMethodsObserver::class);
        TaxTypes::observe(TaxTypesObserver::class);
        Supplier::observe(SupplierObserver::class);
        Warehouse::observe(WarehouseObserver::class);
        Rack::observe(RackObserver::class);
        Tank::observe(TankObserver::class);
        MaterialGroups::observe(MaterialGroupsObserver::class);
        SubMaterialGroups::observe(SubMaterialGroupsObserver::class);
        UsageUnits::observe(UsageUnitsObserver::class);
        RequestTypes::observe(RequestTypesObserver::class);
        PaymentDuration::observe(PaymentDurationObserver::class);
        Items::observe(ItemsObserver::class);
        ItemCategories::observe(ItemCategoriesObserver::class);
        MovementCategories::observe(MovementCategoriesObserver::class);
        ItemBrands::observe(ItemBrandsObserver::class);
        Stock::observe(StockObserver::class);
        StockUnits::observe(StockUnitsObserver::class);
        ItemRequest::observe(ItemRequestObserver::class);
        ItemRequestDetail::observe(ItemRequestDetailObserver::class);
        UserDetail::observe(UserDetailObserver::class);
        SettingPurchaseOrderApproval::observe(SettingPurchaseOrderApprovalObserver::class);
        SettingPoApproval::observe(SettingPoApprovalObserver::class);
        PurchaseOrder::observe(PurchaseOrderObserver::class);
        PurchaseOrderDetail::observe(PurchaseOrderDetailObserver::class);
        ReceiveItem::observe(ReceiveItemObserver::class);
        ReceiveItemDetail::observe(ReceiveItemDetailObserver::class);
        ItemUsage::observe(ItemUsageObserver::class);
        ItemUsageDetail::observe(ItemUsageDetailObserver::class);
        ItemTransfer::observe(ItemTransferObserver::class);
        ItemTransferDetail::observe(ItemTransferDetailObserver::class);
        ReturnItem::observe(ReturnItemObserver::class);
        ReturnItemDetail::observe(ReturnItemDetailObserver::class);
        StockOpname::observe(StockOpnameObserver::class);
        StockOpnameDetail::observe(StockOpnameDetailObserver::class);
        StockAdjustment::observe(StockAdjustmentObserver::class);
        StockAdjustmentDetail::observe(StockAdjustmentDetailObserver::class);
        WarehouseCashRequest::observe(WarehouseCashRequestObserver::class);
        CashPayment::observe(CashPaymentObserver::class);
        CashPurchase::observe(CashPurchaseObserver::class);

        // Rate limiter khusus API partner (Skenario A — B2B server-to-server).
        // Besaran limit dibaca dari kolom `api_clients.rate_limit_per_minute`
        // supaya bisa di-tune per-partner tanpa deploy kode.
        RateLimiter::for('api-client', function (Request $request) {
            $tokenable = $request->user();
            $token = $request->user()?->currentAccessToken();

            if ($tokenable instanceof ApiClient) {
                return Limit::perMinute($tokenable->rate_limit_per_minute ?: 60)
                    ->by('api-client:'.$tokenable->id);
            }

            // Fallback: tokenable lain (user internal, dsb.) atau anonim.
            $key = $token?->id
                ? 'token:'.$token->id
                : 'ip:'.$request->ip();

            return Limit::perMinute(60)->by($key);
        });
    }
}
