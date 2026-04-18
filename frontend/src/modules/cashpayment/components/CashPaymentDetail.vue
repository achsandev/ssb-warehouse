<script lang="ts" setup>
import { useTranslate } from '@/composables/useTranslate'
import { formatDate } from '@/utils/date'
import SystemUiconsClose from '~icons/system-uicons/close'

const model = defineModel<boolean>()

const props = defineProps<{
    item: CashPaymentList
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
        case 'revised':          return 'info'
        default:                 return 'grey'
    }
}

const handleClose = () => emit('close')
</script>

<template>
    <v-navigation-drawer v-model="model" location="right" width="800" temporary>
        <!-- Header -->
        <v-list-item class="pa-4" :title="t('information', { field: t('cashPayment') })">
            <template #append>
                <v-btn :icon="SystemUiconsClose" variant="text" size="small" @click="handleClose" />
            </template>
        </v-list-item>

        <v-divider />

        <div class="pa-4">
            <v-row dense class="mb-2">
                <!-- Payment Number -->
                <v-col cols="6" sm="4" class="text-caption text-medium-emphasis">{{ t('paymentNumber') }}</v-col>
                <v-col cols="6" sm="8" class="text-body-2 font-weight-medium">{{ item.payment_number }}</v-col>

                <!-- Payment Date -->
                <v-col cols="6" sm="4" class="text-caption text-medium-emphasis">{{ t('paymentDate') }}</v-col>
                <v-col cols="6" sm="8" class="text-body-2">{{ formatDate(item.payment_date as string) }}</v-col>

                <!-- Warehouse -->
                <v-col cols="6" sm="4" class="text-caption text-medium-emphasis">{{ t('warehouse') }}</v-col>
                <v-col cols="6" sm="8" class="text-body-2">{{ item.warehouse_name }}</v-col>

                <!-- Cash Balance at time of record -->
                <v-col cols="6" sm="4" class="text-caption text-medium-emphasis">{{ t('cashBalance') }}</v-col>
                <v-col cols="6" sm="8" class="text-body-2">
                    Rp {{ Number(item.cash_balance ?? 0).toLocaleString('id-ID') }}
                </v-col>

                <!-- Description -->
                <v-col cols="6" sm="4" class="text-caption text-medium-emphasis">{{ t('paymentDescription') }}</v-col>
                <v-col cols="6" sm="8" class="text-body-2" style="white-space: pre-wrap">{{ item.description || '-' }}</v-col>

                <!-- Amount -->
                <v-col cols="6" sm="4" class="text-caption text-medium-emphasis">{{ t('paymentAmount') }}</v-col>
                <v-col cols="6" sm="8" class="text-body-2 font-weight-bold">
                    Rp {{ Number(item.amount ?? 0).toLocaleString('id-ID') }}
                </v-col>

                <!-- Status -->
                <v-col cols="6" sm="4" class="text-caption text-medium-emphasis">{{ t('status') }}</v-col>
                <v-col cols="6" sm="8">
                    <v-chip size="small" :color="statusColor(item.status)" variant="outlined">
                        {{ item.status }}
                    </v-chip>
                </v-col>

                <!-- Notes -->
                <v-col cols="6" sm="4" class="text-caption text-medium-emphasis">{{ t('notes') }}</v-col>
                <v-col cols="6" sm="8" class="text-body-2" style="white-space: pre-wrap">{{ item.notes || '-' }}</v-col>

                <!-- Audit -->
                <v-col cols="6" sm="4" class="text-caption text-medium-emphasis">{{ t('createdAt') }}</v-col>
                <v-col cols="6" sm="8" class="text-body-2">{{ formatDate(item.created_at as string) }}</v-col>

                <v-col cols="6" sm="4" class="text-caption text-medium-emphasis">{{ t('createdBy') }}</v-col>
                <v-col cols="6" sm="8" class="text-body-2">{{ item.created_by_name }}</v-col>

                <v-col cols="6" sm="4" class="text-caption text-medium-emphasis">{{ t('updatedAt') }}</v-col>
                <v-col cols="6" sm="8" class="text-body-2">{{ item.updated_at ? formatDate(item.updated_at as string) : '-' }}</v-col>

                <v-col cols="6" sm="4" class="text-caption text-medium-emphasis">{{ t('updatedBy') }}</v-col>
                <v-col cols="6" sm="8" class="text-body-2">{{ item.updated_by_name || '-' }}</v-col>
            </v-row>

            <!-- Documents section -->
            <template v-if="item.spk_url || item.attachment_url">
                <v-divider class="my-3" />
                <p class="text-subtitle-2 font-weight-bold mb-3">{{ t('documents') }}</p>

                <div class="d-flex flex-wrap gap-2">
                    <!-- SPK Document -->
                    <v-btn
                        v-if="item.spk_url"
                        :href="item.spk_url"
                        target="_blank"
                        variant="tonal"
                        color="primary"
                        size="small"
                        prepend-icon="mdi-file-pdf-box"
                    >
                        {{ item.spk_name || t('spkDocument') }}
                    </v-btn>

                    <!-- Attachment -->
                    <v-btn
                        v-if="item.attachment_url"
                        :href="item.attachment_url"
                        target="_blank"
                        variant="tonal"
                        color="secondary"
                        size="small"
                        prepend-icon="mdi-file-pdf-box"
                    >
                        {{ item.attachment_name || t('attachment') }}
                    </v-btn>
                </div>
            </template>
        </div>
    </v-navigation-drawer>
</template>
