<script lang="ts" setup>
import { computed, onMounted, ref } from 'vue'
import { useField, useFieldArray, useForm } from 'vee-validate'
import { useTranslate } from '@/composables/useTranslate'
import { stockAdjustmentSchema } from '@/validations/stockAdjustmentSchema'
import { get as getStockLookup } from '@/api/lookup/stock.api'
import { show as showOpname } from '@/api/stock_opname.api'
import BaseDateInput from '@/components/base/BaseDateInput.vue'
import SystemUiconsClose from '~icons/system-uicons/close'
import dayjs from 'dayjs'

const props = defineProps<{
    modelValue: boolean
    title: string
    data: StockAdjustmentList
    saving?: boolean
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'submit', values: StockAdjustmentForm): void
    (e: 'close'): void
}>()

const t = useTranslate()

// ─── Stock Unit flat lookup ───────────────────────────────────────────────────
type StockOption = {
    title: string
    value: string
    qty: number
}

// ─── Opname flat lookup ───────────────────────────────────────────────────────
type OpnameOption = {
    title: string
    value: string
    details: Array<{
        stock_unit_uid: string | null
        difference_qty: number | null
        notes: string | null
    }>
}

const loadingForm    = ref(false)
const loadingImport  = ref(false)
const stockOptions   = ref<StockOption[]>([])
const opnameOptions  = ref<OpnameOption[]>([])
const MAX_ROWS       = 200

const isEdit = computed(() => !!props.data.uid)
const isBusy = computed(() => loadingForm.value || loadingImport.value || !!props.saving)

// ─── Form ─────────────────────────────────────────────────────────────────────
const { handleSubmit, setValues, setFieldValue, errors } = useForm<StockAdjustmentForm>({
    validationSchema: stockAdjustmentSchema(t),
})

setValues({
    adjustment_date:  props.data.adjustment_date
        ? dayjs(props.data.adjustment_date as string).format('YYYY-MM-DD')
        : dayjs().format('YYYY-MM-DD'),
    stock_opname_uid: props.data.stock_opname_uid ?? null,
    notes:            props.data.notes ?? null,
    details: (props.data.details ?? []).map((d) => ({
        stock_unit_uid: d.stock_unit_uid ?? null,
        adjustment_qty: d.adjustment_qty ?? null,
        notes:          d.notes ?? null,
    })),
})

// ─── Field bindings ───────────────────────────────────────────────────────────
const { value: adjustment_date }  = useField<string>('adjustment_date')
const { value: stock_opname_uid } = useField<string | null>('stock_opname_uid')
const { value: notes }            = useField<string | null>('notes')

const { fields: detailRows, push: addRow, remove: removeRow, replace: replaceRows } = useFieldArray<{
    stock_unit_uid: string | null
    adjustment_qty: number | null
    notes: string | null
}>('details')

const detailError = (idx: number, field: string): string =>
    (errors.value as Record<string, string>)[`details[${idx}].${field}`] ?? ''

// ─── Lookups ──────────────────────────────────────────────────────────────────
const loadLookups = async () => {
    loadingForm.value = true
    try {
        const res = await getStockLookup()
        const stocks: any[] = res.data ?? []
        const flat: StockOption[] = []

        for (const stock of stocks) {
            for (const su of stock.stock_units ?? []) {
                const unitSymbol = su.unit_symbol ?? ''
                const location   = stock.rack?.name
                    ? `${stock.warehouse?.name} / ${stock.rack?.name}`
                    : (stock.warehouse?.name ?? '')
                flat.push({
                    title: `${stock.item?.name ?? '-'} (${unitSymbol}) @ ${location}`,
                    value: su.uid,
                    qty:   su.qty,
                })
            }
        }

        stockOptions.value = flat
    } catch {
        // silently ignore
    } finally {
        loadingForm.value = false
    }
}

onMounted(loadLookups)

