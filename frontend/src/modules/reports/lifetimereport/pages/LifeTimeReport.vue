<script lang="ts" setup>
import { computed, ref } from 'vue'
import type { DataTableHeader } from 'vuetify'
import BaseSearchInput from '@/components/base/BaseSearchInput.vue'
import BaseRefreshButton from '@/components/base/BaseRefreshButton.vue'
import ExportDialog, { type ExportPayload } from '@/modules/reports/_shared/components/ExportDialog.vue'
import { lifeTimeColumns } from '@/modules/reports/_shared/reportColumns'
import { useLifeTimeReportStore } from '@/stores/life_time_report'
import { useTranslate } from '@/composables/useTranslate'
import { useAbility } from '@/composables/useAbility'
import { formatDate } from '@/utils/date'
import MaterialSymbolsDownloadRounded from '~icons/material-symbols/download-rounded'

const t = useTranslate()
const store = useLifeTimeReportStore()
const { can } = useAbility()

const itemPerPageOptions = [
    { title: '10', value: 10 }, { title: '25', value: 25 },
    { title: '50', value: 50 }, { title: '100', value: 100 },
]

const options = ref<TableParams>({
    page: 1, itemsPerPage: 10,
    sortBy: [{ key: 'days_in_stock', order: 'desc' }], search: '',
})

const headers = computed<DataTableHeader[]>(() => [
    { title: t('partNumber'), key: 'item_code', align: 'start', nowrap: true },
    { title: t('itemName'), key: 'item_name', align: 'start', nowrap: true },
    { title: t('unit'), key: 'unit', align: 'start', nowrap: true },
    { title: t('firstReceiptDate'), key: 'first_receipt_date', align: 'start', nowrap: true, sortable: false },
    { title: t('lastReceiptDate'), key: 'last_receipt_date', align: 'start', nowrap: true, sortable: false },
    { title: t('daysInStock'), key: 'days_in_stock', align: 'end', nowrap: true, sortable: false },
])

const loadData = () => store.fetch({
    page: options.value.page, per_page: options.value.itemsPerPage,
    search: options.value.search || null,
})

const exportDialog = ref(false)
const canExport = computed(() => can('export', 'life_time_report') || can('manage', 'all'))
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
                <template #item.first_receipt_date="{ item }">{{ formatDate(item.first_receipt_date as string) }}</template>
                <template #item.last_receipt_date="{ item }">{{ formatDate(item.last_receipt_date as string) }}</template>
                <template #item.days_in_stock="{ item }">
                    <v-chip variant="tonal" size="small" :color="item.days_in_stock > 180 ? 'red' : item.days_in_stock > 90 ? 'warning' : 'success'">
                        {{ item.days_in_stock }} {{ t('days') }}
                    </v-chip>
                </template>
            </v-data-table-server>
        </v-card-text>
    </v-card>

    <export-dialog
        v-if="exportDialog" v-model="exportDialog"
        :loading="store.exporting"
        :columns="lifeTimeColumns"
        @submit="handleExportSubmit" @close="exportDialog = false"
    />
</template>
