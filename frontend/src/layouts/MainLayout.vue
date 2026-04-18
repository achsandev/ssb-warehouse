<script lang="ts" setup>
import { onMounted, ref } from 'vue'
import { useDisplay } from 'vuetify'
// Responsive drawer state
const drawer = ref(true)
const { mdAndDown } = useDisplay()
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
// Import Unplugin Icons
import MaterialSymbolsTabGroupRounded from '~icons/material-symbols/tab-group-rounded'
import MaterialSymbolsSpaceDashboardRounded from '~icons/material-symbols/space-dashboard-rounded'
import MaterialSymbolsDataUsageRounded from '~icons/material-symbols/data-usage-rounded'
import MaterialSymbolsDatasetRounded from '~icons/material-symbols/dataset-rounded';
import MaterialSymbolsPersonApronRounded from '~icons/material-symbols/person-apron-rounded'
import MaterialSymbolsLogoutRounded from '~icons/material-symbols/logout-rounded'
import MaterialSymbolsBrandingWatermarkRounded from '~icons/material-symbols/branding-watermark-rounded'
import MaterialSymbolsCategorySearchRounded from '~icons/material-symbols/category-search-rounded'
import MaterialSymbolsCommunities from '~icons/material-symbols/communities'
import MaterialSymbolsMoveSelectionUpRounded from '~icons/material-symbols/move-selection-up-rounded'
import MaterialSymbolsSourceNotesRounded from '~icons/material-symbols/source-notes-rounded'
import MaterialSymbolsMoreDownRounded from '~icons/material-symbols/more-down-rounded'
import MaterialSymbolsPaymentCard from '~icons/material-symbols/payment-card'
import MaterialSymbolsAttachMoneyRounded from '~icons/material-symbols/attach-money-rounded'
import MaterialSymbolsHandshakeRounded from '~icons/material-symbols/handshake-rounded'
import MaterialSymbolsDeployedCode from '~icons/material-symbols/deployed-code'
import MaterialSymbolsHomePin from '~icons/material-symbols/home-pin'
import MaterialSymbolsMoneyBagRounded from '~icons/material-symbols/money-bag-rounded'
import MaterialSymbolsQuickReorderRounded from '~icons/material-symbols/quick-reorder-rounded'
import MaterialSymbolsDeliveryTruckSpeedRounded from '~icons/material-symbols/delivery-truck-speed-rounded'
import MaterialSymbolsDeployedCodeUpdateRounded from '~icons/material-symbols/deployed-code-update-rounded'
import MaterialSymbolsModelingRounded from '~icons/material-symbols/modeling-rounded'
import MaterialSymbolsPictureInPictureMediumRounded from '~icons/material-symbols/picture-in-picture-medium-rounded'
import MaterialSymbolsForkliftRounded from '~icons/material-symbols/forklift-rounded'
import MaterialSymbolsMovingRounded from '~icons/material-symbols/moving-rounded'
import MaterialSymbolsTagRounded from '~icons/material-symbols/tag-rounded'
import MaterialSymbolsCropRotateRounded from '~icons/material-symbols/crop-rotate-rounded'
import MaterialSymbolsDeployedCodeAlertRounded from '~icons/material-symbols/deployed-code-alert-rounded'
import MaterialSymbolsMonitoringRounded from '~icons/material-symbols/monitoring-rounded'
import MaterialSymbolsLabProfileRounded from '~icons/material-symbols/lab-profile-rounded'
import MaterialSymbolsInventory2Rounded from '~icons/material-symbols/inventory-2-rounded'
import MaterialSymbolsTuneRounded from '~icons/material-symbols/tune-rounded'
import MaterialSymbolsShoppingCartRounded from '~icons/material-symbols/shopping-cart-rounded'
import MaterialSymbolsArchiveRounded from '~icons/material-symbols/archive-rounded'
import MaterialSymbolsAssignmentReturnRounded from '~icons/material-symbols/assignment-return-rounded'
import MaterialSymbolsHourglassTopRounded from '~icons/material-symbols/hourglass-top-rounded'
import MaterialSymbolsTrendingUpRounded from '~icons/material-symbols/trending-up-rounded'
import MaterialSymbolsScheduleRounded from '~icons/material-symbols/schedule-rounded'
import MaterialSymbolsSettings from '~icons/material-symbols/settings'
import MaterialSymbolsLightModeRounded from '~icons/material-symbols/light-mode-rounded'
import MaterialSymbolsDarkModeRounded from '~icons/material-symbols/dark-mode-rounded'
import MaterialSymbolsBookmarksRounded from '~icons/material-symbols/bookmarks-rounded'
import MaterialSymbolsTimelapseRounded from '~icons/material-symbols/timelapse-rounded'
import HugeiconsMenu03 from '~icons/hugeicons/menu-03'
import TdesignUserSettingFilled from '~icons/tdesign/user-setting-filled'
import IcRoundSettingsAccessibility from '~icons/ic/round-settings-accessibility'
import IcRoundAdminPanelSettings from '~icons/ic/round-admin-panel-settings'
import CarbonDeliverySettings from '~icons/carbon/delivery-settings'
import HugeiconsDashboardSquareSetting from '~icons/hugeicons/dashboard-square-setting'
import MaterialSymbolsFunction from '~icons/material-symbols/function'
// Import Stores
import { useMessageStore } from '@/stores/message'
import { useAuthStore } from '@/stores/auth'
import { useIdleTimeout } from '@/composables/useIdleTimeout'
import { useThemeStore } from '@/stores/theme'
// Import Components
import BaseLanguageButton from '@/components/base/BaseLanguageButton.vue'
// Import Assets
import SsbLogo from '@/assets/logo/ssb_logo.png'
// Import Composables
import { useTranslate } from '@/composables/useTranslate'
import { useBreadcrumbs } from '@/composables/useBreadcrumbs'
import { useWarehouseStore } from '@/stores/warehouse'
import { ability, useAbility } from '@/composables/useAbility'

