<script lang="ts" setup>
import { computed, nextTick, onMounted, ref } from 'vue'
import { useField, useFieldArray, useForm } from 'vee-validate'
import dayjs from 'dayjs'
// Composables
import { useTranslate } from '@/composables/useTranslate'
// Validations
import { receiveItemSchema } from '@/validations/receiveItemForm'
// Stores
import { useMessageStore } from '@/stores/message'
import { useItemLookupStore } from '@/stores/lookup/item.store'
import { useUnitLookupStore } from '@/stores/lookup/unit.store'
import { usePurchaseOrderLookupStore } from '@/stores/lookup/purchase_order.store'
import { useStockLookupStore } from '@/stores/lookup/stock.store'
import { useSupplierLookupStore } from '@/stores/lookup/supplier.store'
// APIs
import { get as getReceiveItemLookup } from '@/api/lookup/receive_item.api'
import { get as getWarehouseLookup } from '@/api/lookup/warehouse.api'
import { show as getPurchaseOrderByUid } from '@/api/lookup/purchase_order.api'
// Components
import BaseAutocomplete from '@/components/base/BaseAutocomplete.vue'
import BaseDateInput from '@/components/base/BaseDateInput.vue'
import BaseCurrencyInput from '@/components/base/BaseCurrencyInput.vue'
// Utils
import { formatRupiah } from '@/utils/currency'
// Icons
import MdiTruckDelivery from '~icons/mdi/truck-delivery'
import MdiClose from '~icons/mdi/close'
import MdiInformationOutline from '~icons/mdi/information-outline'
import MdiClipboardListOutline from '~icons/mdi/clipboard-list-outline'

// ─── Types ───────────────────────────────────────────────────────────────────
interface DetailRow {
    item_id: string | null
    unit_id: string | null
    supplier_id: string | null
    qty: number
    price: number
    total: number
    qty_received: number
}

interface UidNamed { uid: string; name?: string; symbol?: string }

// ─── Props / Emits ───────────────────────────────────────────────────────────
const props = defineProps<{
    modelValue: boolean
    title: string
    data: ReceiveItemList
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'submit', values: ReceiveItemForm): void
    (e: 'close'): void
}>()

// ─── Composables & Stores ────────────────────────────────────────────────────
const t = useTranslate()
const message = useMessageStore()
const purchaseOrderStore = usePurchaseOrderLookupStore()
const itemsStore = useItemLookupStore()
const itemUnitsStore = useUnitLookupStore()
const supplierStore = useSupplierLookupStore()
const stockStore = useStockLookupStore()

// ─── State ───────────────────────────────────────────────────────────────────
const loadingForm = ref(false)
const purchaseOrderItem = ref<SelectItem[]>([])
const warehouseItem = ref<SelectItem[]>([])
const itemsItem = ref<SelectItem[]>([])
const supplierItem = ref<SelectItem[]>([])
const unitItem = ref<Record<number, SelectItem[]>>({})
const unitKeys = ref<number[]>([])

// ─── Form ────────────────────────────────────────────────────────────────────
const { handleSubmit, setValues, resetForm, errors } = useForm<ReceiveItemForm>({
    validationSchema: receiveItemSchema(t),
})

const mapInitialDetails = (): DetailRow[] => {
    const source = props.data.details ?? []
    if (source.length === 0) {
        return [{ item_id: null, unit_id: null, supplier_id: null, qty: 1, price: 0, total: 0, qty_received: 0 }]
    }
    return source.map((d) => ({
        item_id: (d.item as UidNamed | null)?.uid ?? null,
        unit_id: (d.unit as UidNamed | null)?.uid ?? null,
        supplier_id: (d.supplier as UidNamed | null)?.uid ?? null,
        qty: d.qty ?? 0,
        price: d.price ?? 0,
        total: d.total ?? 0,
        qty_received: d.qty_received ?? 0,
    }))
}

setValues({
    receipt_date: props.data.receipt_date || dayjs().format('YYYY-MM-DD'),
    project_name: props.data.project_name || '',
    purchase_order_id: props.data.purchase_order?.uid || null,
    warehouse_id: props.data.warehouse?.uid || null,
    shipping_cost: props.data.shipping_cost ?? 0,
    additional_info: props.data.additional_info || '',
    details: mapInitialDetails(),
})

const { fields: itemRows } = useFieldArray<DetailRow>('details')

const { value: receipt_date } = useField<string | Date | null | undefined>('receipt_date')
const { value: purchase_order_id } = useField<string | null>('purchase_order_id')
const { value: warehouse_id } = useField<string | null>('warehouse_id')
const { value: shipping_cost } = useField<number>('shipping_cost')

