<script lang="ts" setup>
import { computed, ref } from 'vue'
import type { DataTableHeader } from 'vuetify'
import BaseSearchInput from '@/components/base/BaseSearchInput.vue'
import BaseRefreshButton from '@/components/base/BaseRefreshButton.vue'
import ExportDialog, { type ExportPayload, type ExportStatusOption } from '@/modules/reports/_shared/components/ExportDialog.vue'
import { itemPurchaseColumns } from '@/modules/reports/_shared/reportColumns'
import { useItemPurchaseReportStore } from '@/stores/item_purchase_report'
import { useTranslate } from '@/composables/useTranslate'
import { useAbility } from '@/composables/useAbility'
import { formatDate } from '@/utils/date'
import MaterialSymbolsDownloadRounded from '~icons/material-symbols/download-rounded'

const t = useTranslate()
const store = useItemPurchaseReportStore()
const { can } = useAbility()

const itemPerPageOptions = [
    { title: '5', value: 5 }, { title: '10', value: 10 },
    { title: '25', value: 25 }, { title: '50', value: 50 }, { title: '100', value: 100 },
]

const options = ref<TableParams>({
    page: 1, itemsPerPage: 10,
    sortBy: [{ key: 'po_date', order: 'desc' }], search: '',
})

const headers = computed<DataTableHeader[]>(() => [
    { title: t('poNumber'), key: 'po_number', align: 'start', nowrap: true },
    { title: t('poDate'), key: 'po_date', align: 'start', nowrap: true },
    { title: t('item'), key: 'item_name', align: 'start', nowrap: true },
    { title: t('unit'), key: 'unit', align: 'start', nowrap: true },
    { title: t('supplier'), key: 'supplier_name', align: 'start', nowrap: true },
    { title: t('qty'), key: 'qty', align: 'end', nowrap: true, sortable: false },
    { title: t('price'), key: 'price', align: 'end', nowrap: true, sortable: false },
    { title: t('total'), key: 'total', align: 'end', nowrap: true, sortable: false },
    { title: t('status'), key: 'status', align: 'start', nowrap: true },
    { title: t('createdBy'), key: 'created_by_name', align: 'start', nowrap: true },
])

const statusOptions = computed<ExportStatusOption[]>(() => [
    { title: t('all'), value: null },
    { title: 'Waiting Approval', value: 'Waiting Approval' },
    { title: 'Approved', value: 'Approved' },
    { title: 'Rejected', value: 'Rejected' },
])

const statusColor = (status: string) => {
    switch ((status ?? '').toLowerCase()) {
        case 'waiting approval': return 'warning'
        case 'approved':         return 'success'
        case 'rejected':         return 'red'
        default:                 return 'grey'
    }
}

const loadData = () => store.fetch({
    page: options.value.page,
    per_page: options.value.itemsPerPage,
    search: options.value.search || null,
})

const exportDialog = ref(false)
const canExport = computed(() => can('export', 'item_purchase_report') || can('manage', 'all'))
const handleExportSubmit = async (payload: ExportPayload) => {
    await store.export(payload)
    if (!store.error) exportDialog.value = false
}

const money = (n: number) => Number(n).toLocaleString('id-ID', { maximumFractionDigits: 2 })
</script>

<template>
    <v-card>
        <v-card-title class="d-flex flex-column flex-sm-row-reverse justify-space-between align-sm-center">
            <v-btn v-if="canExport" color="success" variant="flat"
                :prepend-icon="MaterialSymbolsDownloadRounded"
                :loading="store.exporting" :disabled="store.loading"
                @click="exportDialog = true">{{ t('exportExcel') }}</v-btn>
            <div class="d-flex align-center">
                <base-search-input v-model="options.search" :loading="store.loading" @update:model-value="loadData" />
                <base-refresh-button :loading="store.loading" :disabled="store.loading" @click="loadData" />
            </div>
        </v-card-title>

        <v-card-text>
            <v-data-table-server
                v-model:options="options"
                :loading="store.loading" :headers="headers" :items="store.data"
                :items-length="store.total" :items-per-page="options.itemsPerPage"
                :items-per-page-text="t('itemsPerPage')" :items-per-page-options="itemPerPageOptions"
                @update:options="loadData"
            >
                <template #item.po_date="{ item }">{{ formatDate(item.po_date as string) }}</template>
                <template #item.qty="{ item }">{{ money(item.qty) }}</template>
                <template #item.price="{ item }">{{ money(item.price) }}</template>
                <template #item.total="{ item }">{{ money(item.total) }}</template>
                <template #item.status="{ item }">
                    <v-chip :color="statusColor(item.status)" variant="outlined" size="small">{{ item.status }}</v-chip>
                </template>
            </v-data-table-server>
        </v-card-text>
    </v-card>

    <export-dialog
        v-if="exportDialog" v-model="exportDialog"
        :loading="store.exporting" :status-options="statusOptions"
        :columns="itemPurchaseColumns"
        @submit="handleExportSubmit" @close="exportDialog = false"
    />
</template>
