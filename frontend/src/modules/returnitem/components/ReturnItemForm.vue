<script lang="ts" setup>
import { computed, onMounted, ref } from 'vue'
import { useField, useFieldArray, useForm } from 'vee-validate'
import { useTranslate } from '@/composables/useTranslate'
import { returnItemSchema } from '@/validations/returnItemSchema'
import { useMessageStore } from '@/stores/message'
import { get as getPurchaseOrderLookup, show as getPurchaseOrderByUid } from '@/api/lookup/purchase_order.api'
import BaseAutocomplete from '@/components/base/BaseAutocomplete.vue'
import BaseDateInput from '@/components/base/BaseDateInput.vue'
import dayjs from 'dayjs'

const props = defineProps<{
    modelValue: boolean
    title: string
    data: ReturnItemList
    saving?: boolean
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'submit', values: ReturnItemForm): void
    (e: 'close'): void
}>()

const t = useTranslate()
const message = useMessageStore()

// ─── Types ───────────────────────────────────────────────────────────────────
type DetailDisplay = { itemName: string; unitSymbol: string; orderedQty: number }
type PurchaseOrderOption = { title: string; value: string }

// ─── State ───────────────────────────────────────────────────────────────────
const loadingForm        = ref(false)
const loadingPO          = ref(false)
const purchaseOrderOptions = ref<PurchaseOrderOption[]>([])
const detailDisplay      = ref<DetailDisplay[]>([])

const isEdit = computed(() => !!props.data.uid)
const isBusy = computed(() => loadingForm.value || loadingPO.value || !!props.saving)

// ─── Form ────────────────────────────────────────────────────────────────────
const { handleSubmit, setValues, resetForm, errors } = useForm<ReturnItemForm>({
    validationSchema: returnItemSchema(t),
})

const buildInitialDetails = () => {
    if (!props.data.details?.length) {
        detailDisplay.value = []
        return []
    }
    detailDisplay.value = props.data.details.map((d) => ({
        itemName:   d.item ? (d.item.code ? `${d.item.code} - ${d.item.name}` : d.item.name) : '-',
        unitSymbol: d.unit?.symbol ?? '-',
        orderedQty: d.qty,
    }))
    return props.data.details.map((d) => ({
        item_uid:    d.item?.uid ?? null,
        unit_uid:    d.unit?.uid ?? null,
        qty:         d.qty,
        return_qty:  d.return_qty ?? null,
        description: d.description ?? '',
    }))
}

setValues({
    purchase_order_uid: props.data.purchase_order?.uid ?? null,
    return_date: props.data.return_date
        ? dayjs(props.data.return_date as string).format('YYYY-MM-DD')
        : dayjs().format('YYYY-MM-DD'),
    project_name: props.data.project_name ?? null,
    details: buildInitialDetails(),
})

// ─── Field bindings ──────────────────────────────────────────────────────────
const { value: purchase_order_uid } = useField<string | null>('purchase_order_uid')
const { value: return_date }        = useField<string>('return_date')
const { value: project_name }       = useField<string | null>('project_name')

const { fields: detailRows, replace: replaceRows } = useFieldArray<{
    item_uid:    string | null
    unit_uid:    string | null
    qty:         number
    return_qty:  number | null
    description: string
}>('details')

// ─── Helpers ─────────────────────────────────────────────────────────────────
const detailError = (idx: number, field: string): string =>
    (errors.value as Record<string, string>)[`details[${idx}].${field}`] ?? ''

// ─── PO auto-fill ─────────────────────────────────────────────────────────────
const onPurchaseOrderSelected = async (uid: string | null) => {
    if (!uid) {
        replaceRows([])
        detailDisplay.value = []
        project_name.value = null
        return
    }

    loadingPO.value = true
    try {
        const data = await getPurchaseOrderByUid(uid)
        project_name.value = data.project_name ?? null

        const incoming: PurchaseOrderDetail[] = data.details ?? []
        detailDisplay.value = incoming.map((d) => ({
            itemName:   d.item ? (d.item.code ? `${d.item.code} - ${d.item.name}` : d.item.name) : '-',
            unitSymbol: d.unit?.symbol ?? '-',
            orderedQty: d.qty,
        }))
        replaceRows(
            incoming.map((d) => ({
                item_uid:    d.item?.uid ?? null,
                unit_uid:    d.unit?.uid ?? null,
                qty:         d.qty,
                return_qty:  null,
                description: '',
            })),
        )
    } catch (err) {
        console.error('[ReturnItemForm] onPurchaseOrderSelected error:', err)
        message.setMessage({ text: t('loadingFormError'), timeout: 3000, color: 'error' })
    } finally {
        loadingPO.value = false
    }
}

