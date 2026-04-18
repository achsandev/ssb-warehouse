<script lang="ts" setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useField, useFieldArray, useForm } from 'vee-validate'
import { useTranslate } from '@/composables/useTranslate'
import { itemRequestSchema } from '@/validations'
import { useMessageStore } from '@/stores/message'
// Lookup APIs
import { lookupStocks, lookupUnits } from '@/api/lookup'
// Base components
import BaseAutocomplete from '@/components/base/BaseAutocomplete.vue'
import BaseAsyncSelect from '@/components/base/BaseAsyncSelect.vue'
import type { AsyncFetcher } from '@/components/base/BaseAsyncSelect.vue'
import BaseDateInput from '@/components/base/BaseDateInput.vue'
// Warehouse lookup
import { get as getWarehouseLookup } from '@/api/lookup/warehouse.api'
// Icons
import MdiClose from '~icons/mdi/close'
import MdiClipboardTextOutline from '~icons/mdi/clipboard-text-outline'
import MdiInformationOutline from '~icons/mdi/information-outline'
import MdiPackageVariantClosed from '~icons/mdi/package-variant-closed'
import MdiPlus from '~icons/mdi/plus'
import MdiTrashCanOutline from '~icons/mdi/trash-can-outline'
import MdiContentSaveOutline from '~icons/mdi/content-save-outline'
import MdiBriefcaseOutline from '~icons/mdi/briefcase-outline'
import MdiBriefcaseOffOutline from '~icons/mdi/briefcase-off-outline'
import dayjs from 'dayjs'

// ─── Types ───────────────────────────────────────────────────────────────────
const props = defineProps<{
    modelValue: boolean
    title: string
    data: ItemRequestList
    saving?: boolean
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'submit', values: ItemRequestForm): void
    (e: 'close'): void
}>()

const t = useTranslate()
const message = useMessageStore()

// ─── State ───────────────────────────────────────────────────────────────────
const loadingForm = ref(false)
// Error detail baris baru muncul setelah user klik "Simpan" minimal sekali
const submitAttempted = ref(false)
const allStocks = ref<any[]>([])
const allUnits = ref<any[]>([])
const itemsItem = ref<SelectItem[]>([])
const unitItem = ref<Record<number, SelectItem[]>>({})
const unitKeys = ref<number[]>([0])

// ─── Form ────────────────────────────────────────────────────────────────────
const { handleSubmit, setValues, resetForm, errors } = useForm<ItemRequestForm>({
    validationSchema: itemRequestSchema(t),
})

setValues({
    requirement: props.data.requirement || null,
    request_date: props.data.request_date || dayjs().format('YYYY-MM-DD'),
    unit_code: props.data.unit_code ?? null,
    wo_number: props.data.wo_number ?? null,
    warehouse_uid: props.data.warehouse?.uid ?? null,
    // Default `true` (request proyek) untuk create. Edit memakai nilai server,
    // fallback ke heuristik: row lama yang punya project_name dianggap proyek.
    is_project: props.data.is_project ?? !!props.data.project_name,
    project_name: props.data.project_name ?? null,
    department_name: props.data.department_name || '',
    status: props.data.status || '',
    details: props.data.details?.length
        ? props.data.details.map(d => ({
            item_uid: d.item?.uid ?? null,
            unit_uid: d.unit?.uid ?? null,
            qty: d.qty,
            description: d.description ?? null,
        }))
        : [{ item_uid: null, unit_uid: null, qty: 1, description: null }],
})

const { value: requirement } = useField<string | null>('requirement')
const { value: request_date } = useField<string | Date | null | undefined>('request_date')
const { value: unit_code } = useField<string | null>('unit_code')
const { value: wo_number } = useField<string | null>('wo_number')
const { value: warehouse_uid } = useField<string | null>('warehouse_uid')
const { value: is_project } = useField<boolean>('is_project')
const { value: project_name } = useField<string | null>('project_name')

/**
 * Toggle Project/Non-Project. Saat diset ke non-project, `project_name`
 * dikosongkan supaya payload bersih dan validator tidak salah tangkap
 * sisa value lama.
 */
const handleChangeProjectType = (value: boolean) => {
    is_project.value = value
    if (!value) project_name.value = null
}

// Initial warehouse (untuk edit mode agar label tampil sebelum fetcher load)
type WarehouseOpt = { uid: string; name: string }
const initialWarehouse = ref<WarehouseOpt | null>(
    props.data.warehouse ? { uid: props.data.warehouse.uid, name: props.data.warehouse.name } : null
)

