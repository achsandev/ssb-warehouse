<script lang="ts" setup>
import { computed, onMounted, ref } from 'vue'
import { useField, useFieldArray, useForm } from 'vee-validate'
import { useTranslate } from '@/composables/useTranslate'
import { itemUsageSchema } from '@/validations/itemUsageSchema'
import { useMessageStore } from '@/stores/message'
import { get as getItemRequestLookup, show as getItemRequestByUid } from '@/api/lookup/item_request.api'
import { get as getItemUsage } from '@/api/item_usage.api'
import { get as getStockLookup } from '@/api/lookup/stock.api'
import BaseAutocomplete from '@/components/base/BaseAutocomplete.vue'
import BaseDateInput from '@/components/base/BaseDateInput.vue'
import dayjs from 'dayjs'

const props = defineProps<{
    modelValue: boolean
    title: string
    data: ItemUsageList
    saving?: boolean
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'submit', values: ItemUsageForm): void
    (e: 'close'): void
}>()

const t = useTranslate()
const message = useMessageStore()

// ─── Types ───────────────────────────────────────────────────────────────────
type DetailDisplay = { itemName: string; unitSymbol: string; requestedQty: number }

type ItemRequestOption = { title: string; value: string }

// ─── State ───────────────────────────────────────────────────────────────────
const loadingForm          = ref(false)
const loadingItemRequest   = ref(false)
const itemRequestOptions   = ref<ItemRequestOption[]>([])
const detailDisplay        = ref<DetailDisplay[]>([])

const isEdit  = computed(() => !!props.data.uid)
const isBusy  = computed(() => loadingForm.value || loadingItemRequest.value || !!props.saving)

// ─── Form ────────────────────────────────────────────────────────────────────
const { handleSubmit, setValues, resetForm, errors } = useForm<ItemUsageForm>({
    validationSchema: itemUsageSchema(t),
})

// Build initial detail rows + display data from an existing record (edit mode)
const buildInitialDetails = () => {
    if (!props.data.details?.length) {
        detailDisplay.value = []
        return []
    }
    detailDisplay.value = props.data.details.map((d) => ({
        itemName: d.item ? `${d.item.code} - ${d.item.name}` : '-',
        unitSymbol: d.unit?.symbol ?? '-',
        requestedQty: d.qty,
    }))
    return props.data.details.map((d) => ({
        item_uid:    d.item?.uid ?? null,
        unit_uid:    d.unit?.uid ?? null,
        qty:         d.qty,
        usage_qty:   d.usage_qty ?? null,
        description: d.description ?? '',
    }))
}

setValues({
    item_request_uid: props.data.item_request?.uid ?? null,
    usage_date: props.data.usage_date
        ? dayjs(props.data.usage_date as string).format('YYYY-MM-DD')
        : dayjs().format('YYYY-MM-DD'),
    project_name: props.data.project_name ?? null,
    details: buildInitialDetails(),
})

// ─── Field bindings ──────────────────────────────────────────────────────────
const { value: item_request_uid } = useField<string | null>('item_request_uid')
const { value: usage_date }       = useField<string>('usage_date')
const { value: project_name }     = useField<string | null>('project_name')

const { fields: detailRows, replace: replaceRows } = useFieldArray<{
    item_uid: string | null
    unit_uid: string | null
    qty: number
    usage_qty: number | null
    description: string
}>('details')

// ─── Helpers ─────────────────────────────────────────────────────────────────
const detailError = (idx: number, field: string): string =>
    (errors.value as Record<string, string>)[`details[${idx}].${field}`] ?? ''

// ─── Item Request auto-fill ──────────────────────────────────────────────────
const onItemRequestSelected = async (uid: string | null) => {
    if (!uid) {
        replaceRows([])
        detailDisplay.value = []
        project_name.value = null
        return
    }

    loadingItemRequest.value = true
    try {
        const ir = await getItemRequestByUid(uid)
        project_name.value = ir.project_name ?? null

        const incoming: ItemRequestDetail[] = ir.details ?? []
        detailDisplay.value = incoming.map((d) => ({
            itemName: d.item ? `${d.item.code} - ${d.item.name}` : '-',
            unitSymbol: d.unit?.symbol ?? '-',
            requestedQty: d.qty,
        }))
        replaceRows(
            incoming.map((d) => ({
                item_uid:    d.item?.uid ?? null,
                unit_uid:    d.unit?.uid ?? null,
                qty:         d.qty,
                usage_qty:   null,
                description: d.description ?? '',
            })),
        )
    } catch (err) {
        console.error('[ItemUsageForm] onItemRequestSelected error:', err)
        message.setMessage({ text: t('loadingFormError'), timeout: 3000, color: 'error' })
    } finally {
        loadingItemRequest.value = false
    }
}

