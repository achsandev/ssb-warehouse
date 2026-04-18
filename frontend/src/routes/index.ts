import { createRouter, createWebHistory } from 'vue-router'
// Import Stores
import { useAuthStore } from '@/stores/auth'
import { ability } from '@/plugins/casl'

// Import Layouts
const AuthLayout = () => import('@/layouts/AuthLayout.vue')
const MainLayout = () => import('@/layouts/MainLayout.vue')

// Import Modules / Views
const Signin = () => import('@/modules/auth/pages/Signin.vue')
const Dashboard = () => import('@/modules/dashboard/pages/Dashboard.vue')
const ItemBrands = () => import('@/modules/itembrands/pages/ItemBrands.vue')
const ItemCategories = () => import('@/modules/itemcategories/pages/ItemCategories.vue')
const ItemUnits = () => import('@/modules/itemunits/pages/ItemUnits.vue')
const MovementCategories = () => import('@/modules/movementcategories/pages/MovementCategories.vue')
const ValuationMethods = () => import('@/modules/valuationmethods/pages/ValuationMethods.vue')
const PaymentMethods = () => import('@/modules/paymentmethods/pages/PaymentMethods.vue')
const TaxTypes = () => import('@/modules/taxtypes/pages/TaxTypes.vue')
const Supplier = () => import('@/modules/supplier/pages/Supplier.vue')
const Warehouse = () => import('@/modules/warehouse/pages/Warehouse.vue')
const Rack = () => import('@/modules/rack/pages/Rack.vue')
const WarehouseRackAndTankManagemnt = () => import('@/modules/warehouse/pages/WarehouseRackAndTankManagement.vue')
const MaterialGroups = () => import('@/modules/materialgroups/pages/MaterialGroups.vue')
const SubMaterialGroups = () => import('@/modules/materialgroups/pages/SubMaterialGroupsManage.vue')
const UsageUnits = () => import('@/modules/usageunits/pages/UsageUnits.vue')
const RequestTypes = () => import('@/modules/requesttypes/pages/RequestTypes.vue')
const Items = () => import('@/modules/items/pages/Items.vue')
const PaymentDuration = () => import('@/modules/paymentduration/pages/PaymentDuration.vue')
const ItemRequest = () => import('@/modules/itemrequest/pages/ItemRequest.vue')
const PurchaseOrder = () => import('@/modules/purchaseorder/pages/PurchaseOrder.vue')
const ReceiveItem = () => import('@/modules/receiveitem/pages/ReceiveItem.vue')
const ReturnItem  = () => import('@/modules/returnitem/pages/ReturnItem.vue')
const ItemUsage = () => import('@/modules/itemusage/pages/ItemUsage.vue')
const ItemTransfer = () => import('@/modules/itemtransfer/pages/ItemTransfer.vue')
const StockOpname       = () => import('@/modules/stockopname/pages/StockOpname.vue')
const StockAdjustment   = () => import('@/modules/stockadjustment/pages/StockAdjustment.vue')
const WarehouseCashRequest = () => import('@/modules/warehousecashrequest/pages/WarehouseCashRequest.vue')
const CashPayment = () => import('@/modules/cashpayment/pages/CashPayment.vue')
const CashPurchase = () => import('@/modules/cashpurchase/pages/CashPurchase.vue')
const UserManagement = () => import('@/modules/usermanagement/pages/UserManagement.vue')
const RoleManagement = () => import('@/modules/rolemanagement/pages/RoleManagement.vue')
const SettingApproverItemRequest = () => import('@/modules/settingapproveritemrequest/pages/SettingApproverItemRequest.vue')
const StockUsageReport = () => import('@/modules/reports/stockusagereport/pages/StockUsageReport.vue')
const StockAdjustmentReport = () => import('@/modules/reports/stockadjustmentreport/pages/StockAdjustmentReport.vue')
const ItemPurchaseReport = () => import('@/modules/reports/itempurchasereport/pages/ItemPurchaseReport.vue')
const ItemReceiptReport = () => import('@/modules/reports/itemreceiptreport/pages/ItemReceiptReport.vue')
const ReturnItemReport = () => import('@/modules/reports/returnitemreport/pages/ReturnItemReport.vue')
const LifeTimeReport = () => import('@/modules/reports/lifetimereport/pages/LifeTimeReport.vue')
const DemandRateReport = () => import('@/modules/reports/demandratereport/pages/DemandRateReport.vue')
const LeadTimeReport = () => import('@/modules/reports/leadtimereport/pages/LeadTimeReport.vue')
const SettingPurchaseOrderApproval = () => import('@/modules/settingpurchaseorderapproval/pages/SettingPurchaseOrderApproval.vue')
const SettingDppFormula = () => import('@/modules/settingdppformula/pages/SettingDppFormula.vue')
const Forbidden = () => import('@/modules/error/pages/Forbidden.vue')