/**
 * Fetcher untuk BaseAsyncSelect: lookup warehouse tidak support pagination
 * server-side, jadi kita selalu muat semua di page=1 dan hasMore=false.
 * Pencarian difilter client-side (lookup API sudah support prefix search juga).
 */
const warehouseFetcher: AsyncFetcher<WarehouseOpt> = async ({ search, page, signal }) => {
    if (page > 1) return { items: [], hasMore: false }
    try {
        const { data } = await getWarehouseLookup()
        let list = (data ?? []) as WarehouseOpt[]
        if (search) {
            const s = search.toLowerCase()
            list = list.filter(w => (w.name ?? '').toLowerCase().includes(s))
        }
        return { items: list, hasMore: false }
    } catch (err: any) {
        if (signal?.aborted) return { items: [], hasMore: false }
        throw err
    }
}
const { value: department_name } = useField<string>('department_name')

const { fields: itemRows, push, remove } = useFieldArray<{
    item_uid: string | null
    unit_uid: string | null
    qty: number
    description: string | null
}>('details')

// ─── Static options ─────────────────────────────────────────────────────────
const requirementItem: SelectItem[] = [
    { title: t('directUse'), value: 'Direct Use' },
    { title: t('replenishment'), value: 'Replenishment' },
]

// ─── Derived ────────────────────────────────────────────────────────────────
const getAvailableItems = (idx: number) => {
    const selectedUids = itemRows.value
        .map((row, i) => i !== idx ? (row.value as any).item_uid : undefined)
        .filter(Boolean)
    return itemsItem.value.filter(item => !selectedUids.includes(item.value))
}

const detailError = (idx: number, field: string): string =>
    (errors.value as Record<string, string>)[`details[${idx}].${field}`] ?? ''

// ─── Loaders ────────────────────────────────────────────────────────────────
/**
 * Opsi item difilter berdasarkan gudang yang dipilih:
 * hanya item yang memiliki stok di gudang tersebut yang muncul.
 * Jika belum ada gudang dipilih, daftar dikosongkan.
 */
const loadItemData = () => {
    const whUid = warehouse_uid.value
    if (!whUid) {
        itemsItem.value = []
        return
    }
    const seen = new Set<string>()
    itemsItem.value = allStocks.value
        .filter(stock => {
            if (stock.warehouse?.uid !== whUid) return false
            const itemUid = stock.item?.uid
            if (!itemUid || seen.has(itemUid)) return false
            seen.add(itemUid)
            return true
        })
        .map(stock => ({
            title: stock.item?.name || '',
            value: stock.item?.uid || '',
        }))
}

// Saat gudang berubah, refresh daftar item dan reset pilihan baris
// agar tidak ada item yang ter-submit dari gudang lain.
watch(warehouse_uid, (_newVal, oldVal) => {
    loadItemData()
    if (oldVal !== undefined) {
        itemRows.value.forEach((row, idx) => {
            ;(row.value as any).item_uid = null
            ;(row.value as any).unit_uid = null
            unitItem.value[idx] = []
            unitKeys.value[idx] = (unitKeys.value[idx] || 0) + 1
        })
    }
})

const loadUnitData = async (idx: number, stock_units?: any[]) => {
    if (stock_units && stock_units.length > 0) {
        unitItem.value[idx] = stock_units.map(u => ({
            title: `${u.unit_name} (${u.unit_symbol})`,
            value: u.unit_uid,
        }))
    } else {
        unitItem.value[idx] = allUnits.value.map(u => ({
            title: `${u.name} (${u.symbol})`,
            value: u.uid,
        }))
    }

    const row = itemRows.value[idx]
    if (row && unitItem.value[idx]?.length > 0 && !(row.value as any).unit_uid) {
        (row.value as any).unit_uid = unitItem.value[idx][0].value
    }
}

// ─── Row management ─────────────────────────────────────────────────────────
const addItemRow = () => {
    push({ item_uid: null, unit_uid: null, qty: 1, description: null })
    unitKeys.value.push(0)
}

const removeItemRow = (idx: number) => {
    if (itemRows.value.length > 1) {
        remove(idx)
        unitKeys.value.splice(idx, 1)
    }
}

if (props.data?.details?.length) {
    unitKeys.value = props.data.details.map(() => 0)
}