// ─── Helpers ─────────────────────────────────────────────────────────────────
/** Map API list to Vuetify select items dengan mengabaikan entry tanpa uid. */
const toSelectItems = <T extends { uid?: string | null }>(
    list: T[],
    toTitle: (row: T) => string,
): SelectItem[] =>
    list
        .filter((row): row is T & { uid: string } => !!row.uid)
        .map((row) => ({ title: toTitle(row), value: row.uid }))

/** Safely read the cell value of a detail row for template casts. */
const rowValue = <K extends keyof DetailRow>(idx: number, key: K): DetailRow[K] | undefined =>
    (itemRows.value[idx]?.value as DetailRow | undefined)?.[key]

const setRowValue = <K extends keyof DetailRow>(idx: number, key: K, value: DetailRow[K]) => {
    const row = itemRows.value[idx]?.value as DetailRow | undefined
    if (row) row[key] = value
}

const notifyError = (scope: string) =>
    message.setMessage({ text: `${t('loadingFormError')} (${scope})`, timeout: 3000, color: 'error' })

// ─── Computeds ───────────────────────────────────────────────────────────────
const subtotal = computed(() =>
    (itemRows.value ?? []).reduce((sum, row) => {
        const v = row.value as DetailRow
        return sum + (Number(v.qty) || 0) * (Number(v.price) || 0)
    }, 0),
)

const grandTotal = computed(() => subtotal.value + (Number(shipping_cost.value) || 0))

const totalQty = computed(() =>
    (itemRows.value ?? []).reduce((sum, row) => sum + (Number((row.value as DetailRow).qty) || 0), 0),
)

const totalReceived = computed(() =>
    (itemRows.value ?? []).reduce((sum, row) => sum + (Number((row.value as DetailRow).qty_received) || 0), 0),
)

// ─── Loaders ─────────────────────────────────────────────────────────────────
/** PO yang sudah dipakai Receive Item lain harus disembunyikan dari pilihan. */
const loadPurchaseOrderData = async () => {
    try {
        const { data: receipts = [] } = await getReceiveItemLookup()
        const usedPoUids = new Set<string>()
        for (const r of receipts as Array<{ purchase_order?: { uid?: string } }>) {
            const uid = r.purchase_order?.uid
            if (uid && uid !== props.data?.purchase_order?.uid) usedPoUids.add(uid)
        }

        purchaseOrderItem.value = purchaseOrderStore.data
            .filter((po) => po.status?.toLowerCase() === 'approved' && !usedPoUids.has(po.uid))
            .map((po) => ({ title: `${po.po_number} | ${po.project_name}`, value: po.uid }))
    } catch {
        notifyError('loadPurchaseOrderData')
    }
}

const loadWarehouseData = async () => {
    try {
        const { data = [] } = await getWarehouseLookup()
        warehouseItem.value = toSelectItems(data as UidNamed[], (w) => w.name ?? '-')
    } catch {
        notifyError('loadWarehouseData')
    }
}

const loadItemData = () => {
    itemsItem.value = toSelectItems(itemsStore.data as UidNamed[], (i) => i.name ?? '-')
}

const loadSupplierData = () => {
    supplierItem.value = toSelectItems(supplierStore.data as UidNamed[], (s) => s.name ?? '-')
}

/** Populate unit dropdown untuk satu row berdasarkan stock_units atau fallback master. */
const loadUnitData = (idx: number, stockUnits?: Array<{ unit_uid?: string; unit_name?: string; unit_symbol?: string }>) => {
    const units: SelectItem[] = (stockUnits?.length
        ? stockUnits.map((u) => ({
            title: u.unit_name && u.unit_symbol ? `${u.unit_name} (${u.unit_symbol})` : (u.unit_name || u.unit_uid || ''),
            value: u.unit_uid ?? '',
        }))
        : itemUnitsStore.data.map((u) => ({
            title: `${u.name} (${u.symbol})`,
            value: u.uid,
        }))
    ).filter((u) => !!u.value)

    unitItem.value[idx] = units
    if (units.length > 0 && !rowValue(idx, 'unit_id')) {
        setRowValue(idx, 'unit_id', String(units[0].value))
    }
}

