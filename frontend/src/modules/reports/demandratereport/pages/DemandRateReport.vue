<script lang="ts" setup>
import { computed, ref } from 'vue'
import type { DataTableHeader } from 'vuetify'
import BaseSearchInput from '@/components/base/BaseSearchInput.vue'
import BaseRefreshButton from '@/components/base/BaseRefreshButton.vue'
import ExportDialog, { type ExportPayload } from '@/modules/reports/_shared/components/ExportDialog.vue'
import { demandRateColumns } from '@/modules/reports/_shared/reportColumns'
import { useDemandRateReportStore } from '@/stores/demand_rate_report'
import { useTranslate } from '@/composables/useTranslate'
import { useAbility } from '@/composables/useAbility'
import MaterialSymbolsDownloadRounded from '~icons/material-symbols/download-rounded'

const t = useTranslate()
const store = useDemandRateReportStore()
const { can } = useAbility()

const itemPerPageOptions = [
    { title: '10', value: 10 }, { title: '25', value: 25 },
    { title: '50', value: 50 }, { title: '100', value: 100 },
]

const options = ref<TableParams>({
    page: 1, itemsPerPage: 10,
    sortBy: [{ key: 'total_requests', order: 'desc' }], search: '',
})

const headers = computed<DataTableHeader[]>(() => [
    { title: t('partNumber'), key: 'part_number', align: 'start', nowrap: true },
    { title: t('itemName'), key: 'item_name', align: 'start', nowrap: true },
    { title: t('unit'), key: 'unit', align: 'start', nowrap: true },
    { title: t('totalRequests'), key: 'total_requests', align: 'end', nowrap: true, sortable: false },
    { title: t('distinctRequests'), key: 'distinct_requests', align: 'end', nowrap: true, sortable: false },
    { title: t('totalQty'), key: 'total_qty', align: 'end', nowrap: true, sortable: false },
])

const loadData = () => store.fetch({
    page: options.value.page, per_page: options.value.itemsPerPage,
    search: options.value.search || null,
})

const exportDialog = ref(false)
const canExport = computed(() => can('export', 'demand_rate_report') || can('manage', 'all'))
const handleExportSubmit = async (payload: ExportPayload) => {
    await store.export(payload)
    if (!store.error) exportDialog.value = false
}
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
                <template #item.total_requests="{ item }">{{ Number(item.total_requests).toLocaleString('id-ID') }}</template>
                <template #item.distinct_requests="{ item }">{{ Number(item.distinct_requests).toLocaleString('id-ID') }}</template>
                <template #item.total_qty="{ item }">{{ Number(item.total_qty).toLocaleString('id-ID') }}</template>
            </v-data-table-server>
        </v-card-text>
    </v-card>

    <export-dialog
        v-if="exportDialog" v-model="exportDialog"
        :loading="store.exporting"
        :columns="demandRateColumns"
        @submit="handleExportSubmit" @close="exportDialog = false"
    />
</template>
