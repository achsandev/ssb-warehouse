<script lang="ts" setup>
import { computed, reactive, watch } from 'vue'
import BaseDateInput from '@/components/base/BaseDateInput.vue'
import { useTranslate } from '@/composables/useTranslate'
import MaterialSymbolsDownloadRounded from '~icons/material-symbols/download-rounded'
import MaterialSymbolsCloseRounded from '~icons/material-symbols/close-rounded'
import MaterialSymbolsViewColumnOutlineRounded from '~icons/material-symbols/view-column-outline-rounded'
import MaterialSymbolsSelectAllRounded from '~icons/material-symbols/select-all-rounded'
import MaterialSymbolsDeselectRounded from '~icons/material-symbols/deselect-rounded'

export interface ExportStatusOption {
    title: string
    value: string | null
}

/**
 * Definisi satu kolom yang bisa dipilih user di dialog export.
 *  - `key`     : identifier yang dikirim ke BE via `columns[]`.
 *  - `label`   : label yang ditampilkan di checkbox.
 *  - `default` : default-on (true) atau off (false). Default true.
 *  - `locked`  : kalau true, tidak bisa di-uncheck (selalu ikut export).
 *  - `hint`    : tooltip / sub-label opsional.
 */
export interface ExportColumnOption {
    key: string
    label: string
    default?: boolean
    locked?: boolean
    hint?: string
}

export interface ExportPayload {
    start_date: string | null
    end_date: string | null
    status: string | null
    columns: string[] | null
}

const props = withDefaults(defineProps<{
    modelValue: boolean
    loading?: boolean
    statusOptions?: ExportStatusOption[]
    /**
     * Daftar kolom yang bisa user pilih. Kalau kosong/omit → picker tidak ditampilkan
     * dan payload `columns` di-emit sebagai null (BE akan pakai semua kolom default).
     */
    columns?: ExportColumnOption[]
}>(), {
    loading: false,
    statusOptions: () => [],
    columns: () => [],
})

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
    selectedColumns: string[]
}>({
    exportAll: false,
    start_date: null,
    end_date: null,
    status: null,
    selectedColumns: [],
})

/** Default selection berdasarkan flag `default` (default true) + lock. */
const initialSelection = (): string[] => {
    if (!props.columns?.length) return []
    return props.columns
        .filter((c) => c.locked || c.default !== false)
        .map((c) => c.key)
}

watch(() => props.modelValue, (open) => {
    if (open) {
        form.exportAll = false
        form.start_date = null
        form.end_date = null
        form.status = null
        form.selectedColumns = initialSelection()
    }
})

watch(() => form.exportAll, (v) => {
    if (v) {
        form.start_date = null
        form.end_date = null
    }
})

const showStatus = computed(() => (props.statusOptions?.length ?? 0) > 0)
const showColumnPicker = computed(() => (props.columns?.length ?? 0) > 0)

const lockedKeys = computed(() => props.columns.filter((c) => c.locked).map((c) => c.key))
const allKeys = computed(() => props.columns.map((c) => c.key))
const selectableCount = computed(() => allKeys.value.length)
const selectedCount = computed(() => form.selectedColumns.length)
const isAllSelected = computed(() => selectedCount.value === selectableCount.value && selectableCount.value > 0)
const isNoneSelected = computed(() => selectedCount.value === 0)

const isLocked = (key: string) => lockedKeys.value.includes(key)

const toggleColumn = (key: string, checked: boolean) => {
    if (isLocked(key)) return
    const set = new Set(form.selectedColumns)
    checked ? set.add(key) : set.delete(key)
    form.selectedColumns = allKeys.value.filter((k) => set.has(k)) // preserve order
}

const selectAll = () => {
    form.selectedColumns = [...allKeys.value]
}

const deselectAll = () => {
    // Tetap pertahankan kolom yang locked.
    form.selectedColumns = [...lockedKeys.value]
}

const errorMessage = computed<string | null>(() => {
    if (showColumnPicker.value && isNoneSelected.value) return t('reportErrorColumnsRequired')
    if (form.exportAll) return null
    if (!form.start_date || !form.end_date) return t('reportErrorDateRangeRequired')
    if (form.end_date < form.start_date) return t('reportErrorDateRangeInvalid')
    return null
})

const summary = computed(() => {
    const parts: string[] = []
    if (form.exportAll) {
        parts.push(t('reportSummaryAll'))
    } else if (form.start_date && form.end_date) {
        parts.push(`${t('reportSummaryRange')}: ${form.start_date} → ${form.end_date}`)
    }
    if (showColumnPicker.value && selectedCount.value > 0) {
        parts.push(`${selectedCount.value}/${selectableCount.value} ${t('reportColumnsSelected')}`)
    }
    return parts.length ? parts.join(' • ') : null
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
        columns:    showColumnPicker.value ? [...form.selectedColumns] : null,
    })
}
</script>

