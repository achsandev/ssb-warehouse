<script lang="ts" setup>
import { useTranslate } from '@/composables/useTranslate'
import { formatDate } from '@/utils/date'
import SystemUiconsClose from '~icons/system-uicons/close'

const model = defineModel<boolean>()

const props = defineProps<{
    item: WarehouseCashRequestList
}>()

const emit = defineEmits<{
    (e: 'close'): void
}>()

const t = useTranslate()

const statusColor = (status: string): string => {
    switch ((status ?? '').toLowerCase()) {
        case 'waiting approval': return 'warning'
        case 'approved':         return 'success'
        case 'rejected':         return 'red'
        case 'revised':          return 'orange'
        default:                 return 'grey'
    }
}

const formatCurrency = (val: number) =>
    'Rp ' + Number(val ?? 0).toLocaleString('id-ID')

const handleClose = () => emit('close')
</script>

<template>
    <v-navigation-drawer v-model="model" location="right" width="560" temporary>
        <!-- Header -->
        <v-list-item class="pa-4" :title="t('information', { field: t('warehouseCashRequest') })">
            <template #append>
                <v-btn :icon="SystemUiconsClose" variant="text" size="small" @click="handleClose" />
            </template>
        </v-list-item>

        <v-divider />

        <div class="pa-4">
            <v-row dense class="mb-2">
                <!-- Request Number -->
                <v-col cols="6" sm="5" class="text-caption text-medium-emphasis">{{ t('requestNumber') }}</v-col>
                <v-col cols="6" sm="7" class="text-body-2 font-weight-medium">{{ item.request_number }}</v-col>

                <!-- Request Date -->
                <v-col cols="6" sm="5" class="text-caption text-medium-emphasis">{{ t('requestDate') }}</v-col>
                <v-col cols="6" sm="7" class="text-body-2">{{ formatDate(item.request_date as string) }}</v-col>

                <!-- Warehouse -->
                <v-col cols="6" sm="5" class="text-caption text-medium-emphasis">{{ t('warehouse') }}</v-col>
                <v-col cols="6" sm="7" class="text-body-2">{{ item.warehouse_name }}</v-col>

                <!-- Cash Balance -->
                <v-col cols="6" sm="5" class="text-caption text-medium-emphasis">{{ t('cashBalance') }}</v-col>
                <v-col cols="6" sm="7" class="text-body-2">{{ formatCurrency(item.cash_balance) }}</v-col>

                <!-- Amount -->
                <v-col cols="6" sm="5" class="text-caption text-medium-emphasis">{{ t('requestAmount') }}</v-col>
                <v-col cols="6" sm="7" class="text-body-2 font-weight-medium text-primary">
                    {{ formatCurrency(item.amount) }}
                </v-col>

                <!-- Status -->
                <v-col cols="6" sm="5" class="text-caption text-medium-emphasis">{{ t('status') }}</v-col>
                <v-col cols="6" sm="7">
                    <v-chip size="small" :color="statusColor(item.status)" variant="outlined">
                        {{ item.status }}
                    </v-chip>
                </v-col>

                <!-- Notes -->
                <v-col cols="6" sm="5" class="text-caption text-medium-emphasis">{{ t('notes') }}</v-col>
                <v-col cols="6" sm="7" class="text-body-2">{{ item.notes || '-' }}</v-col>

                <!-- Attachment -->
                <v-col cols="6" sm="5" class="text-caption text-medium-emphasis">{{ t('attachment') }}</v-col>
                <v-col cols="6" sm="7" class="text-body-2">
                    <v-btn
                        v-if="item.attachment_url"
                        :href="item.attachment_url"
                        target="_blank"
                        size="small"
                        variant="tonal"
                        color="primary"
                        prepend-icon="mdi-download"
                    >
                        {{ item.attachment_name || t('downloadAttachment') }}
                    </v-btn>
                    <span v-else>-</span>
                </v-col>

                <v-divider class="my-3" style="grid-column: 1 / -1" />

                <!-- Created At -->
                <v-col cols="6" sm="5" class="text-caption text-medium-emphasis">{{ t('createdAt') }}</v-col>
                <v-col cols="6" sm="7" class="text-body-2">{{ formatDate(item.created_at as string) }}</v-col>

                <!-- Created By -->
                <v-col cols="6" sm="5" class="text-caption text-medium-emphasis">{{ t('createdBy') }}</v-col>
                <v-col cols="6" sm="7" class="text-body-2">{{ item.created_by_name }}</v-col>

                <!-- Updated At -->
                <v-col cols="6" sm="5" class="text-caption text-medium-emphasis">{{ t('updatedAt') }}</v-col>
                <v-col cols="6" sm="7" class="text-body-2">{{ item.updated_at ? formatDate(item.updated_at as string) : '-' }}</v-col>

                <!-- Updated By -->
                <v-col cols="6" sm="5" class="text-caption text-medium-emphasis">{{ t('updatedBy') }}</v-col>
                <v-col cols="6" sm="7" class="text-body-2">{{ item.updated_by_name || '-' }}</v-col>
            </v-row>
        </div>
    </v-navigation-drawer>
</template>

<style scoped></style>
