<script lang="ts" setup>
import { computed, ref } from 'vue'
import type { DataTableHeader } from 'vuetify'
// Components
import BaseSearchInput from '@/components/base/BaseSearchInput.vue'
import BaseRefreshButton from '@/components/base/BaseRefreshButton.vue'
import SettingMinWarehouseCashForm from '@/modules/settingminwarehousecash/components/SettingMinWarehouseCashForm.vue'
// Stores
import { useSettingMinWarehouseCashStore } from '@/stores/setting_min_warehouse_cash'
// Composables
import { useTranslate } from '@/composables/useTranslate'
import { useAbility } from '@/composables/useAbility'
// Utils
import { formatRupiah } from '@/utils/currency'
// Icons
import MdiPencilOutline from '~icons/mdi/pencil-outline'
import MdiCashLock from '~icons/mdi/cash-lock'
import MdiCashCheck from '~icons/mdi/cash-check'
import MdiAlertCircleOutline from '~icons/mdi/alert-circle-outline'
import MdiMinusCircleOutline from '~icons/mdi/minus-circle-outline'

const t = useTranslate()
const store = useSettingMinWarehouseCashStore()
const { can } = useAbility()

const formDialog = ref<boolean>(false)
const selectedWarehouse = ref<SettingMinWarehouseCashList | null>(null)

const itemPerPageOptions = [
    { title: '5', value: 5 },
    { title: '10', value: 10 },
    { title: '25', value: 25 },
    { title: '50', value: 50 },
    { title: '100', value: 100 },
    { title: 'All', value: -1 },
]

const options = ref<TableParams>({
    page: 1,
    itemsPerPage: 10,
    sortBy: [{ key: 'name', order: 'asc' }],
    search: '',
})

const headers = computed<DataTableHeader[]>(() => [
    { title: t('actions'), key: 'action', align: 'center', width: '20', sortable: false },
    { title: t('warehouseName'), key: 'name', align: 'start' },
    { title: t('address'), key: 'address', align: 'start' },
    { title: t('settingMinCashCurrentBalance'), key: 'cash_balance', align: 'end', width: '180' },
    { title: t('settingMinCashThreshold'), key: 'min_cash', align: 'end', width: '180' },
    { title: t('status'), key: 'status', align: 'center', width: '140', sortable: false },
])

const formTitle = computed(() => t('settingMinCashEditTitle'))

// ─── KPI summary di header (counts) ─────────────────────────────────────────
const summary = computed(() => {
    const items = store.data ?? []
    const total = items.length
    const configured = items.filter((i) => i.min_cash !== null).length
    const belowMin = items.filter((i) => i.is_below_min).length
    const noThreshold = total - configured
    return { total, configured, belowMin, noThreshold }
})

const loadData = () => store.fetch(options.value)

const handleOpenDialog = (item: SettingMinWarehouseCashList) => {
    selectedWarehouse.value = item
    formDialog.value = true
}

const handleSubmit = (newMinCash: number | null) => {
    if (!selectedWarehouse.value) return
    store.update(selectedWarehouse.value.uid, { min_cash: newMinCash })
    handleClose()
}

const handleClose = () => {
    formDialog.value = false
    selectedWarehouse.value = null
}
</script>