// ─── Handlers ───────────────────────────────────────────────────────────────
const handleSelectItem = async (value: string | null, idx: number) => {
    const row = itemRows.value[idx]
    if (!row) return

    ;(row.value as any).unit_uid = undefined

    if (!value) return

    ;(row.value as any).item_uid = value
    unitKeys.value[idx] = (unitKeys.value[idx] || 0) + 1

    const stock = allStocks.value.find(s => s.item?.uid === value)
    if (!stock) {
        await loadUnitData(idx)
        return
    }

    if (stock.item?.uid) {
        (row.value as any).stock_uid = stock.item.uid
    }

    // Replenishment: pakai base unit saja. Direct Use: semua stock_units.
    if (requirement.value !== 'Direct Use' && stock.unit) {
        await loadUnitData(idx, [{
            unit_uid: stock.unit.uid,
            unit_name: stock.unit.name,
            unit_symbol: stock.unit.symbol,
        }])
    } else {
        await loadUnitData(idx, stock.stock_units ?? undefined)
    }
}

// ─── Totals (untuk summary di footer) ───────────────────────────────────────
// Hanya count row yang sudah punya item (bukan row kosong).
const totalItems = computed(() =>
    itemRows.value.filter(r => !!(r.value as any).item_uid).length
)
const totalQty = computed(() =>
    itemRows.value.reduce((sum, r) => sum + (Number((r.value as any).qty) || 0), 0)
)

// ─── Lifecycle ──────────────────────────────────────────────────────────────
onMounted(async () => {
    loadingForm.value = true
    try {
        const [stocks, units] = await Promise.all([lookupStocks(), lookupUnits()])
        allStocks.value = stocks
        allUnits.value = units

        loadItemData()

        // Edit mode — load unit per row
        if (props.data?.uid && itemRows.value.length) {
            itemRows.value.forEach((row, idx) => {
                const stock = allStocks.value.find(s => s.item?.uid === (row.value as any).item_uid)
                if (stock) {
                    loadUnitData(idx, stock.stock_units ?? undefined)
                } else {
                    loadUnitData(idx)
                }
            })
        }
    } catch {
        message.setMessage({
            text: t('loadingFormError'),
            timeout: 3000,
            color: 'error',
        })
    } finally {
        loadingForm.value = false
    }
})

// ─── Submit ──────────────────────────────────────────────────────────────────
const submit = () => {
    submitAttempted.value = true
    handleSubmit((values) => {
        emit('submit', values)
    })()
}

const handleClose = () => {
    submitAttempted.value = false
    resetForm()
    emit('close')
}
</script>

