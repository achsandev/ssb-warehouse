<script lang="ts" setup>
import { computed } from 'vue'
import type { DataTableHeader } from 'vuetify'
import { useTranslate } from '@/composables/useTranslate'
import { formatDate } from '@/utils/date'
import SystemUiconsClose from '~icons/system-uicons/close'

const model = defineModel<boolean>()

const props = defineProps<{
    item: CashPurchaseList
}>()

const emit = defineEmits<{
    (e: 'close'): void
}>()

const t = useTranslate()

const headers = computed<DataTableHeader[]>(() => [
    { title: 'No',        value: 'no',          align: 'center', width: '50px', sortable: false },
    { title: t('item'),   value: 'item_name',   align: 'start',  sortable: false },
    { title: t('unit'),   value: 'unit_symbol', align: 'start',  sortable: false },
    { title: t('qty'),    value: 'qty',         align: 'end',    sortable: false },
    { title: t('price'),  value: 'price',       align: 'end',    sortable: false },
    { title: t('total'),  value: 'total',       align: 'end',    sortable: false },
])

const statusColor = (status: string): string => {
    switch ((status ?? '').toLowerCase()) {
        case 'waiting approval': return 'warning'
        case 'approved':         return 'success'
        case 'rejected':         return 'red'
        case 'revised':          return 'deep-orange'
        default:                 return 'grey'
    }
}

const handleClose = () => emit('close')
</script>

<template>
    <v-navigation-drawer
        v-model="model"
        location="right"
        width="700"
        temporary
    >
        <!-- Header -->
        <v-list-item
            class="pa-4"
            :title="t('information', { field: t('cashPurchase') })"
        >
            <template #append>
                <v-btn
                    :icon="SystemUiconsClose"
                    variant="text"
                    size="small"
                    @click="handleClose"
                />
            </template>
        </v-list-item>

        <v-divider />

        <div class="pa-4">
            <v-row dense class="mb-2">
                <v-col cols="6" sm="4" class="text-caption text-medium-emphasis">{{ t('purchaseNumber') }}</v-col>
                <v-col cols="6" sm="8" class="text-body-2 font-weight-medium">{{ item.purchase_number }}</v-col>

                <v-col cols="6" sm="4" class="text-caption text-medium-emphasis">{{ t('purchaseDate') }}</v-col>
                <v-col cols="6" sm="8" class="text-body-2">{{ formatDate(item.purchase_date as string) }}</v-col>

                <v-col cols="6" sm="4" class="text-caption text-medium-emphasis">{{ t('warehouse') }}</v-col>
                <v-col cols="6" sm="8" class="text-body-2 font-weight-medium">{{ item.warehouse_name }}</v-col>

                <v-col cols="6" sm="4" class="text-caption text-medium-emphasis">{{ t('cashBalance') }}</v-col>
                <v-col cols="6" sm="8" class="text-body-2">
                    Rp {{ Number(item.cash_balance ?? 0).toLocaleString('id-ID') }}
                </v-col>

                <v-col cols="6" sm="4" class="text-caption text-medium-emphasis">{{ t('poNumber') }}</v-col>
                <v-col cols="6" sm="8" class="text-body-2 font-weight-medium">{{ item.po_number }}</v-col>

                <v-col cols="6" sm="4" class="text-caption text-medium-emphasis">{{ t('poTotalAmount') }}</v-col>
                <v-col cols="6" sm="8" class="text-body-2 font-weight-bold">
                    Rp {{ Number(item.po_total_amount ?? 0).toLocaleString('id-ID') }}
                </v-col>

                <v-col cols="6" sm="4" class="text-caption text-medium-emphasis">{{ t('status') }}</v-col>
                <v-col cols="6" sm="8">
                    <v-chip size="small" :color="statusColor(item.status)" variant="outlined">
                        {{ item.status }}
                    </v-chip>
                </v-col>

                <v-col cols="6" sm="4" class="text-caption text-medium-emphasis">{{ t('notes') }}</v-col>
                <v-col cols="6" sm="8" class="text-body-2" style="white-space: pre-wrap">{{ item.notes || '-' }}</v-col>

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
                :items="item.po_details ?? []"
                density="compact"
                hide-default-footer
                :items-per-page="-1"
            >
                <template #item.no="{ index }">{{ index + 1 }}</template>
                <template #item.price="{ item: d }">
                    Rp {{ Number(d.price).toLocaleString('id-ID') }}
                </template>
                <template #item.total="{ item: d }">
                    Rp {{ Number(d.total).toLocaleString('id-ID') }}
                </template>
                <template #bottom>
                    <div class="d-flex justify-end pa-2">
                        <span class="text-body-2 font-weight-bold">
                            Total: Rp {{ Number(item.po_total_amount ?? 0).toLocaleString('id-ID') }}
                        </span>
                    </div>
                </template>
            </v-data-table>
        </div>
    </v-navigation-drawer>
</template>