// ─── Handlers ────────────────────────────────────────────────────────────────
const handleSelectItemRequest = async (value: unknown) => {
    const uid = typeof value === 'string' ? value : null
    purchase_order_id.value = uid
    loadItemData()
    loadSupplierData()

    if (!uid) return

    try {
        const req = await getPurchaseOrderByUid(uid)
        if (!req) return

        const source: any[] = Array.isArray(req.details) ? req.details : []
        const defaultSupplierId = supplierItem.value[0]?.value ?? ''

        setValues({
            project_name: req.project_name || '',
            details: source.map((d) => ({
                item_id: d.item?.uid ?? '',
                unit_id: d.unit?.uid ?? '',
                supplier_id: d.supplier?.uid ?? defaultSupplierId,
                qty: d.qty ?? 0,
                price: d.price ?? 0,
                total: d.total ?? 0,
                qty_received: 0,
            })),
        })

        unitKeys.value = source.map(() => 0)

        // Tambah item / supplier yang mungkin belum ada di master dropdown agar nama tampil benar.
        source.forEach((d, idx) => {
            if (d.item?.uid && !itemsItem.value.some((i) => i.value === d.item.uid)) {
                itemsItem.value.push({ title: d.item.name || d.item.uid, value: d.item.uid })
            }
            if (d.unit?.uid) {
                unitItem.value[idx] = [{
                    title: `${d.unit.name || ''} (${d.unit.symbol || ''})`,
                    value: d.unit.uid,
                }]
            }
            if (d.supplier?.uid && !supplierItem.value.some((s) => s.value === d.supplier.uid)) {
                supplierItem.value.push({ title: d.supplier.name || d.supplier.uid, value: d.supplier.uid })
            }
        })
    } catch {
        notifyError('handleSelectItemRequest')
    }
}

const asUid = (value: unknown): string | null =>
    typeof value === 'string' && value.length > 0 ? value : null

const handleSelectWarehouse = (value: unknown) => {
    warehouse_id.value = asUid(value)
}

const handleSelectItem = async (value: unknown, idx: number) => {
    setRowValue(idx, 'unit_id', null)
    const uid = asUid(value)
    if (!uid) return

    setRowValue(idx, 'item_id', uid)
    unitKeys.value[idx] = (unitKeys.value[idx] ?? 0) + 1

    const stock = stockStore.data.find((s) => s.item?.uid === uid)
    if (!stock) return

    if (Array.isArray(stock.stock_units) && stock.stock_units.length > 0) {
        loadUnitData(idx, stock.stock_units as any[])
    } else if (stock.unit) {
        loadUnitData(idx, [{ unit_uid: stock.unit.uid, unit_name: stock.unit.name, unit_symbol: stock.unit.symbol }])
    } else {
        loadUnitData(idx)
    }
}

const handleSelectUnit = (value: unknown, idx: number) => setRowValue(idx, 'unit_id', asUid(value))
const handleSelectSupplier = (value: unknown, idx: number) => setRowValue(idx, 'supplier_id', asUid(value))

const submit = handleSubmit((values) => emit('submit', values))

const handleClose = () => {
    resetForm()
    emit('close')
}

// ─── Lifecycle ───────────────────────────────────────────────────────────────
onMounted(async () => {
    loadingForm.value = true
    try {
        await Promise.all([
            itemsStore.fetch(),
            itemUnitsStore.fetch(),
            supplierStore.fetch(),
            stockStore.fetch(),
            purchaseOrderStore.fetch(),
        ])

        await Promise.all([loadPurchaseOrderData(), loadWarehouseData()])
        loadItemData()
        loadSupplierData()

        const sourceDetails = props.data.details ?? []
        unitKeys.value = sourceDetails.length > 0 ? sourceDetails.map(() => 0) : [0]

        await nextTick()

        sourceDetails.forEach((d, idx) => {
            const unit = d.unit as UidNamed | null
            if (unit?.uid) {
                unitItem.value[idx] = [{
                    title: `${unit.name ?? ''} (${unit.symbol ?? ''})`,
                    value: unit.uid,
                }]
            }
        })

        if (!receipt_date.value) receipt_date.value = dayjs().format('YYYY-MM-DD')
    } catch {
        notifyError('onMounted')
    } finally {
        loadingForm.value = false
    }
})
</script>