<template>
    <v-dialog
        :model-value="props.modelValue"
        max-width="1300"
        persistent
        scrollable
    >
        <v-card class="rounded-lg d-flex flex-column" :loading="loadingForm || props.saving" style="max-height: 92vh;">
            <!-- Header -->
            <v-card-title class="d-flex align-center ga-2 px-4 px-sm-6 py-3 py-sm-4">
                <v-icon :icon="MdiClipboardTextOutline" color="primary" class="shrink-0" />
                <span class="text-subtitle-1 text-sm-h6 font-weight-semibold text-truncate">{{ props.title }}</span>
                <v-spacer />
                <v-btn
                    :icon="MdiClose"
                    variant="text"
                    size="small"
                    class="shrink-0 ms-auto"
                    :disabled="loadingForm || props.saving"
                    @click="handleClose"
                />
            </v-card-title>

            <v-divider />

            <v-form @submit.prevent="submit" class="d-flex flex-column grow" style="min-height: 0;">
                <v-card-text class="px-4 px-sm-6 pt-6 pt-sm-8 pb-4 pb-sm-6 grow overflow-y-auto">
                    <!-- ════ Section 1: Informasi Umum ════ -->
                    <div class="section-header mb-3">
                        <v-icon :icon="MdiInformationOutline" size="18" color="primary" />
                        <span class="text-subtitle-2 font-weight-bold text-primary">{{ t('generalInfo') }}</span>
                    </div>

                    <!-- Toggle: Proyek vs Non-Proyek (ditempatkan SEBELUM requirement). -->
                    <div class="project-toggle mb-3" role="radiogroup" :aria-label="t('projectType')">
                        <button
                            type="button"
                            class="project-chip"
                            :class="{ 'project-chip--active': is_project === true }"
                            :aria-pressed="is_project === true"
                            :disabled="loadingForm || props.saving"
                            @click="handleChangeProjectType(true)"
                        >
                            <v-icon :icon="MdiBriefcaseOutline" size="18" />
                            <span>{{ t('project') }}</span>
                        </button>
                        <button
                            type="button"
                            class="project-chip"
                            :class="{ 'project-chip--active': is_project === false }"
                            :aria-pressed="is_project === false"
                            :disabled="loadingForm || props.saving"
                            @click="handleChangeProjectType(false)"
                        >
                            <v-icon :icon="MdiBriefcaseOffOutline" size="18" />
                            <span>{{ t('nonProject') }}</span>
                        </button>
                    </div>

                    <v-row dense>
                        <v-col cols="12" sm="6" md="3">
                            <base-autocomplete
                                v-model="requirement"
                                :items="requirementItem"
                                :label="t('requirement')"
                                :error-messages="errors.requirement as any"
                                clearable
                            />
                        </v-col>
                        <v-col cols="12" sm="6" md="3">
                            <base-date-input
                                v-model="request_date"
                                :label="t('requestDate')"
                            />
                        </v-col>
                        <v-col cols="12" sm="6" md="3">
                            <v-text-field
                                v-model="unit_code"
                                :label="`${t('unitCode')} (${t('optional')})`"
                                variant="outlined"
                                density="comfortable"
                                autocomplete="off"
                                hide-details="auto"
                                :error-messages="errors.unit_code as any"
                            />
                        </v-col>
                        <v-col cols="12" sm="6" md="3">
                            <v-text-field
                                v-model="wo_number"
                                :label="`${t('woNumber')} (${t('optional')})`"
                                variant="outlined"
                                density="comfortable"
                                autocomplete="off"
                                hide-details="auto"
                                :error-messages="errors.wo_number as any"
                            />
                        </v-col>
                        <v-col cols="12" md="6">
                            <base-async-select
                                v-model="warehouse_uid"
                                :fetcher="warehouseFetcher"
                                :label="t('warehouse')"
                                item-title="name"
                                item-value="uid"
                                :initial-selected="initialWarehouse"
                                :error-messages="errors.warehouse_uid as any"
                                density="comfortable"
                            />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="project_name"
                                :label="is_project ? t('projectName') : `${t('projectName')} (${t('nonProject')})`"
                                variant="outlined"
                                density="comfortable"
                                autocomplete="off"
                                hide-details="auto"
                                :disabled="!is_project || loadingForm || props.saving"
                                :placeholder="is_project ? '' : '—'"
                                :error-messages="errors.project_name as any"
                            />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="department_name"
                                :label="t('departmentName')"
                                variant="outlined"
                                density="comfortable"
                                autocomplete="off"
                                hide-details="auto"
                                :error-messages="errors.department_name as any"
                            />
                        </v-col>
                    </v-row>

                    <v-divider class="my-5" />

                    <!-- ════ Section 2: Detail Barang ════ -->
                    <div class="d-flex align-center justify-space-between mb-3 flex-wrap ga-2">
                        <div class="section-header">
                            <v-icon :icon="MdiPackageVariantClosed" size="18" color="primary" />
                            <span class="text-subtitle-2 font-weight-bold text-primary">{{ t('details') }}</span>
                            <v-chip size="x-small" color="primary" variant="tonal" class="ms-2">
                                {{ itemRows.length }}
                            </v-chip>
                        </div>
                        <v-btn
                            size="small"
                            color="primary"
                            variant="tonal"
                            :prepend-icon="MdiPlus"
                            :disabled="!requirement"
                            @click="addItemRow"
                        >
                            {{ t('addItem') }}
                        </v-btn>
                    </div>

                    <v-alert
                        v-if="!requirement"
                        type="info"
                        variant="tonal"
                        density="compact"
                        class="mb-3"
                        :text="t('selectRequirementFirst')"
                    />

                    <v-sheet rounded="lg" border class="overflow-hidden">
                        <v-table density="compact" class="detail-table">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 40px;">No.</th>
                                    <th>{{ t('item') }}</th>
                                    <th style="width: 200px;">{{ t('unit') }}</th>
                                    <th class="text-end" style="width: 120px;">{{ t('qty') }}</th>
                                    <th>{{ t('description') }}</th>
                                    <th style="width: 56px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(row, idx) in itemRows" :key="row.key">
                                    <td class="text-center text-caption text-medium-emphasis">{{ idx + 1 }}</td>
                                    <td class="py-2">
                                        <base-autocomplete
                                            :items="getAvailableItems(idx)"
                                            :label="t('item')"
                                            v-model="(row.value as any).item_uid"
                                            :error-messages="submitAttempted ? detailError(idx, 'item_uid') : undefined"
                                            :disabled="!requirement"
                                            density="compact"
                                            @update:model-value="(val) => handleSelectItem(val, idx)"
                                        />
                                    </td>
                                    <td class="py-2">
                                        <base-autocomplete
                                            :key="unitKeys[idx]"
                                            :items="unitItem[idx] || []"
                                            :label="t('unit')"
                                            v-model="(row.value as any).unit_uid"
                                            :clearable="requirement === 'Direct Use'"
                                            :error-messages="submitAttempted ? detailError(idx, 'unit_uid') : undefined"
                                            :disabled="!(row.value as any).item_uid || (requirement !== 'Direct Use' && unitItem[idx]?.length === 1)"
                                            density="compact"
                                        />
                                    </td>
                                    <td class="py-2">
                                        <v-number-input
                                            v-model="(row.value as any).qty"
                                            :error-messages="submitAttempted ? detailError(idx, 'qty') : undefined"
                                            :min="1"
                                            :disabled="!(row.value as any).item_uid"
                                            variant="outlined"
                                            density="compact"
                                            hide-details="auto"
                                            control-variant="stacked"
                                        />
                                    </td>
                                    <td class="py-2">
                                        <v-text-field
                                            v-model="(row.value as any).description"
                                            :placeholder="t('description')"
                                            variant="outlined"
                                            density="compact"
                                            hide-details="auto"
                                        />
                                    </td>
                                    <td class="text-center">
                                        <v-btn
                                            :icon="MdiTrashCanOutline"
                                            variant="text"
                                            size="x-small"
                                            color="error"
                                            :disabled="itemRows.length <= 1"
                                            @click="removeItemRow(idx)"
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </v-table>
                    </v-sheet>

                    <!-- Summary footer -->
                    <v-row dense class="mt-3">
                        <v-col cols="6" sm="3">
                            <v-sheet rounded="lg" border class="pa-3">
                                <div class="text-overline">{{ t('totalItems') }}</div>
                                <div class="text-body-2 font-weight-medium">{{ totalItems }}</div>
                            </v-sheet>
                        </v-col>
                        <v-col cols="6" sm="3">
                            <v-sheet rounded="lg" border class="pa-3">
                                <div class="text-overline">{{ t('totalQty') }}</div>
                                <div class="text-body-2 font-weight-medium">{{ totalQty }}</div>
                            </v-sheet>
                        </v-col>
                    </v-row>
                </v-card-text>

                <v-divider />

                <v-card-actions class="pa-4 justify-end ga-2">
                    <v-btn variant="tonal" :disabled="loadingForm || props.saving" @click="handleClose">
                        {{ t('cancel') }}
                    </v-btn>
                    <v-btn
                        type="submit"
                        color="primary"
                        variant="elevated"
                        :loading="props.saving"
                        :disabled="loadingForm"
                        :prepend-icon="MdiContentSaveOutline"
                    >
                        {{ t('save') }}
                    </v-btn>
                </v-card-actions>
            </v-form>
        </v-card>
    </v-dialog>