<template>
    <v-dialog
        :model-value="modelValue"
        max-width="640"
        persistent
        scrollable
        @update:model-value="emit('update:modelValue', $event)"
    >
        <v-card rounded="lg" class="export-dialog">
            <v-card-title class="d-flex align-center justify-space-between pa-4 pb-3">
                <div class="d-flex align-center ga-3">
                    <div class="export-dialog__icon">
                        <v-icon :icon="MaterialSymbolsDownloadRounded" size="20" />
                    </div>
                    <div class="d-flex flex-column">
                        <span class="text-subtitle-1 font-weight-bold">{{ t('exportExcelDialogTitle') }}</span>
                        <span class="text-caption text-medium-emphasis">{{ t('exportExcelDialogSubtitle') }}</span>
                    </div>
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

            <v-card-text class="pa-4">
                <!-- Section: Periode -->
                <div class="export-section">
                    <p class="export-section__title">{{ t('reportSectionPeriod') }}</p>
                    <p class="export-section__desc">{{ t('exportExcelDialogDescription') }}</p>

                    <v-row dense>
                        <v-col cols="12">
                            <v-checkbox
                                v-model="form.exportAll"
                                :label="t('reportExportAll')"
                                density="compact"
                                hide-details
                                color="success"
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

                        <v-col v-if="showStatus" cols="12">
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
                </div>

                <!-- Section: Kolom -->
                <template v-if="showColumnPicker">
                    <v-divider class="my-4" />

                    <div class="export-section">
                        <div class="d-flex align-center justify-space-between flex-wrap ga-2 mb-2">
                            <div class="d-flex align-center ga-2">
                                <v-icon
                                    :icon="MaterialSymbolsViewColumnOutlineRounded"
                                    size="18"
                                    class="text-medium-emphasis"
                                />
                                <p class="export-section__title mb-0">
                                    {{ t('reportSectionColumns') }}
                                </p>
                                <v-chip
                                    size="x-small"
                                    color="success"
                                    variant="tonal"
                                    class="font-weight-medium"
                                >
                                    {{ selectedCount }}/{{ selectableCount }}
                                </v-chip>
                            </div>
                            <div class="d-flex ga-1">
                                <v-btn
                                    size="x-small"
                                    variant="text"
                                    color="success"
                                    :prepend-icon="MaterialSymbolsSelectAllRounded"
                                    :disabled="isAllSelected"
                                    @click="selectAll"
                                >
                                    {{ t('reportSelectAll') }}
                                </v-btn>
                                <v-btn
                                    size="x-small"
                                    variant="text"
                                    color="grey-darken-1"
                                    :prepend-icon="MaterialSymbolsDeselectRounded"
                                    :disabled="isNoneSelected"
                                    @click="deselectAll"
                                >
                                    {{ t('reportDeselectAll') }}
                                </v-btn>
                            </div>
                        </div>

                        <p class="export-section__desc">{{ t('reportColumnsDescription') }}</p>

                        <v-sheet
                            rounded="lg"
                            border
                            class="pa-2 column-grid"
                        >
                            <div
                                v-for="col in columns"
                                :key="col.key"
                                class="column-item"
                                :class="{ 'column-item--locked': isLocked(col.key) }"
                                @click="toggleColumn(col.key, !form.selectedColumns.includes(col.key))"
                            >
                                <v-checkbox
                                    :model-value="form.selectedColumns.includes(col.key)"
                                    :disabled="isLocked(col.key)"
                                    density="compact"
                                    hide-details
                                    color="success"
                                    class="ma-0 pa-0 column-item__check"
                                    @click.stop
                                    @update:model-value="(v) => toggleColumn(col.key, !!v)"
                                />
                                <div class="column-item__label">
                                    <span class="text-body-2">{{ col.label }}</span>
                                    <span
                                        v-if="col.hint"
                                        class="text-caption text-medium-emphasis d-block"
                                    >
                                        {{ col.hint }}
                                    </span>
                                </div>
                                <v-icon
                                    v-if="isLocked(col.key)"
                                    icon="mdi-lock-outline"
                                    size="14"
                                    class="text-medium-emphasis ms-1"
                                />
                            </div>
                        </v-sheet>
                    </div>
                </template>

                <!-- Summary / Error -->
                <v-alert
                    v-if="summary && !errorMessage"
                    type="info"
                    variant="tonal"
                    density="compact"
                    class="mt-4"
                    :icon="false"
                >
                    <div class="d-flex align-center ga-2">
                        <v-icon icon="mdi-information-outline" size="16" />
                        <span class="text-body-2">{{ summary }}</span>
                    </div>
                </v-alert>

                <v-alert
                    v-if="errorMessage"
                    type="warning"
                    variant="tonal"
                    density="compact"
                    class="mt-4"
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

<style scoped>
.export-dialog {
    overflow: hidden;
}

.export-dialog__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background-color: rgba(var(--v-theme-success), 0.12);
    color: rgb(var(--v-theme-success));
    flex-shrink: 0;
}

.export-section__title {
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
    color: rgba(var(--v-theme-on-surface), 0.92);
}

.export-section__desc {
    font-size: 0.78rem;
    color: rgba(var(--v-theme-on-surface), 0.62);
    margin-bottom: 0.75rem;
}

.column-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 4px;
    max-height: 260px;
    overflow-y: auto;
    background-color: rgb(var(--v-theme-surface));
}

@media (min-width: 600px) {
    .column-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

.column-item {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 8px;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.12s ease;
}

.column-item:hover {
    background-color: rgba(var(--v-theme-on-surface), 0.04);
}

.column-item--locked {
    cursor: not-allowed;
    opacity: 0.7;
}

.column-item__label {
    flex: 1;
    min-width: 0;
    line-height: 1.2;
}

.column-item__check :deep(.v-selection-control) {
    min-height: unset;
}

.column-item__check :deep(.v-selection-control__wrapper) {
    height: 22px;
    width: 22px;
}
</style>
