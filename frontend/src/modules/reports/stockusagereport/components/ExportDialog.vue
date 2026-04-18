<script lang="ts" setup>
import { computed, reactive, watch } from 'vue'
import BaseDateInput from '@/components/base/BaseDateInput.vue'
import { useTranslate } from '@/composables/useTranslate'
import MaterialSymbolsDownloadRounded from '~icons/material-symbols/download-rounded'
import MaterialSymbolsCloseRounded from '~icons/material-symbols/close-rounded'

export interface ExportPayload {
    start_date: string | null
    end_date: string | null
    status: string | null
}

const props = defineProps<{
    modelValue: boolean
    loading?: boolean
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'submit', value: ExportPayload): void
    (e: 'close'): void
}>()

const t = useTranslate()

const form = reactive<{
    exportAll: boolean
    start_date: string | null
    end_date: string | null
    status: string | null
}>({
    exportAll: false,
    start_date: null,
    end_date: null,
    status: null,
})

// Reset form tiap dialog dibuka agar state tidak "bocor" antar pemakaian.
watch(() => props.modelValue, (open) => {
    if (open) {
        form.exportAll = false
        form.start_date = null
        form.end_date = null
        form.status = null
    }
})

// Jika user meng-centang "Export All", kosongkan rentang tanggal.
watch(() => form.exportAll, (v) => {
    if (v) {
        form.start_date = null
        form.end_date = null
    }
})

const statusOptions = computed(() => [
    { title: t('all'), value: null },
    { title: 'Waiting Approval', value: 'Waiting Approval' },
    { title: 'Approved', value: 'Approved' },
    { title: 'Rejected', value: 'Rejected' },
    { title: 'Revised', value: 'Revised' },
])

const errorMessage = computed<string | null>(() => {
    if (form.exportAll) return null
    if (!form.start_date || !form.end_date) return t('reportErrorDateRangeRequired')
    if (form.end_date < form.start_date) return t('reportErrorDateRangeInvalid')
    return null
})

const summary = computed(() => {
    if (form.exportAll) return t('reportSummaryAll')
    if (form.start_date && form.end_date) {
        return `${t('reportSummaryRange')}: ${form.start_date} → ${form.end_date}`
    }
    return null
})

const handleClose = () => {
    emit('update:modelValue', false)
    emit('close')
}

const handleSubmit = () => {
    if (errorMessage.value) return
    emit('submit', {
        start_date: form.exportAll ? null : form.start_date,
        end_date:   form.exportAll ? null : form.end_date,
        status:     form.status,
    })
}
</script>

<template>
    <v-dialog
        :model-value="modelValue"
        max-width="560"
        persistent
        scrollable
        @update:model-value="emit('update:modelValue', $event)"
    >
        <v-card rounded="lg">
            <v-card-title class="d-flex align-center justify-space-between">
                <div class="d-flex align-center ga-2">
                    <v-icon :icon="MaterialSymbolsDownloadRounded" />
                    <span>{{ t('exportExcelDialogTitle') }}</span>
                </div>
                <v-btn
                    :icon="MaterialSymbolsCloseRounded"
                    variant="text"
                    size="small"
                    :disabled="loading"
                    @click="handleClose"
                />
            </v-card-title>

            <v-divider />

            <v-card-text>
                <p class="text-body-2 text-medium-emphasis mb-4">
                    {{ t('exportExcelDialogDescription') }}
                </p>

                <v-row dense>
                    <v-col cols="12">
                        <v-checkbox
                            v-model="form.exportAll"
                            :label="t('reportExportAll')"
                            density="compact"
                            hide-details
                        />
                    </v-col>

                    <v-col cols="12" sm="6">
                        <base-date-input
                            v-model="form.start_date"
                            :label="t('startDate')"
                            :readonly="form.exportAll"
                            :max="form.end_date || undefined"
                        />
                    </v-col>
                    <v-col cols="12" sm="6">
                        <base-date-input
                            v-model="form.end_date"
                            :label="t('endDate')"
                            :readonly="form.exportAll"
                            :min="form.start_date || undefined"
                        />
                    </v-col>

                    <v-col cols="12">
                        <v-select
                            v-model="form.status"
                            :items="statusOptions"
                            item-title="title"
                            item-value="value"
                            :label="t('status')"
                            density="comfortable"
                            variant="outlined"
                            hide-details="auto"
                            clearable
                        />
                    </v-col>
                </v-row>

                <v-alert
                    v-if="summary && !errorMessage"
                    type="info"
                    variant="tonal"
                    density="compact"
                    class="mt-3"
                >
                    {{ summary }}
                </v-alert>

                <v-alert
                    v-if="errorMessage"
                    type="warning"
                    variant="tonal"
                    density="compact"
                    class="mt-3"
                >
                    {{ errorMessage }}
                </v-alert>
            </v-card-text>

            <v-divider />

            <v-card-actions class="pa-4">
                <v-spacer />
                <v-btn
                    variant="text"
                    :disabled="loading"
                    @click="handleClose"
                >
                    {{ t('cancel') }}
                </v-btn>
                <v-btn
                    color="success"
                    variant="flat"
                    :prepend-icon="MaterialSymbolsDownloadRounded"
                    :loading="loading"
                    :disabled="!!errorMessage || loading"
                    @click="handleSubmit"
                >
                    {{ t('exportExcel') }}
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<style scoped></style>