// ─── Import from opname ───────────────────────────────────────────────────────
const handleImportOpname = async () => {
    if (!stock_opname_uid.value) return
    loadingImport.value = true
    try {
        const res = await showOpname(stock_opname_uid.value)
        const opname = res.data?.data ?? res.data
        const rows = (opname?.details ?? [])
            .filter((d: any) => d.difference_qty !== null && d.difference_qty !== 0)
            .map((d: any) => ({
                stock_unit_uid: d.stock_unit_uid ?? null,
                adjustment_qty: d.difference_qty ?? null,
                notes:          d.notes ?? null,
            }))
        replaceRows(rows)
    } catch {
        // silently ignore
    } finally {
        loadingImport.value = false
    }
}

// ─── Row management ───────────────────────────────────────────────────────────
const handleAddRow = () => {
    if (detailRows.value.length >= MAX_ROWS) return
    addRow({ stock_unit_uid: null, adjustment_qty: null, notes: null })
}

// ─── Submit ───────────────────────────────────────────────────────────────────
const onSubmit = handleSubmit((values) => emit('submit', values))
const onClose  = () => emit('close')
</script>

<template>
    <v-dialog
        :model-value="modelValue"
        max-width="960"
        :persistent="isBusy"
        scrollable
        @update:model-value="onClose"
    >
        <v-card :loading="loadingForm || loadingImport" rounded="lg">

            <!-- Title bar -->
            <v-card-title class="d-flex align-center gap-2 px-6 py-4">
                <span class="text-h6 font-weight-semibold">{{ title }}</span>
                <v-spacer />
                <v-btn :icon="SystemUiconsClose" variant="text" size="small" :disabled="isBusy" @click="onClose" />
            </v-card-title>

            <v-divider />

            <v-card-text class="px-6 py-5">
                <v-form @submit.prevent="onSubmit">

                    <!-- General Info -->
                    <div class="section-header mb-3">
                        <span class="text-subtitle-2 font-weight-bold text-primary">{{ t('generalInfo') }}</span>
                    </div>

                    <v-row dense>
                        <v-col cols="12">
                            <base-date-input v-model="adjustment_date" :disabled="loadingForm" />
                        </v-col>
                        <v-col cols="12">
                            <v-textarea
                                v-model="notes"
                                :label="t('notes')"
                                :disabled="loadingForm"
                                rows="2"
                                auto-grow
                                density="compact"
                                variant="outlined"
                                hide-details="auto"
                            />
                        </v-col>
                    </v-row>

                    <v-divider class="mt-2 mb-4" />

                    <!-- Import from Opname -->
                    <div class="section-header mb-3">
                        <span class="text-subtitle-2 font-weight-bold text-primary">{{ t('importFromOpname') }}</span>
                    </div>

                    <v-row dense class="mb-2 align-center">
                        <v-col cols="12">
                            <v-text-field
                                v-model="stock_opname_uid"
                                :label="t('opnameReference')"
                                :placeholder="t('opnameReferenceHint')"
                                density="compact"
                                variant="outlined"
                                hide-details="auto"
                                clearable
                                :disabled="loadingForm"
                            />
                        </v-col>
                    </v-row>

                    <v-divider class="mt-2 mb-4" />

                    <!-- Details header -->
                    <div class="d-flex justify-space-between align-center mb-3">
                        <div class="section-header grow">
                            <span class="text-subtitle-2 font-weight-bold text-primary">{{ t('details') }}</span>
                            <v-chip v-if="detailRows.length" size="x-small" color="primary" variant="tonal" class="ms-1">
                                {{ detailRows.length }} / {{ MAX_ROWS }}
                            </v-chip>
                        </div>
                        <!-- <v-btn
                            size="small"
                            color="primary"
                            variant="tonal"
                            :disabled="loadingForm || detailRows.length >= MAX_ROWS"
                            @click="handleAddRow"
                        >
                            {{ t('addRow') }}
                        </v-btn> -->
                    </div>

                    <v-sheet rounded="lg" border class="overflow-hidden">
                        <v-table density="compact" class="detail-table">
                            <thead>
                                <tr>
                                    <th class="text-center no-col">No.</th>
                                    <th>{{ t('selectStock') }}</th>
                                    <th class="qty-col">{{ t('adjustmentQty') }}</th>
                                    <th class="note-col">{{ t('notes') }}</th>
                                    <th class="text-center act-col"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="detailRows.length === 0">
                                    <td colspan="5" class="pa-8 text-center">
                                        <div class="d-flex flex-column align-center gap-2 text-medium-emphasis">
                                            <v-icon icon="mdi-tune" size="48" />
                                            <span class="text-body-2">{{ t('addRowToStart') }}</span>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-for="(row, idx) in detailRows" :key="row.key">
                                    <td class="text-center text-caption text-medium-emphasis no-col">{{ idx + 1 }}</td>

                                    <!-- Stock Unit Autocomplete -->
                                    <td class="py-1">
                                        <v-autocomplete
                                            v-model="row.value.stock_unit_uid"
                                            :items="stockOptions"
                                            item-title="title"
                                            item-value="value"
                                            :error-messages="detailError(idx, 'stock_unit_uid')"
                                            :disabled="loadingForm || (isEdit && !!props.data.details?.[idx]?.stock_unit_uid)"
                                            density="compact"
                                            variant="outlined"
                                            hide-details="auto"
                                            clearable
                                        />
                                    </td>

                                    <!-- Adjustment Qty -->
                                    <td class="py-1 qty-col">
                                        <v-text-field
                                            v-model.number="row.value.adjustment_qty"
                                            :error-messages="detailError(idx, 'adjustment_qty')"
                                            :disabled="loadingForm"
                                            :placeholder="t('adjustmentQty')"
                                            type="number"
                                            density="compact"
                                            variant="outlined"
                                            hide-details="auto"
                                        />
                                    </td>

                                    <!-- Notes -->
                                    <td class="py-1 note-col">
                                        <v-text-field
                                            v-model="row.value.notes"
                                            :disabled="loadingForm"
                                            :placeholder="t('notes')"
                                            density="compact"
                                            variant="outlined"
                                            hide-details="auto"
                                        />
                                    </td>

                                    <!-- Remove -->
                                    <td class="text-center act-col">
                                        <v-btn
                                            icon="mdi-close"
                                            size="x-small"
                                            variant="text"
                                            color="error"
                                            :disabled="loadingForm"
                                            @click="removeRow(idx)"
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </v-table>
                    </v-sheet>

                    <div v-if="(errors as any).details" class="text-caption text-error mt-1 ms-1">
                        {{ (errors as any).details }}
                    </div>

                </v-form>
            </v-card-text>

            <v-divider />

            <!-- Actions -->
            <v-card-actions class="px-6 py-4 justify-end gap-2">
                <v-btn variant="tonal" :disabled="isBusy" @click="onClose">{{ t('cancel') }}</v-btn>
                <v-btn
                    color="primary"
                    variant="elevated"
                    :loading="saving"
                    :disabled="loadingForm || loadingImport"
                    @click="onSubmit"
                >
                    {{ t('save') }}
                </v-btn>
            </v-card-actions>

        </v-card>
    </v-dialog>
</template>

<style scoped>
.section-header { display: flex; align-items: center; gap: 6px; }

.detail-table :deep(thead th) {
    font-size: 0.7rem !important;
    font-weight: 700 !important;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    white-space: nowrap;
    background-color: rgba(var(--v-theme-primary), 1) !important;
    color: #ffffff !important;
}
.detail-table :deep(tbody tr:hover td) { background-color: rgba(var(--v-theme-primary), 0.04); }
.detail-table :deep(tbody td) { vertical-align: middle; }

.no-col   { width: 48px;  min-width: 48px; }
.qty-col  { width: 140px; min-width: 140px; }
.note-col { min-width: 200px; }
.act-col  { width: 48px;  min-width: 48px; }
</style>