<template>
    <v-dialog
        persistent
        max-width="1280"
        :model-value="props.modelValue"
        scrollable
        @update:model-value="(value) => { emit('update:modelValue', value); if (!value) handleClose() }"
    >
        <v-card class="receive-form-card rounded-lg" :loading="loadingForm">
            <!-- Header -->
            <v-toolbar color="transparent" density="comfortable" flat>
                <template #prepend>
                    <v-icon :icon="MdiTruckDelivery" class="ms-2" />
                </template>
                <v-toolbar-title class="text-subtitle-1 font-weight-bold">
                    {{ props.title }}
                </v-toolbar-title>
                <template #append>
                    <v-btn
                        :icon="MdiClose"
                        variant="text"
                        density="comfortable"
                        :disabled="loadingForm"
                        :aria-label="t('close')"
                        @click="handleClose"
                    />
                </template>
            </v-toolbar>

            <v-form @submit.prevent="submit">
                <v-card-text class="pa-4 pa-sm-5">
                    <!-- Section: Receipt Info -->
                    <section class="form-section mb-4">
                        <header class="section-header">
                            <v-icon :icon="MdiInformationOutline" size="18" />
                            <span>{{ t('dataForm', { field: t('receiveItem') }) }}</span>
                        </header>
                        <v-divider class="mb-3" />
                        <v-row dense>
                            <v-col cols="12" sm="6">
                                <base-autocomplete
                                    :items="purchaseOrderItem"
                                    :label="t('purchaseOrder')"
                                    v-model="purchase_order_id"
                                    :error-messages="errors.purchase_order_id"
                                    :clearable="true"
                                    @update:model-value="handleSelectItemRequest"
                                />
                            </v-col>
                            <v-col cols="12" sm="6">
                                <base-date-input v-model="receipt_date" readonly />
                            </v-col>
                            <v-col cols="12" sm="6">
                                <base-autocomplete
                                    :items="warehouseItem"
                                    :label="t('warehouse')"
                                    v-model="warehouse_id"
                                    :error-messages="errors.warehouse_id"
                                    :clearable="true"
                                    @update:model-value="handleSelectWarehouse"
                                />
                            </v-col>
                            <v-col cols="12" sm="6">
                                <base-currency-input
                                    v-model="shipping_cost"
                                    :label="t('shippingCost')"
                                    :error-messages="errors.shipping_cost"
                                />
                            </v-col>
                        </v-row>
                    </section>

                    <!-- Section: Details -->
                    <section class="form-section">
                        <header class="section-header">
                            <v-icon :icon="MdiClipboardListOutline" size="18" />
                            <span>{{ t('details') }}</span>
                            <v-spacer />
                            <v-chip size="x-small" variant="tonal" color="primary">
                                {{ itemRows.length }} {{ t('items') }}
                            </v-chip>
                        </header>
                        <v-divider class="mb-3" />

                        <v-alert
                            v-if="!purchase_order_id"
                            type="info"
                            variant="tonal"
                            density="compact"
                            class="mb-3"
                        >
                            {{ t('selectPurchaseOrderFirst') }}
                        </v-alert>

                        <div v-else class="detail-list">
                            <div
                                v-for="(row, idx) in itemRows"
                                :key="row.key"
                                class="detail-row"
                            >
                                <div class="detail-row__index">
                                    <v-chip size="small" variant="tonal" color="primary" class="font-weight-bold">
                                        {{ idx + 1 }}
                                    </v-chip>
                                </div>

                                <v-row dense class="grow ma-0">
                                    <v-col cols="12" sm="6" md="4">
                                        <base-autocomplete
                                            :items="itemsItem"
                                            :label="t('item')"
                                            v-model="(row.value as DetailRow).item_id"
                                            item-title="title"
                                            item-value="value"
                                            :error-messages="errors[`details.${idx}.item_id` as keyof typeof errors]"
                                            :disabled="!purchase_order_id"
                                            readonly
                                            @update:model-value="(val) => handleSelectItem(val, idx)"
                                        />
                                    </v-col>
                                    <v-col cols="6" sm="3" md="2">
                                        <base-autocomplete
                                            :key="unitKeys[idx]"
                                            :items="unitItem[idx] || []"
                                            :label="t('unit')"
                                            v-model="(row.value as DetailRow).unit_id"
                                            item-title="title"
                                            item-value="value"
                                            :error-messages="errors[`details.${idx}.unit_id` as keyof typeof errors]"
                                            :disabled="!purchase_order_id"
                                            readonly
                                            @update:model-value="(val) => handleSelectUnit(val, idx)"
                                        />
                                    </v-col>
                                    <v-col cols="6" sm="3" md="3">
                                        <base-autocomplete
                                            :items="supplierItem"
                                            :label="t('supplier')"
                                            v-model="(row.value as DetailRow).supplier_id"
                                            item-title="title"
                                            item-value="value"
                                            :error-messages="errors[`details.${idx}.supplier_id` as keyof typeof errors]"
                                            :disabled="!purchase_order_id"
                                            @update:model-value="(val) => handleSelectSupplier(val, idx)"
                                        />
                                    </v-col>
                                    <v-col cols="6" sm="6" md="1">
                                        <v-text-field
                                            v-model.number="(row.value as DetailRow).qty"
                                            :label="t('qty')"
                                            type="number"
                                            variant="outlined"
                                            density="comfortable"
                                            hide-details="auto"
                                            min="0"
                                            readonly
                                            :error-messages="errors[`details.${idx}.qty` as keyof typeof errors]"
                                            :disabled="!purchase_order_id"
                                        />
                                    </v-col>
                                    <v-col cols="6" sm="6" md="2">
                                        <v-text-field
                                            v-model.number="(row.value as DetailRow).qty_received"
                                            :label="t('qtyReceived')"
                                            type="number"
                                            variant="outlined"
                                            density="comfortable"
                                            hide-details="auto"
                                            min="0"
                                            :error-messages="errors[`details.${idx}.qty_received` as keyof typeof errors]"
                                            :disabled="!purchase_order_id"
                                        />
                                    </v-col>
                                    <v-col cols="12" md="12">
                                        <base-currency-input
                                            v-model.number="(row.value as DetailRow).price"
                                            :label="t('price')"
                                            :disabled="!purchase_order_id"
                                            :error-messages="errors[`details.${idx}.price` as keyof typeof errors]"
                                        />
                                    </v-col>
                                </v-row>
                            </div>
                        </div>
                    </section>

                    <!-- Summary -->
                    <v-sheet v-if="purchase_order_id" class="summary-sheet mt-4 pa-4 rounded-lg" border>
                        <div class="summary-row">
                            <span class="summary-label">{{ t('qty') }}</span>
                            <span class="summary-value">{{ totalQty.toLocaleString('id-ID') }}</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">{{ t('qtyReceived') }}</span>
                            <span class="summary-value">{{ totalReceived.toLocaleString('id-ID') }}</span>
                        </div>
                        <v-divider class="my-2" />
                        <div class="summary-row">
                            <span class="summary-label">Subtotal</span>
                            <span class="summary-value">{{ formatRupiah(subtotal) }}</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">{{ t('shippingCost') }}</span>
                            <span class="summary-value">{{ formatRupiah(Number(shipping_cost) || 0) }}</span>
                        </div>
                        <v-divider class="my-2" />
                        <div class="summary-row summary-row--total">
                            <span class="summary-label">{{ t('total') }}</span>
                            <span class="summary-value text-primary">{{ formatRupiah(grandTotal) }}</span>
                        </div>
                    </v-sheet>
                </v-card-text>

                <v-divider />

                <v-card-actions class="pa-3 pa-sm-4">
                    <v-spacer />
                    <v-btn
                        variant="text"
                        :text="t('cancel')"
                        :disabled="loadingForm"
                        @click="handleClose"
                    />
                    <v-btn
                        type="submit"
                        color="success"
                        variant="flat"
                        :text="t('save')"
                        :loading="loadingForm"
                        :disabled="loadingForm"
                    />
                </v-card-actions>
            </v-form>
        </v-card>
    </v-dialog>