// ─── Loaders ─────────────────────────────────────────────────────────────────
const loadPurchaseOrders = async () => {
    const res = await getPurchaseOrderLookup()
    const all = (res.data ?? []) as any[]
    const options: PurchaseOrderOption[] = all
        .filter((po) => (po.status ?? '').toLowerCase() === 'approved')
        .map((po) => ({
            title: po.project_name
                ? `${po.po_number} | ${po.project_name}`
                : po.po_number,
            value: po.uid,
        }))

    // In edit mode re-inject current PO if not included
    if (isEdit.value && props.data.purchase_order) {
        const alreadyIncluded = options.some((o) => o.value === props.data.purchase_order!.uid)
        if (!alreadyIncluded) {
            options.unshift({
                title: props.data.purchase_order.po_number,
                value: props.data.purchase_order.uid,
            })
        }
    }

    purchaseOrderOptions.value = options
}

// ─── Lifecycle ────────────────────────────────────────────────────────────────
onMounted(async () => {
    loadingForm.value = true
    try {
        await loadPurchaseOrders()
    } catch (err) {
        console.error('[ReturnItemForm] onMounted error:', err)
        message.setMessage({ text: t('loadingFormError'), timeout: 3000, color: 'error' })
    } finally {
        loadingForm.value = false
    }
})

// ─── Actions ─────────────────────────────────────────────────────────────────
const onSubmit = handleSubmit((values) => emit('submit', values))

const onClose = () => {
    resetForm()
    emit('update:modelValue', false)
    emit('close')
}
</script>