// ─── Loaders ─────────────────────────────────────────────────────────────────
const loadItemRequests = async () => {
    const [lookupRes, usageRes, stockRes] = await Promise.all([
        getItemRequestLookup(),
        getItemUsage({ page: 1, per_page: -1, sort_by: 'created_at', sort_dir: 'desc', search: '' }),
        getStockLookup(),
    ])

    const stocks = (stockRes.data?.data ?? stockRes.data ?? []) as any[]

    // Collect item_request UIDs that are already used (exclude rejected — those can be reused)
    const usedUids = new Set<string>(
        ((usageRes.data?.data ?? usageRes.data ?? []) as ItemUsageList[])
            .filter((u) => (u.status ?? '').toLowerCase() !== 'rejected')
            .map((u) => u.item_request?.uid)
            .filter(Boolean) as string[]
    )

    const all = (lookupRes.data ?? []) as any[]
    const options: ItemRequestOption[] = all
        .filter((r) => {
            if ((r.requirement ?? '').toLowerCase() !== 'direct use') return false
            if ((r.status ?? '').toLowerCase() !== 'approved') return false
            if (!isEdit.value && usedUids.has(r.uid)) return false

            // Semua detail harus punya qty <= stock yang tersedia
            const details = r.details ?? []
            if (details.length === 0) return false
            return details.every((d: any) => {
                const stock = stocks.find((s: any) => s.item && s.item.uid === d.item?.uid)
                let stockQty = 0
                if (stock && Array.isArray(stock.stock_units) && stock.stock_units.length > 0) {
                    const su = stock.stock_units.find((u: any) => u.unit_uid === d.unit?.uid)
                    stockQty = su ? su.qty : 0
                }
                return d.qty <= stockQty
            })
        })
        .map((r) => ({
            title: r.project_name ? `${r.request_number} | ${r.project_name}` : r.request_number,
            value: r.uid,
        }))

    // In edit mode re-inject current item request if BE already filtered it out
    if (isEdit.value && props.data.item_request) {
        const alreadyIncluded = options.some((o) => o.value === props.data.item_request!.uid)
        if (!alreadyIncluded) {
            options.unshift({
                title: props.data.item_request.request_number,
                value: props.data.item_request.uid,
            })
        }
    }

    itemRequestOptions.value = options
}

// ─── Lifecycle ────────────────────────────────────────────────────────────────
onMounted(async () => {
    loadingForm.value = true
    try {
        await loadItemRequests()
    } catch (err) {
        console.error('[ItemUsageForm] onMounted loadItemRequests error:', err)
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
        <v-card :loading="loadingForm || loadingItemRequest" rounded="lg">

            <!-- ── Title bar ───────────────────────────────────────────── -->
            <v-card-title class="d-flex align-center gap-2 px-6 py-4">
                <v-icon icon="mdi-clipboard-check-outline" color="primary" class="me-1" />
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
                                v-model="item_request_uid"
                                :label="t('itemRequest')"
                                :items="itemRequestOptions"
                                :error-messages="(errors as any).item_request_uid"
                                :disabled="loadingForm || isEdit"
                                :loading="loadingItemRequest"
                                clearable
                                @update:model-value="onItemRequestSelected"
                            />
                        </v-col>

                        <v-col cols="12" md="6">
                            <base-date-input
                                v-model="usage_date"
                                :disabled="loadingForm"
                            />
                        </v-col>

                    </v-row>

                    <!-- ── Section: Item Details ──────────────────────── -->
                    <v-divider class="mt-1 mb-4" />

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
                                    <th class="text-center qty-col">{{ t('requestedQty') }}</th>
                                    <th class="text-center usage-col">{{ t('usageQty') }}</th>
                                    <th class="desc-col">{{ t('description') }}</th>
                                </tr>
                            </thead>
                            <tbody>

                                <!-- Empty state -->
                                <tr v-if="detailRows.length === 0">
                                    <td colspan="6" class="pa-8 text-center">
                                        <div class="d-flex flex-column align-center gap-2 text-medium-emphasis">
                                            <v-icon icon="mdi-clipboard-text-outline" size="48" />
                                            <span class="text-body-2">{{ t('selectItemRequestFirst') }}</span>
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

                                    <!-- Requested Qty (readonly) -->
                                    <td class="text-center qty-col">
                                        <span class="text-body-2 font-weight-medium">
                                            {{ detailDisplay[idx]?.requestedQty ?? row.value.qty }}
                                        </span>
                                    </td>

                                    <!-- Usage Qty (editable) -->
                                    <td class="py-1 usage-col">
                                        <v-number-input
                                            v-model="row.value.usage_qty"
                                            :error-messages="detailError(idx, 'usage_qty')"
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
                    :disabled="loadingForm || loadingItemRequest"
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

.no-col    { width: 48px;  min-width: 48px;  }
.unit-col  { width: 110px; min-width: 110px; }
.qty-col   { width: 110px; min-width: 110px; }
.usage-col { width: 150px; min-width: 150px; }
.desc-col  { min-width: 180px; }
</style>