</template>

<style scoped>
.receive-form-card {
    overflow: hidden;
}

.form-section {
    background: rgba(var(--v-theme-on-surface), 0.02);
    border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
    border-radius: 12px;
    padding: 16px;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    font-size: 0.88rem;
    color: rgba(var(--v-theme-on-surface), 0.85);
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.detail-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.detail-row {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px;
    border: 1px solid rgba(var(--v-theme-on-surface), 0.06);
    border-radius: 10px;
    background: rgb(var(--v-theme-surface));
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.detail-row:hover {
    border-color: rgba(var(--v-theme-primary), 0.35);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.detail-row__index {
    padding-top: 6px;
    flex-shrink: 0;
}

.summary-sheet {
    background: rgba(var(--v-theme-primary), 0.04);
    max-width: 460px;
    margin-left: auto;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 4px 0;
    font-size: 0.88rem;
}

.summary-row--total {
    font-size: 1rem;
    font-weight: 700;
}

.summary-label {
    color: rgba(var(--v-theme-on-surface), 0.7);
}

.summary-value {
    font-weight: 600;
    color: rgba(var(--v-theme-on-surface), 0.95);
}

@media (max-width: 600px) {
    .detail-row {
        flex-direction: column;
        align-items: stretch;
    }
    .detail-row__index {
        padding-top: 0;
    }
    .summary-sheet {
        max-width: 100%;
    }
}
</style>