const routes = [
    {
        path: '/auth',
        component: AuthLayout,
        children: [
            { path: 'signin', name: 'Signin', component: Signin, meta: { guest: true } }
        ]
    },
    {
        path: '/',
        component: MainLayout,
        redirect: 'dashboard',
        children: [
            { path: 'dashboard', name: 'Dashboard', component: Dashboard, meta: { title: 'dashboard', requiresAuth: true } },
            {
                path: 'common',
                meta: { title: 'commonData' },
                children: [
                    {
                        path: 'item_categories',
                        name: 'ItemCategories',
                        component: ItemCategories,
                        meta: {
                            title: 'itemCategories',
                            requiresAuth: true,
                            ability: { action: 'read', subject: 'category' }
                        }
                    },
                    { path: 'units', name: 'Units', component: ItemUnits, meta: { title: 'units', requiresAuth: true, ability: { action: 'read', subject: 'unit' } } },
                    { path: 'movement_categories', name: 'MovementCategories', component: MovementCategories, meta: { title: 'movementCategories', requiresAuth: true, ability: { action: 'read', subject: 'movement_category' } } },
                    { path: 'valuation_methods', name: 'ValuationMethods', component: ValuationMethods, meta: { title: 'valuationMethods', requiresAuth: true, ability: { action: 'read', subject: 'valuation_method' } } },
                    { path: 'request_types', name: 'RequestTypes', component: RequestTypes, meta: { title: 'requestTypes', requiresAuth: true, ability: { action: 'read', subject: 'request_type' } } },
                    { path: 'payment_methods', name: 'PaymentMethods', component: PaymentMethods, meta: { title: 'paymentMethods', requiresAuth: true, ability: { action: 'read', subject: 'payment_method' } } },
                    { path: 'payment_duration', name: 'PaymentDuration', component: PaymentDuration, meta: { title: 'paymentDuration', requiresAuth: true, ability: { action: 'read', subject: 'payment_duration' } } },
                    { path: 'tax_types', name: 'TaxTypes', component: TaxTypes, meta: { title: 'taxTypes', requiresAuth: true, ability: { action: 'read', subject: 'tax_type' } } },
                ]
            },
            {
                path: 'master',
                meta: { title: 'masterData' },
                children: [
                    { path: 'material_groups', name: 'MaterialGroups', component: MaterialGroups, meta: { title: 'materialGroups', requiresAuth: true, ability: { action: 'read', subject: 'material_group' } } },
                    { path: 'material_groups/:uid/sub_material_groups', name: 'SubMaterialGroups', component: SubMaterialGroups, meta: { title: 'subMaterialGroups', requiresAuth: true, parent: 'MaterialGroups', ability: { action: 'read', subject: 'sub_material_group' } } },
                    { path: 'brands', name: 'Brands', component: ItemBrands, meta: { title: 'brands', requiresAuth: true, ability: { action: 'read', subject: 'brands' } } },
                    { path: 'usage_units', name: 'UsageUnits', component: UsageUnits, meta: { title: 'usageUnits', requiresAuth: true, ability: { action: 'read', subject: 'usage_units' } } },
                    { path: 'suppliers', name: 'Suppliers', component: Supplier, meta: { title: 'suppliers', requiresAuth: true, ability: { action: 'read', subject: 'supplier' } } },
                    { path: 'storage_locations', name: 'Warehouse', component: Warehouse, meta: { title: 'storageLocations', requiresAuth: true, ability: { action: 'read', subject: 'warehouse' } } },
                    { path: 'storage_locations/:uid/rack-tank', name: 'RackAndTank', component: WarehouseRackAndTankManagemnt, meta: { title: 'rackAndTank', requiresAuth: true, parent: 'Warehouse', ability: { action: 'read', subject: 'rack' } } },
                    { path: 'storage_locations/:uid/rack', name: 'Rack', component: Rack, meta: { title: 'rack', requiresAuth: true, parent: 'Warehouse', ability: { action: 'read', subject: 'rack' } } },
                    { path: 'items_services', name: 'Items', component: Items, meta: { title: 'itemsServices', requiresAuth: true, ability: { action: 'read', subject: 'items' } } }
                ]
            },
            {
                path: 'purchase',
                meta: { title: 'purchase' },
                children: [
                    { path: 'item_request', name: 'ItemRequest', component: ItemRequest, meta: { title: 'itemRequest', requiresAuth: true, ability: { action: 'read', subject: 'item_request' } } },
                    { path: 'purchase_order', name: 'PurchaseOrder', component: PurchaseOrder, meta: { title: 'purchaseOrder', requiresAuth: true, ability: { action: 'read', subject: 'purchase_order' } } },
                    { path: 'receive_item', name: 'ReceiveItem', component: ReceiveItem, meta: { title: 'receiveItem', requiresAuth: true, ability: { action: 'read', subject: 'receive_item' } } },
                    { path: 'return_item', name: 'ReturnItem', component: ReturnItem, meta: { title: 'returnItem', requiresAuth: true, ability: { action: 'read', subject: 'return_item' } } }
                ]
            },
            {
                path: 'inventory',
                meta: { title: 'inventory' },
                children: [
                    { path: 'item_transfer', name: 'ItemTransfer', component: ItemTransfer, meta: { title: 'itemTransfer', requiresAuth: true, ability: { action: 'read', subject: 'item_transfer' } } },
                    { path: 'item_usage', name: 'ItemUsage', component: ItemUsage, meta: { title: 'itemUsage', requiresAuth: true, ability: { action: 'read', subject: 'item_usage' } } },
                    { path: 'stock_opname', name: 'StockOpname', component: StockOpname, meta: { title: 'stockOpname', requiresAuth: true, ability: { action: 'read', subject: 'stock_opname' } } },
                    { path: 'stock_adjustment', name: 'StockAdjustment', component: StockAdjustment, meta: { title: 'stockAdjustment', requiresAuth: true, ability: { action: 'read', subject: 'stock_adjustment' } } },
                ]
            },
            {
                path: 'warehouse_cash',
                meta: { title: 'warehouseCash' },
                children: [
                    { path: 'warehouse_cash_request', name: 'WarehouseCashRequest', component: WarehouseCashRequest, meta: { title: 'warehouseCashRequest', requiresAuth: true, ability: { action: 'read', subject: 'warehouse_cash_request' } } },
                    { path: 'cash_payment', name: 'CashPayment', component: CashPayment, meta: { title: 'cashPayment', requiresAuth: true, ability: { action: 'read', subject: 'cash_payment' } } },
                    { path: 'cash_purchase', name: 'CashPurchase', component: CashPurchase, meta: { title: 'cashPurchase', requiresAuth: true, ability: { action: 'read', subject: 'cash_purchase' } } },
                ]
            },
            {
                path: 'settings',
                meta: { title: 'settings' },
                children: [
                    { path: 'item_request', name: 'SettingApproverItemRequest', component: SettingApproverItemRequest, meta: { title: 'settingApproverItemRequest', requiresAuth: true, ability: { action: 'read', subject: 'setting_approver_item_request' } } },
                    { path: 'purchase_order_approval', name: 'SettingPurchaseOrderApproval', component: SettingPurchaseOrderApproval, meta: { title: 'settingPurchaseOrderApprover', requiresAuth: true, ability: { action: 'read', subject: 'setting_po_approval' } } },
                    { path: 'dpp_formula', name: 'SettingDppFormula', component: SettingDppFormula, meta: { title: 'settingDppFormula', requiresAuth: true, ability: { action: 'read', subject: 'setting_dpp_formula' } } }
                ]
            },
            {
                path: 'reports',
                meta: { title: 'reports' },
                children: [
                    { path: 'stock_usage', name: 'StockUsageReport', component: StockUsageReport, meta: { title: 'stockUsageReport', requiresAuth: true, ability: { action: 'read', subject: 'stock_usage_report' } } },
                    { path: 'stock_adjustment', name: 'StockAdjustmentReport', component: StockAdjustmentReport, meta: { title: 'stockAdjustmentReport', requiresAuth: true, ability: { action: 'read', subject: 'stock_adjustment_report' } } },
                    { path: 'item_purchase', name: 'ItemPurchaseReport', component: ItemPurchaseReport, meta: { title: 'itemPurchaseReport', requiresAuth: true, ability: { action: 'read', subject: 'item_purchase_report' } } },
                    { path: 'item_receipt', name: 'ItemReceiptReport', component: ItemReceiptReport, meta: { title: 'itemReceiptReport', requiresAuth: true, ability: { action: 'read', subject: 'item_receipt_report' } } },
                    { path: 'return_item', name: 'ReturnItemReport', component: ReturnItemReport, meta: { title: 'returnItemReport', requiresAuth: true, ability: { action: 'read', subject: 'return_item_report' } } },
                    { path: 'life_time', name: 'LifeTimeReport', component: LifeTimeReport, meta: { title: 'lifeTimeReport', requiresAuth: true, ability: { action: 'read', subject: 'life_time_report' } } },
                    { path: 'demand_rate', name: 'DemandRateReport', component: DemandRateReport, meta: { title: 'demandRateReport', requiresAuth: true, ability: { action: 'read', subject: 'demand_rate_report' } } },
                    { path: 'lead_time', name: 'LeadTimeReport', component: LeadTimeReport, meta: { title: 'leadTimeReport', requiresAuth: true, ability: { action: 'read', subject: 'lead_time_report' } } },
                ]
            },
            {
                path: 'management',
                meta: { title: 'managementAccess' },
                children: [
                    { path: 'users', name: 'UserManagement', component: UserManagement, meta: { title: 'userManagement', requiresAuth: true, ability: { action: 'read', subject: 'user_management' } } },
                    { path: 'roles', name: 'RoleManagement', component: RoleManagement, meta: { title: 'roleManagement', requiresAuth: true, ability: { action: 'read', subject: 'role_management' } } },
                ]
            }
        ]
    },
    {
        path: '/forbidden',
        name: 'Forbidden',
        component: Forbidden,
    }
]

export const router = createRouter({
    history: createWebHistory(),
    routes
})

router.beforeEach(async (to, from, next) => {
    const auth = useAuthStore()

    // Pastikan user ter-hydrate sebelum evaluasi ability — hindari race pada
    // reload / deep link dimana permission belum di-sync dari backend.
    if (to.meta.requiresAuth && !auth.user) {
        try {
            await auth.fetchMe()
        } catch {
            return next({ name: 'Signin' })
        }
    }

    // Guest-only route (mis. Signin) — cegah user yang sudah login masuk.
    // WAJIB pakai `return` supaya tidak lanjut ke `next()` bawah (multiple
    // next() calls menyebabkan Vue Router abort navigasi secara intermiten).
    if (to.meta.guest && auth.isAuthenticated) {
        return next('/')
    }

    if (
        to.meta.ability
        && typeof to.meta.ability === 'object'
        && 'action' in to.meta.ability
        && 'subject' in to.meta.ability
    ) {
        const { action, subject } = to.meta.ability as { action: string; subject: string }
        if (!ability.can('manage', 'all') && !ability.can(action, subject)) {
            return next('/forbidden')
        }
    }

    return next()
})