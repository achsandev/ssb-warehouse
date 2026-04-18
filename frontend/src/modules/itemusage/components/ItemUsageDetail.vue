<script lang="ts" setup>
import { computed } from 'vue'
import type { DataTableHeader } from 'vuetify'
// Import Composables
import { useTranslate } from '@/composables/useTranslate'
// Import Utils
import { formatDate } from '@/utils/date'
// Import Icons
import SystemUiconsClose from '~icons/system-uicons/close'

const model = defineModel<boolean>()

const props = defineProps<{
    item: ItemUsageList
}>()

const emit = defineEmits<{
    (e: 'close'): void
}>()

const t = useTranslate()

const headers = computed<DataTableHeader[]>(() => [
    { title: 'No', value: 'no', align: 'center', width: '50px', sortable: false },
    { title: t('item'), value: 'item', align: 'start' },
    { title: t('unit'), value: 'unit', align: 'start' },
    { title: t('requestedQty'), value: 'qty', align: 'end' },
    { title: t('usageQty'), value: 'usage_qty', align: 'end' },
    { title: t('description'), value: 'description', align: 'start' },
])

const statusColor = (status: string): string => {
    switch ((status ?? '').toLowerCase()) {
        case 'waiting approval': return 'warning'
        case 'approved':         return 'success'
        case 'rejected':         return 'red'
        case 'revised':          return 'deep-orange'
        case 'converted':        return 'blue'
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
            :title="t('information', { field: t('itemUsage') })"
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
            <!-- Info rows -->
            <v-row dense class="mb-2">
                <v-col cols="6" sm="4" class="text-caption text-medium-emphasis">{{ t('itemRequest') }}</v-col>
                <v-col cols="6" sm="8" class="text-body-2 font-weight-medium">{{ item.item_request?.request_number ?? '-' }}</v-col>

                <v-col cols="6" sm="4" class="text-caption text-medium-emphasis">{{ t('usageNumber') }}</v-col>
                <v-col cols="6" sm="8" class="text-body-2 font-weight-medium">{{ item.usage_number }}</v-col>

                <v-col cols="6" sm="4" class="text-caption text-medium-emphasis">{{ t('usageDate') }}</v-col>
                <v-col cols="6" sm="8" class="text-body-2">{{ formatDate(item.usage_date as string) }}</v-col>

                <v-col cols="6" sm="4" class="text-caption text-medium-emphasis">{{ t('status') }}</v-col>
                <v-col cols="6" sm="8">
                    <v-chip size="small" :color="statusColor(item.status)" variant="outlined">
                        {{ item.status }}
                    </v-chip>
                </v-col>

                <v-col cols="6" sm="4" class="text-caption text-medium-emphasis">{{ t('projectName') }}</v-col>
                <v-col cols="6" sm="8" class="text-body-2">{{ item.project_name || '-' }}</v-col>

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

            <!-- Details table -->
            <p class="text-subtitle-2 font-weight-bold mb-2">{{ t('details') }}</p>
            <v-data-table
                :headers="headers"
                :items="item.details ?? []"
                density="compact"
                hide-default-footer
                :items-per-page="-1"
            >
                <template #item.no="{ index }">{{ index + 1 }}</template>
                <template #item.item="{ item: d }">{{ d.item?.name ?? '-' }}</template>
                <template #item.unit="{ item: d }">{{ d.unit?.symbol ?? '-' }}</template>
                <template #item.qty="{ item: d }">{{ d.qty }}</template>
                <template #item.usage_qty="{ item: d }">{{ d.usage_qty }}</template>
                <template #item.description="{ item: d }">{{ d.description || '-' }}</template>
            </v-data-table>
        </div>
    </v-navigation-drawer>
</template>