</template>

<style scoped>
.section-header {
    display: flex;
    align-items: center;
    gap: 8px;
}

.detail-table :deep(td) {
    vertical-align: middle;
}

.detail-table :deep(.v-input__details) {
    min-height: 18px;
    padding-top: 2px;
}

/* ════ Project / Non-Project segmented toggle ════ */
.project-toggle {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
    max-width: 480px;
}
.project-chip {
    all: unset;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 48px;
    padding: 10px 14px;
    border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
    border-radius: 12px;
    background: rgb(var(--v-theme-surface));
    font-size: 0.85rem;
    font-weight: 500;
    color: rgba(var(--v-theme-on-surface), 0.75);
    text-align: center;
    transition:
        border-color 0.15s ease,
        background 0.15s ease,
        color 0.15s ease,
        transform 0.15s ease;
}
.project-chip:hover:not(:disabled) {
    border-color: rgb(var(--v-theme-primary));
    color: rgb(var(--v-theme-primary));
    transform: translateY(-1px);
}
.project-chip:focus-visible {
    outline: 2px solid rgb(var(--v-theme-primary));
    outline-offset: 2px;
}
.project-chip:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}
.project-chip--active {
    border-color: rgb(var(--v-theme-primary));
    background: color-mix(in srgb, rgb(var(--v-theme-primary)) 10%, transparent);
    color: rgb(var(--v-theme-primary));
    font-weight: 700;
}

@media (max-width: 500px) {
    .project-toggle {
        grid-template-columns: 1fr;
    }
    .project-chip {
        justify-content: flex-start;
        min-height: 44px;
    }
}
</style>