<template>
    <v-card>
        <v-card-title class="d-flex flex-column flex-sm-row-reverse justify-space-between align-sm-center pa-3 pa-sm-4 pb-2">
            <div class="d-flex align-center">
                <base-search-input
                    :loading="store.loading"
                    v-model="options.search"
                    @update:model-value="loadData"
                />
                <base-refresh-button
                    :loading="store.loading"
                    :disabled="store.loading"
                    @click="loadData"
                />
            </div>
        </v-card-title>

        <!-- ════ KPI Summary Cards ════ -->
        <v-card-text class="pt-1 pb-2">
            <v-row dense>
                <v-col cols="6" sm="3">
                    <div class="kpi kpi--neutral">
                        <div class="kpi-label">{{ t('settingMinCashTotalWarehouses') }}</div>
                        <div class="kpi-value">{{ summary.total }}</div>
                    </div>
                </v-col>
                <v-col cols="6" sm="3">
                    <div class="kpi kpi--success">
                        <div class="kpi-label">
                            <v-icon :icon="MdiCashCheck" size="14" />
                            {{ t('settingMinCashConfigured') }}
                        </div>
                        <div class="kpi-value">{{ summary.configured }}</div>
                    </div>
                </v-col>
                <v-col cols="6" sm="3">
                    <div class="kpi kpi--warning">
                        <div class="kpi-label">
                            <v-icon :icon="MdiMinusCircleOutline" size="14" />
                            {{ t('settingMinCashNoThreshold') }}
                        </div>
                        <div class="kpi-value">{{ summary.noThreshold }}</div>
                    </div>
                </v-col>
                <v-col cols="6" sm="3">
                    <div class="kpi kpi--error">
                        <div class="kpi-label">
                            <v-icon :icon="MdiAlertCircleOutline" size="14" />
                            {{ t('settingMinCashBelowCount') }}
                        </div>
                        <div class="kpi-value">{{ summary.belowMin }}</div>
                    </div>
                </v-col>
            </v-row>
        </v-card-text>

        <v-card-text class="pt-2">
            <v-data-table-server
                v-model:options="options"
                :loading="store.loading"
                :headers="headers"
                :items="store.data"
                :items-length="store.total"
                :items-per-page="options.itemsPerPage"
                :items-per-page-text="t('itemsPerPage')"
                :items-per-page-options="itemPerPageOptions"
                @update:options="loadData"
            >
                <template v-slot:item.address="{ item }">
                    <span v-if="item.address" class="text-body-2">{{ item.address }}</span>
                    <span v-else class="text-medium-emphasis">-</span>
                </template>

                <template v-slot:item.cash_balance="{ item }">
                    <span class="font-mono">{{ formatRupiah(item.cash_balance) }}</span>
                </template>

                <template v-slot:item.min_cash="{ item }">
                    <span v-if="item.min_cash !== null" class="font-mono">
                        {{ formatRupiah(item.min_cash) }}
                    </span>
                    <span v-else class="text-medium-emphasis text-caption">
                        {{ t('settingMinCashNotSet') }}
                    </span>
                </template>

                <template v-slot:item.status="{ item }">
                    <v-chip
                        v-if="item.min_cash === null"
                        size="x-small"
                        variant="tonal"
                        color="grey"
                        class="font-weight-bold"
                    >
                        {{ t('settingMinCashUnconfigured') }}
                    </v-chip>
                    <v-chip
                        v-else-if="item.is_below_min"
                        size="x-small"
                        variant="tonal"
                        color="error"
                        class="font-weight-bold"
                    >
                        <v-icon :icon="MdiAlertCircleOutline" size="12" class="me-1" />
                        {{ t('settingMinCashBelow') }}
                    </v-chip>
                    <v-chip
                        v-else
                        size="x-small"
                        variant="tonal"
                        color="success"
                        class="font-weight-bold"
                    >
                        <v-icon :icon="MdiCashCheck" size="12" class="me-1" />
                        {{ t('settingMinCashOk') }}
                    </v-chip>
                </template>

                <template v-slot:item.action="{ item }">
                    <v-btn
                        v-if="can('update', 'setting_min_warehouse_cash')"
                        :icon="MdiPencilOutline"
                        variant="tonal"
                        density="compact"
                        size="small"
                        color="primary"
                        :aria-label="t('settingMinCashEditTitle')"
                        @click="handleOpenDialog(item)"
                    />
                </template>
            </v-data-table-server>
        </v-card-text>
    </v-card>

    <setting-min-warehouse-cash-form
        v-model="formDialog"
        :title="formTitle"
        :warehouse="selectedWarehouse"
        :saving="store.loading"
        @submit="handleSubmit"
        @close="handleClose"
    />
</template>

<style scoped>
/* ════ KPI cards ════ */
.kpi {
    border-radius: 10px;
    padding: 10px 12px;
    border: 1px solid;
    background: rgb(var(--v-theme-surface));
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.kpi:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
}
.kpi-label {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: rgba(var(--v-theme-on-surface), 0.65);
    display: flex;
    align-items: center;
    gap: 4px;
}
.kpi-value {
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1.1;
    margin-top: 4px;
    color: rgba(var(--v-theme-on-surface), 0.95);
}

.kpi--neutral { border-color: rgba(var(--v-theme-on-surface), 0.12); }
.kpi--success {
    border-color: rgba(var(--v-theme-success), 0.4);
    background: color-mix(in srgb, rgb(var(--v-theme-success)) 5%, rgb(var(--v-theme-surface)));
}
.kpi--warning {
    border-color: rgba(var(--v-theme-warning), 0.4);
    background: color-mix(in srgb, rgb(var(--v-theme-warning)) 5%, rgb(var(--v-theme-surface)));
}
.kpi--error {
    border-color: rgba(var(--v-theme-error), 0.4);
    background: color-mix(in srgb, rgb(var(--v-theme-error)) 5%, rgb(var(--v-theme-surface)));
}

.font-mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
}

@media (max-width: 600px) {
    .kpi { padding: 8px 10px; }
    .kpi-value { font-size: 1.25rem; }
}
</style>
