<script lang="ts" setup>
import { computed } from 'vue'
import type { DataTableHeader } from 'vuetify'
import { useTranslate } from '@/composables/useTranslate'
import { formatDate } from '@/utils/date'
import SystemUiconsClose from '~icons/system-uicons/close'

const model = defineModel<boolean>()

const props = defineProps<{
    item: StockAdjustmentList
}>()

const emit = defineEmits<{
    (e: 'close'): void
}>()

const t = useTranslate()

const headers = computed<DataTableHeader[]>(() => [
    { title: 'No',                 value: 'no',             align: 'center', width: '50px', sortable: false },
    { title: t('item'),            value: 'item_name',      align: 'start' },
    { title: t('unit'),            value: 'unit_symbol',    align: 'start' },
    { title: t('warehouse'),       value: 'warehouse_name', align: 'start' },
    { title: t('adjustmentQty'),   value: 'adjustment_qty', align: 'end' },
    { title: t('notes'),           value: 'notes',          align: 'start' },
])

const statusColor = (status: string): string => {
    switch ((status ?? '').toLowerCase()) {
        case 'waiting approval': return 'warning'
        case 'approved':         return 'success'
        case 'rejected':         return 'red'
        case 'revised':          return 'info'
        default:                 return 'grey'
    }
}

const handleClose = () => emit('close')
</script>

<template>
    <v-navigation-drawer v-model="model" location="right" width="800" temporary>
        <!-- Header -->
        <v-list-item class="pa-4" :title="t('information', { field: t('stockAdjustment') })">
            <template #append>
                <v-btn :icon="SystemUiconsClose" variant="text" size="small" @click="handleClose" />
            </template>
        </v-list-item>

        <v-divider />

        <div class="pa-4">
            <v-row dense class="mb-2">
                <v-col cols="6" sm="4" class="text-caption text-medium-emphasis">{{ t('adjustmentNumber') }}</v-col>
                <v-col cols="6" sm="8" class="text-body-2 font-weight-medium">{{ item.adjustment_number }}</v-col>

                <v-col cols="6" sm="4" class="text-caption text-medium-emphasis">{{ t('adjustmentDate') }}</v-col>
                <v-col cols="6" sm="8" class="text-body-2">{{ formatDate(item.adjustment_date as string) }}</v-col>

                <v-col cols="6" sm="4" class="text-caption text-medium-emphasis">{{ t('opnameReference') }}</v-col>
                <v-col cols="6" sm="8" class="text-body-2">{{ item.stock_opname_number || '-' }}</v-col>

                <v-col cols="6" sm="4" class="text-caption text-medium-emphasis">{{ t('status') }}</v-col>
                <v-col cols="6" sm="8">
                    <v-chip size="small" :color="statusColor(item.status)" variant="outlined">
                        {{ item.status }}
                    </v-chip>
                </v-col>

                <v-col cols="6" sm="4" class="text-caption text-medium-emphasis">{{ t('notes') }}</v-col>
                <v-col cols="6" sm="8" class="text-body-2">{{ item.notes || '-' }}</v-col>

                <v-col cols="6" sm="4" class="text-caption text-medium-emphasis">{{ t('createdAt') }}</v-col>
                <v-col cols="6" sm="8" class="text-body-2">{{ formatDate(item.created_at as string) }}</v-col>

                <v-col cols="6" sm="4" class="text-caption text-medium-emphasis">{{ t('createdBy') }}</v-col>
                <v-col cols="6" sm="8" class="text-body-2">{{ item.created_by_name }}</v-col>

                <v-col cols="6" sm="4" class="text-caption text-medium-emphasis">{{ t('updatedAt') }}</v-col>
                <v-col cols="6" sm="8" class="text-body-2">{{ item.updated_at ? formatDate(item.updated_at as string) : '-' }}</v-col>

                <v-col cols="6" sm="4" class="text-caption text-medium-emphasis">{{ t('updatedBy') }}</v-col>
                <v-col cols="6" sm="8" class="text-body-2">{{ item.updated_by_name || '-' }}</v-col>
            </v-row>

            <v-divider class="my-3" />

            <p class="text-subtitle-2 font-weight-bold mb-2">{{ t('details') }}</p>

            <v-data-table
                :headers="headers"
                :items="item.details ?? []"
                density="compact"
                hide-default-footer
                :items-per-page="-1"
            >
                <template #item.no="{ index }">{{ index + 1 }}</template>
                <template #item.warehouse_name="{ item: d }">
                    {{ d.warehouse_name }}
                    <span v-if="d.rack_name" class="text-caption text-medium-emphasis"> / {{ d.rack_name }}</span>
                </template>
                <template #item.adjustment_qty="{ item: d }">
                    <span :class="d.adjustment_qty < 0 ? 'text-error' : d.adjustment_qty > 0 ? 'text-success' : ''">
                        {{ d.adjustment_qty >= 0 ? '+' : '' }}{{ d.adjustment_qty }}
                    </span>
                </template>
                <template #item.notes="{ item: d }">{{ d.notes || '-' }}</template>
            </v-data-table>
        </div>
    </v-navigation-drawer>
</template>