<template>
    <v-dialog
        :model-value="modelValue"
        max-width="1100"
        :persistent="isBusy"
        scrollable
        @update:model-value="onClose"
    >
        <v-card :loading="loadingForm || loadingPO" rounded="lg">

            <!-- ── Title bar ───────────────────────────────────────────── -->
            <v-card-title class="d-flex align-center gap-2 px-6 py-4">
                <v-icon icon="mdi-arrow-u-left-top-bold" color="primary" class="me-1" />
                <span class="text-h6 font-weight-semibold">{{ title }}</span>
                <v-spacer />
                <v-btn
                    icon="mdi-close"
                    variant="text"
                    size="small"
                    :disabled="isBusy"
                    @click="onClose"
                />
            </v-card-title>

            <v-divider />

            <v-card-text class="px-6 py-5">
                <v-form @submit.prevent="onSubmit">

                    <!-- ── Section: General Info ──────────────────────── -->
                    <div class="section-header mb-3">
                        <v-icon icon="mdi-information-outline" size="18" color="primary" />
                        <span class="text-subtitle-2 font-weight-bold text-primary">{{ t('generalInfo') }}</span>
                    </div>

                    <v-row dense>
                        <v-col cols="12" md="6">
                            <base-autocomplete
                                v-model="purchase_order_uid"
                                :label="t('purchaseOrder')"
                                :items="purchaseOrderOptions"
                                :error-messages="(errors as any).purchase_order_uid"
                                :disabled="loadingForm || isEdit"
                                :loading="loadingPO"
                                clearable
                                @update:model-value="onPurchaseOrderSelected"
                            />
                        </v-col>

                        <v-col cols="12" md="6">
                            <base-date-input
                                v-model="return_date"
                                :label="t('returnDate')"
                                :disabled="loadingForm"
                            />
                        </v-col>
                    </v-row>

                    <!-- ── Section: Return Details ────────────────────── -->
                    <v-divider class="mt-3 mb-4" />

                    <div class="section-header mb-3">
                        <v-icon icon="mdi-format-list-bulleted" size="18" color="primary" />
                        <span class="text-subtitle-2 font-weight-bold text-primary">{{ t('details') }}</span>
                        <v-chip
                            v-if="detailRows.length"
                            size="x-small"
                            color="primary"
                            variant="tonal"
                            class="ms-1"
                        >
                            {{ detailRows.length }}
                        </v-chip>
                    </div>

                    <v-sheet rounded="lg" border class="overflow-hidden">
                        <v-table density="compact" class="detail-table">
                            <thead>
                                <tr>
                                    <th class="text-center no-col">No.</th>
                                    <th>{{ t('item') }}</th>
                                    <th class="text-center unit-col">{{ t('unit') }}</th>
                                    <th class="text-center qty-col">{{ t('qty') }}</th>
                                    <th class="text-center return-col">{{ t('returnQty') }}</th>
                                    <th class="desc-col">{{ t('description') }}</th>
                                </tr>
                            </thead>
                            <tbody>

                                <!-- Empty state -->
                                <tr v-if="detailRows.length === 0">
                                    <td colspan="6" class="pa-8 text-center">
                                        <div class="d-flex flex-column align-center gap-2 text-medium-emphasis">
                                            <v-icon icon="mdi-package-variant-closed" size="48" />
                                            <span class="text-body-2">{{ t('selectPurchaseOrderFirst') }}</span>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Detail rows -->
                                <tr v-for="(row, idx) in detailRows" :key="row.key">

                                    <!-- No. -->
                                    <td class="text-center text-caption text-medium-emphasis no-col">
                                        {{ idx + 1 }}
                                    </td>

                                    <!-- Item (readonly) -->
                                    <td>
                                        <div class="text-body-2 font-weight-medium py-1">
                                            {{ detailDisplay[idx]?.itemName ?? '-' }}
                                        </div>
                                        <input type="hidden" :value="row.value.item_uid" />
                                        <div v-if="detailError(idx, 'item_uid')" class="text-caption text-error">
                                            {{ detailError(idx, 'item_uid') }}
                                        </div>
                                    </td>

                                    <!-- Unit (readonly) -->
                                    <td class="text-center unit-col">
                                        <v-chip size="x-small" variant="tonal" color="secondary">
                                            {{ detailDisplay[idx]?.unitSymbol ?? '-' }}
                                        </v-chip>
                                        <div v-if="detailError(idx, 'unit_uid')" class="text-caption text-error">
                                            {{ detailError(idx, 'unit_uid') }}
                                        </div>
                                    </td>

                                    <!-- Ordered Qty (readonly) -->
                                    <td class="text-center qty-col">
                                        <span class="text-body-2 font-weight-medium">
                                            {{ detailDisplay[idx]?.orderedQty ?? row.value.qty }}
                                        </span>
                                    </td>

                                    <!-- Return Qty (editable) -->
                                    <td class="py-1 return-col">
                                        <v-number-input
                                            v-model="row.value.return_qty"
                                            :error-messages="detailError(idx, 'return_qty')"
                                            :disabled="loadingForm"
                                            :min="0.01"
                                            :step="0.01"
                                            density="compact"
                                            control-variant="stacked"
                                            variant="outlined"
                                            hide-details="auto"
                                        />
                                    </td>

                                    <!-- Description (editable) -->
                                    <td class="py-1 desc-col">
                                        <v-text-field
                                            v-model="row.value.description"
                                            :disabled="loadingForm"
                                            :placeholder="t('description')"
                                            density="compact"
                                            variant="outlined"
                                            hide-details="auto"
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

            <!-- ── Actions ────────────────────────────────────────────── -->
            <v-card-actions class="px-6 py-4 justify-end gap-2">
                <v-btn
                    variant="tonal"
                    :disabled="isBusy"
                    @click="onClose"
                >
                    {{ t('cancel') }}
                </v-btn>
                <v-btn
                    color="primary"
                    variant="elevated"
                    :loading="saving"
                    :disabled="loadingForm || loadingPO"
                    prepend-icon="mdi-content-save-outline"
                    @click="onSubmit"
                >
                    {{ t('save') }}
                </v-btn>
            </v-card-actions>

        </v-card>
    </v-dialog>
</template>

<style scoped>
.section-header {
    display: flex;
    align-items: center;
    gap: 6px;
}

.detail-table :deep(thead th) {
    font-size: 0.7rem !important;
    font-weight: 700 !important;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    white-space: nowrap;
    background-color: rgba(var(--v-theme-primary), 1) !important;
    color: #ffffff !important;
}

.detail-table :deep(tbody tr:hover td) {
    background-color: rgba(var(--v-theme-primary), 0.04);
}

.detail-table :deep(tbody td) {
    vertical-align: middle;
}

.no-col     { width: 48px;  min-width: 48px;  }
.unit-col   { width: 110px; min-width: 110px; }
.qty-col    { width: 110px; min-width: 110px; }
.return-col { width: 150px; min-width: 150px; }
.desc-col   { min-width: 180px; }
</style>