const t = useTranslate()

const store = useAuthStore()
const message = useMessageStore()
const themeStore = useThemeStore()
const warehouseStore = useWarehouseStore()

// Track user activity — auto-logout setelah 2 jam idle
useIdleTimeout()

onMounted(async () => {
    if (!store.user && localStorage.getItem('auth')) {
        await store.fetchMe()
    }
})

const { can } = useAbility()

// Menu List
const items = computed(() => [
    {
        title: t('dashboard'),
        props: { link: true, to: '/dashboard', rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsSpaceDashboardRounded, class: 'mb-1' },
        show: can('read', 'dashboard')
    },
    {
        title: t('commonData'),
        props: { link: true, rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsDataUsageRounded, class: 'mb-1' },
        children: [
            { title: t('itemCategories'), props: { link: true, to: '/common/item_categories', rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsCategorySearchRounded, class: 'mb-1' }, show: can('read', 'category') },
            { title: t('units'), props: { link: true, to: '/common/units', rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsCommunities, class: 'mb-1' }, show: can('read', 'unit') },
            { title: t('movementCategories'), props: { link: true, to: '/common/movement_categories', rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsMoveSelectionUpRounded, class: 'mb-1' }, show: can('read', 'movement_category') },
            { title: t('valuationMethods'), props: { link: true, to: '/common/valuation_methods', rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsSourceNotesRounded, class: 'mb-1' }, show: can('read', 'valuation_method') },
            { title: t('requestTypes'), props: { link: true, to: '/common/request_types', rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsMoreDownRounded, class: 'mb-1' }, show: can('read', 'request_type') },
            { title: t('paymentMethods'), props: { link: true, to: '/common/payment_methods', rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsPaymentCard, class: 'mb-1' }, show: can('read', 'payment_method') },
            { title: t('paymentDuration'), props: { link: true, to: '/common/payment_duration', rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsTimelapseRounded, class: 'mb-1' }, show: can('read', 'payment_duration') },
            { title: t('taxTypes'), props: { link: true, to: '/common/tax_types', rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsAttachMoneyRounded, class: 'mb-1' }, show: can('read', 'tax_type') },
        ]
    },
    {
        title: t('masterData'),
        props: { link: true, rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsDatasetRounded, class: 'mb-1' },
        children: [
            { title: t('materialGroups'), props: { link: true, to: '/master/material_groups', rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsTabGroupRounded, class: 'mb-1' }, show: can('read', 'material_group') },
            { title: t('brands'), props: { link: true, to: '/master/brands', rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsBrandingWatermarkRounded, class: 'mb-1' }, show: can('read', 'brands') },
            { title: t('usageUnits'), props: { link: true, to: '/master/usage_units', rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsBookmarksRounded, class: 'mb-1' }, show: can('read', 'usage_units') },
            { title: t('suppliers'), props: { link: true, to: '/master/suppliers', rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsHandshakeRounded, class: 'mb-1' }, show: can('read', 'supplier') },
            { title: t('storageLocations'), props: { link: true, to: '/master/storage_locations', rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsHomePin, class: 'mb-1' }, show: can('read', 'warehouse') },
            { title: t('itemsServices'), props: { link: true, to: '/master/items_services', rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsDeployedCode, class: 'mb-1' }, show: can('read', 'items') },
        ]
    },
    {
        title: t('purchase'),
        props: { link: true, rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsMoneyBagRounded, class: 'mb-1' },
        children: [
            { title: t('itemRequest'), props: { link: true, to: '/purchase/item_request', rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsQuickReorderRounded, class: 'mb-1' }, show: can('read', 'item_request') },
            { title: t('purchaseOrder'), props: { link: true, to: '/purchase/purchase_order', rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsDeliveryTruckSpeedRounded, class: 'mb-1' }, show: can('read', 'purchase_order') },
            { title: t('receiveItem'), props: { link: true, to: '/purchase/receive_item', rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsDeployedCodeUpdateRounded, class: 'mb-1' }, show: can('read', 'receive_item') || can('approve', 'receive_item') },
            { title: t('returnItem'), props: { link: true, to: '/purchase/return_item', rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsModelingRounded, class: 'mb-1' }, show: can('read', 'return_item') }
        ]
    },
    {
        title: t('inventory'),
        props: { link: true, rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsPictureInPictureMediumRounded, class: 'mb-1' },
        children: [
            { title: t('itemTransfer'), props: { link: true, to: '/inventory/item_transfer', rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsForkliftRounded, class: 'mb-1' }, show: can('read', 'item_transfer') },
            { title: t('itemUsage'), props: { link: true, to: '/inventory/item_usage', rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsMovingRounded, class: 'mb-1' }, show: can('read', 'item_usage') },
            { title: t('stockOpname'), props: { link: true, to: '/inventory/stock_opname', rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsTagRounded, class: 'mb-1' }, show: can('read', 'stock_opname') },
            { title: t('stockAdjustment'), props: { link: true, to: '/inventory/stock_adjustment', rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsCropRotateRounded, class: 'mb-1' }, show: can('read', 'stock_adjustment') },
            { title: t('deadStockManagement'), props: { link: true, rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsDeployedCodeAlertRounded, class: 'mb-1' }, show: can('read', 'dead_stock_management') },
        ]
    },
    {
        title: t('warehouseCash'),
        props: { link: true, rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsMonitoringRounded, class: 'mb-1' },
        children: [
            { title: t('warehouseCashRequest'), props: { link: true, to: '/warehouse_cash/warehouse_cash_request', rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsAttachMoneyRounded, class: 'mb-1' }, show: can('read', 'warehouse_cash_request') },
            { title: t('cashPayment'), props: { link: true, to: '/warehouse_cash/cash_payment', rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsAttachMoneyRounded, class: 'mb-1' }, show: can('read', 'cash_payment') },
            { title: t('cashPurchase'), props: { link: true, to: '/warehouse_cash/cash_purchase', rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsAttachMoneyRounded, class: 'mb-1' }, show: can('read', 'cash_purchase') }
        ]
    },
    {
        title: t('reports'),
        props: { link: true, rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsLabProfileRounded, class: 'mb-1' },
        children: [
            { title: t('stockUsageReport'), props: { link: true, to: '/reports/stock_usage', rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsInventory2Rounded, class: 'mb-1' }, show: can('read', 'stock_usage_report') },
            { title: t('stockAdjustmentReport'), props: { link: true, to: '/reports/stock_adjustment', rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsTuneRounded, class: 'mb-1' }, show: can('read', 'stock_adjustment_report') },
            { title: t('itemPurchaseReport'), props: { link: true, to: '/reports/item_purchase', rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsShoppingCartRounded, class: 'mb-1' }, show: can('read', 'item_purchase_report') },
            { title: t('itemReceiptReport'), props: { link: true, to: '/reports/item_receipt', rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsArchiveRounded, class: 'mb-1' }, show: can('read', 'item_receipt_report') },
            { title: t('returnItemReport'), props: { link: true, to: '/reports/return_item', rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsAssignmentReturnRounded, class: 'mb-1' }, show: can('read', 'return_item_report') },
            { title: t('lifeTimeReport'), props: { link: true, to: '/reports/life_time', rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsHourglassTopRounded, class: 'mb-1' }, show: can('read', 'life_time_report') },
            { title: t('demandRateReport'), props: { link: true, to: '/reports/demand_rate', rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsTrendingUpRounded, class: 'mb-1' }, show: can('read', 'demand_rate_report') },
            { title: t('leadTimeReport'), props: { link: true, to: '/reports/lead_time', rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsScheduleRounded, class: 'mb-1' }, show: can('read', 'lead_time_report') }
        ]
    },
    {
        title: t('settings'),
        props: { link: true, rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsSettings, class: 'mb-1' },
        children: [
            { title: t('itemRequest'), props: { link: true, to: '/settings/item_request', rounded: 'xl', density: 'compact', prependIcon: HugeiconsDashboardSquareSetting, class: 'mb-1' }, show: can('read', 'setting_approver_item_request') },
            { title: t('purchaseOrder'), props: { link: true, to: '/settings/purchase_order_approval', rounded: 'xl', density: 'compact', prependIcon: CarbonDeliverySettings, class: 'mb-1' }, show: can('read', 'setting_po_approval') },
            { title: t('settingDppFormula'), props: { link: true, to: '/settings/dpp_formula', rounded: 'xl', density: 'compact', prependIcon: MaterialSymbolsFunction, class: 'mb-1' }, show: can('read', 'setting_dpp_formula') }
        ]
    },
    {
        title: t('managementAccess'),
        props: { link: true, rounded: 'xl', density: 'compact', prependIcon: IcRoundAdminPanelSettings, class: 'mb-1' },
        children: [
            { title: t('userManagement'), props: { link: true, to: '/management/users', rounded: 'xl', density: 'compact', prependIcon: TdesignUserSettingFilled, class: 'mb-1' }, show: can('read', 'user_management') },
            { title: t('roleManagement'), props: { link: true, to: '/management/roles', rounded: 'xl', density: 'compact', prependIcon: IcRoundSettingsAccessibility, class: 'mb-1' }, show: can('read', 'role_management') }
        ]
    }
])

const filteredItems = computed(() => {
    // Always work with an array for Vuetify v-list
    const menuItems = Array.isArray(items.value) ? items.value : [];
    if (can('manage', 'all')) {
        return menuItems.map(item => {
            if (item.children) {
                return { ...item, children: item.children };
            }
            return item;
        });
    }
    // Otherwise, filter as before
    return menuItems
        .map(item => {
            if (item.children) {
                const children = item.children.filter(child => child.show !== false)
                if (!children.length) return null
                return { ...item, children }
            }
            if (item.show === false) return null
            return item
        }).filter(Boolean)
})

const { breadcrumbs } = useBreadcrumbs()

const handleChangeTheme = computed({
    // Switch reflect tema yang sedang aktif (resolved dari preferensi atau system).
    get: () => themeStore.resolvedTheme === 'dark',
    // Saat user toggle, simpan preferensi eksplisit (override 'system').
    set: (value: boolean) => {
        themeStore.setTheme(value ? 'dark' : 'light')
    }
})

const handleBreadcrumbClick = (item: any) => {
    if (item.to?.includes('/master/warehouse')) {
        warehouseStore.clearCurrentWarehouse()
    }
}

const handleSignOut = () => {
    store.signout()
}
</script>

<template>
    <v-navigation-drawer
        v-model="drawer"
        :temporary="mdAndDown"
        :permanent="!mdAndDown"
        floating
        :width="mdAndDown ? 280 : 350"
        class="py-4 px-4"
    >
        <div class="sidebar-logo d-flex align-center justify-center pb-4 mb-2">
            <img :src="SsbLogo" alt="SSB Logo" class="sidebar-logo__img" />
        </div>
        <v-divider class="mb-3" />
        <v-list :items="filteredItems" />
        <template v-slot:append>
            <v-list>
                <v-list-item
                    link
                    prepend-avatar="https://randomuser.me/api/portraits/men/85.jpg"
                    :title="store.user ? store.user.name : 'Guest'"
                    subtitle="202300669"
                    rounded="xl"
                >
                    <v-menu activator="parent">
                        <v-list rounded="lg" density="compact">
                            <v-list-item link :title="t('profile')" :prepend-icon="MaterialSymbolsPersonApronRounded" class="mb-1" />
                            <v-list-item link :title="t('signOut')" :prepend-icon="MaterialSymbolsLogoutRounded" class="text-red-darken-2" @click="handleSignOut" />
                        </v-list>
                    </v-menu>
                </v-list-item>
            </v-list>
        </template>
    </v-navigation-drawer>

    <v-main>
        <div class="px-3 px-sm-4 py-3 py-sm-4">
            <header class="app-header d-flex align-center ga-2 pt-1 pb-3 pb-sm-4 font-weight-bold text-caption">
                <v-btn
                    :icon="HugeiconsMenu03"
                    class="d-lg-none shrink-0"
                    @click="drawer = !drawer"
                    variant="text"
                    size="small"
                />
                <v-breadcrumbs
                    :items="breadcrumbs"
                    divider=">"
                    density="compact"
                    class="breadcrumbs-wrapper grow pa-0 overflow-hidden"
                >
                    <template #item="{ item }">
                        <router-link
                            v-if="item.to && !item.disabled"
                            class="breadcrumb-link text-decoration-none text-truncate"
                            :to="item.to"
                            @click="handleBreadcrumbClick(item)"
                        >
                            {{ item.title }}
                        </router-link>
                        <span v-else class="breadcrumb-current text-truncate">{{ item.title }}</span>
                    </template>
                </v-breadcrumbs>
                <div class="d-flex align-center ga-1 ga-sm-3 shrink-0 ms-auto">
                    <base-language-button />
                    <v-switch
                        :loading="themeStore.loading"
                        :disabled="themeStore.loading"
                        v-model="handleChangeTheme"
                        inset
                        glow
                        flat
                        hide-details
                        density="compact"
                        color="blue-accent-2"
                        :false-icon="MaterialSymbolsLightModeRounded"
                        :true-icon="MaterialSymbolsDarkModeRounded"
                    />
                </div>
            </header>
            <router-view />
            <v-snackbar-queue v-model="message.messages" location="top" />
            <v-footer class="d-flex flex-column justify-center align-center mt-8 mt-sm-10 px-3 px-sm-4 pt-4 pt-sm-6 pb-3 pb-sm-4 text-center">
                <div class="text-caption">&copy; {{ new Date().getFullYear() }} {{ t('wms') }} PT. SSB</div>
                <div class="text-caption">{{ t('developBy') }} Muhammad Noer Achsan Simba</div>
                <div class="text-caption">v 1.0.0</div>
            </v-footer>
        </div>
    </v-main>
</template>

<style scoped>
.app-header {
    min-height: 44px;
}

.sidebar-logo {
    padding-top: 4px;
}

.sidebar-logo__img {
    max-height: 36px;
    max-width: 100%;
    object-fit: contain;
}

.breadcrumb-link {
    color: rgba(var(--v-theme-on-surface), 0.7);
    transition: color 0.15s ease;
}

.breadcrumb-link:hover {
    color: rgb(var(--v-theme-primary));
}

.breadcrumb-current {
    color: rgb(var(--v-theme-on-surface));
    font-weight: 500;
}

.breadcrumbs-wrapper :deep(.v-breadcrumbs-item) {
    white-space: nowrap;
    min-width: 0;
}

/* Mobile: sembunyikan ancestor breadcrumb, tampilkan hanya current */
@media (max-width: 600px) {
    .breadcrumbs-wrapper :deep(.v-breadcrumbs-item):not(:last-child),
    .breadcrumbs-wrapper :deep(.v-breadcrumbs-divider) {
        display: none;
    }

    .breadcrumbs-wrapper :deep(.v-breadcrumbs-item:last-child) {
        max-width: 100%;
    }
}
</style>