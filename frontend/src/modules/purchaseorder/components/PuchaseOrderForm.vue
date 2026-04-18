<script lang="ts" setup>
import { computed, onMounted, ref } from 'vue'
import { useField, useFieldArray, useForm } from 'vee-validate'
// Import Composables
import { useTranslate } from '@/composables/useTranslate'
// Import Validations
import { purchaseOrderSchema } from '@/validations'
// import Stores
import { useMessageStore } from '@/stores/message'
import { useItemLookupStore } from '@/stores/lookup/item.store'
import { useUnitLookupStore } from '@/stores/lookup/unit.store'
import { useItemRequestLookupStore } from '@/stores/lookup/item_request.store'
import { useStockLookupStore } from '@/stores/lookup/stock.store'
import { usePurchaseOrderLookupStore } from '@/stores/lookup/purchase_order.store'
// Import Components
import BaseAutocomplete from '@/components/base/BaseAutocomplete.vue'
import BaseDateInput from '@/components/base/BaseDateInput.vue'
import BaseCurrencyInput from '@/components/base/BaseCurrencyInput.vue'
import BaseSupplierPicker from '@/components/base/BaseSupplierPicker.vue'
import TaxAmountField from './TaxAmountField.vue'
import DppField from './DppField.vue'
import { computeDiscountAmount, subtotalOf, taxUsesDpp, type DppFormulaMeta, type TaxMeta } from '../composables/usePoTaxAmount'
import { numberOnly } from '@/helpers/numberOnly'
// Icons
import MdiCartOutline from '~icons/mdi/cart-outline'
import MdiClipboardTextOutline from '~icons/mdi/clipboard-text-outline'
import MdiInformationOutline from '~icons/mdi/information-outline'
import MdiPackageVariantClosed from '~icons/mdi/package-variant-closed'
import MdiClose from '~icons/mdi/close'
import MdiContentSaveOutline from '~icons/mdi/content-save-outline'
import MdiFileDocumentOutline from '~icons/mdi/file-document-outline'
import type { PurchaseHistoryEntry, SupplierOption } from '@/components/base/BaseSupplierPicker.vue'
// Import Icons
import { useSupplierLookupStore } from '@/stores/lookup/supplier.store'
import { useSettingDppFormulaLookupStore } from '@/stores/lookup/setting_dpp_formula.store'
import dayjs from 'dayjs'

const props = defineProps<{
    modelValue: boolean,
    title: string,
    data: PurchaseOrderList
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void,
    (e: 'submit', values: PurchaseOrderForm): void,
    (e: 'close'): void,
    (e: 'ready'): void
}>()

const t = useTranslate()
// Store Definition
const message = useMessageStore()
const itemsStore = useItemLookupStore()
const itemUnitsStore = useUnitLookupStore()
const supplierStore = useSupplierLookupStore()
const stockStore = useStockLookupStore()
const itemRequestStore = useItemRequestLookupStore()
const purchaseOrderStore = usePurchaseOrderLookupStore()
const dppFormulaStore = useSettingDppFormulaLookupStore()

// Initialization Ref Variable
const loadingForm = ref<boolean>(false)
const isReady = ref<boolean>(false)
const itemRequestItem = ref<SelectItem[]>([])
const unitKeys = ref<number[]>([])
const itemsItem = ref<SelectItem[]>([])
const unitItem = ref<{[index: number]: SelectItem[]}>({})
const supplierItem = ref<SelectItem[]>([])

const { handleSubmit, setValues, resetForm, errors } = useForm<PurchaseOrderForm>({
    validationSchema: purchaseOrderSchema(t)
})

const { fields: itemRows } = useFieldArray('details')

setValues({
    item_request_uid: props.data.item_request?.uid || null,
    po_date: props.data.po_date || dayjs().format('YYYY-MM-DD'),
    project_name: props.data.project_name || '',
    total_amount: props.data.total_amount || 0,
    status: props.data.status || '',
    details: props.data && props.data.details && props.data.details.length > 0
        ? props.data.details.map(d => ({
            item_uid: d.item?.uid || '',
            unit_uid: d.unit?.uid || '',
            supplier_uid: d.supplier?.uid || '',
            tax_type_uid: null,
            tax_amount: null,
            dpp_formula_uid: null,
            dpp_amount: null,
            discount: null,
            discount_amount: null,
            qty: d.qty,
            price: d.price,
            total: d.total
        }))
    : [{
        item_uid: null as any,
        unit_uid: null as any,
        supplier_uid: null as any,
        tax_type_uid: null,
        tax_amount: null,
        dpp_formula_uid: null,
        dpp_amount: null,
        discount: null,
        discount_amount: null,
        qty: null as any,
        price: null as any,
        total: 0
    }]
})

const { value: item_request_uid } = useField('item_request_uid')
const { value: po_date } = useField<string | Date | null | undefined>('po_date')

onMounted(async () => {
    loadingForm.value = false
    try {
        await itemsStore.fetch()
        await itemUnitsStore.fetch()
        await supplierStore.fetch()
        await stockStore.fetch()
        await purchaseOrderStore.fetch()
        await dppFormulaStore.fetch()

        await loadItemRequestData()
        await loadItemData()
        await loadSupplierData()

        // Muat opsi satuan hanya untuk baris yang sudah punya item (mode edit).
        // Pada form baru, baris masih kosong sehingga satuan juga dibiarkan kosong.
        if (itemRows.value && itemRows.value.length > 0) {
            itemRows.value.forEach((row, idx) => {
                const itemUid = (row.value as any)?.item_uid
                if (!itemUid) return
                loadUnitData(idx).catch(err => console.error("Error loading units:", err))
            })
        }
    } catch (err) {
        message.setMessage({
            text: t('loadingFormError'),
            timeout: 3000,
            color: 'error'
        })
    } finally {
        loadingForm.value = false
        isReady.value = true
        emit('ready')
    }
})

const loadItemRequestData = async () => {
    itemRequestItem.value = []

    try {
        await itemRequestStore.fetch()

        // purchaseOrderStore sudah di-fetch di onMounted — tidak perlu dynamic import ulang.
        const usedItemRequestUids = new Set<string>()
        purchaseOrderStore.data.forEach(po => {
            if (po.item_request?.uid && po.item_request.uid !== props.data?.item_request?.uid) {
                usedItemRequestUids.add(po.item_request.uid)
            }
        })

        itemRequestItem.value = itemRequestStore.data.filter(ir => {
            if (!ir.uid) return false;
            if (usedItemRequestUids.has(ir.uid)) return false;
            if (ir.status !== 'Approved') return false;

            if (ir.requirement === 'Replenishment') return true;

            if (ir.requirement === 'Direct Use') {
                // Tampilkan jika ada detail yang qty diminta > stock tersedia
                return ir.details && Array.isArray(ir.details) && ir.details.some(detail => {
                    const stock = stockStore.data.find(s => s.item && s.item.uid === detail.item?.uid)
                    let stockQty = 0
                    if (stock && Array.isArray(stock.stock_units) && stock.stock_units.length > 0) {
                        const su = stock.stock_units.find((u: any) => u.unit_uid === detail.unit?.uid)
                        stockQty = su ? su.qty : 0
                    }
                    return detail.qty > stockQty
                })
            }

            return false;
        }).map(ir => ({
            title: String(ir.request_number ?? ir.uid ?? ''),
            subtitle: String(ir.project_name ?? ''),
            value: ir.uid as string,
        })) as any[]
    } catch (err) {
        message.setMessage({
            text: t('loadingFormError') + ' (Item Requests)',
            timeout: 3000,
            color: 'error'
        })
    }
}

const loadItemData = async () => {
    itemsItem.value = []

    try {
        await itemsStore.fetch()

        itemsItem.value = itemsStore.data.map(item => ({
            title: item.name,
            value: item.uid
        }))
    } catch (err) {
        console.error("Error :", err)
    }
}

const loadUnitData = async (idx: number, stock_units?: any[]) => {
    try {
        let units: { title: string, value: string }[] = []
        if (stock_units && stock_units.length > 0) {
            units = stock_units.map(u => ({
                title: (u.unit_name && u.unit_symbol) ? `${u.unit_name} (${u.unit_symbol})` : String(u.unit_name ?? u.unit_uid ?? ''),
                value: u.unit_uid
            }))
        } else {
            await itemUnitsStore.fetch()
            units = itemUnitsStore.data.map(item => ({
                title: `${item.name} (${item.symbol})`,
                value: item.uid
            }))
        }
        // Fallback: if still missing name, use uid
        units = units.map(u => ({
            title: u.title && u.title !== u.value ? u.title : u.value,
            value: u.value
        }))
        unitItem.value[idx] = units
        if (unitItem.value[idx] && unitItem.value[idx].length > 0 && !(itemRows.value[idx].value as any).unit_uid) {
            (itemRows.value[idx].value as any).unit_uid = unitItem.value[idx][0].value
        }
    } catch (err) {
        console.log("Error :", err)
    }
}

// =========================
// Konteks item request terpilih
// =========================
/** Detail item request yang sedang dipilih — untuk panel referensi user. */
const selectedRequest = computed<any | null>(() => {
    const uid = item_request_uid.value
    if (!uid) return null
    const cur = itemRequestStore.current_data as any
    if (Array.isArray(cur)) return cur.find(r => r?.uid === uid) ?? null
    if (cur?.uid === uid) return cur
    return (itemRequestStore.data ?? []).find((r: any) => r?.uid === uid) ?? null
})

/** Hitung stok tersedia untuk pasangan item+unit. */
const getStockFor = (itemUid?: string, unitUid?: string): number => {
    if (!itemUid) return 0
    const stock = stockStore.data.find(s => s.item?.uid === itemUid)
    if (!stock || !Array.isArray(stock.stock_units)) return 0
    const su = stock.stock_units.find((u: any) => u.unit_uid === unitUid)
    return su?.qty ?? 0
}

// Formatter angka konsisten lokal.
const formatQty = (n: number | null | undefined) =>
    Number(n ?? 0).toLocaleString('id-ID')

// =========================
// Data pendukung BaseSupplierPicker
// =========================
const supplierOptions = computed<SupplierOption[]>(() =>
    (supplierStore.data ?? []).map((s: any) => ({ uid: s.uid, name: s.name }))
)

// ─── Tax types per supplier ─────────────────────────────────────────────────
/** Map supplier_uid → tax_types yang sudah dikurasi (hanya field yang relevan). */
const supplierTaxTypesMap = computed<Record<string, TaxMeta[]>>(() => {
    const map: Record<string, TaxMeta[]> = {}
    for (const s of (supplierStore.data ?? []) as any[]) {
        if (!s?.uid) continue
        const list: TaxMeta[] = Array.isArray(s.tax_types)
            ? s.tax_types
                .filter((t: any) => t?.uid && t?.name)
                .map((t: any) => ({
                    uid:          String(t.uid),
                    name:         String(t.name),
                    formula_type: (t.formula_type ?? 'percentage') as TaxMeta['formula_type'],
                    formula:      t.formula ?? null,
                    uses_dpp:     !!t.uses_dpp,
                }))
            : []
        map[s.uid] = list
    }
    return map
})

/** SelectItem options untuk dropdown tax type pada row tertentu (berdasar supplier). */
const getTaxTypeOptions = (supplierUid: string | null | undefined): SelectItem[] => {
    if (!supplierUid) return []
    return (supplierTaxTypesMap.value[supplierUid] ?? []).map(t => ({ title: t.name, value: t.uid }))
}

/** Ambil meta lengkap tax type yang sedang dipilih di row. */
const getTaxMeta = (supplierUid: string | null | undefined, taxUid: string | null | undefined): TaxMeta | null => {
    if (!supplierUid || !taxUid) return null
    const list = supplierTaxTypesMap.value[supplierUid] ?? []
    return list.find(t => t.uid === taxUid) ?? null
}

// ─── DPP formula options (dari master Setting DPP Formula aktif) ───────────
/** List formula DPP siap-pakai untuk komponen DppField. */
const dppFormulas = computed<DppFormulaMeta[]>(() =>
    (dppFormulaStore.data ?? [])
        .filter((f: any) => f?.uid && f?.formula)
        .map((f: any) => ({
            uid:     String(f.uid),
            name:    String(f.name ?? ''),
            formula: String(f.formula ?? ''),
        })),
)


/**
 * Ekstraksi history pembelian per item dari seluruh PO:
 * tiap detail PO → 1 entri { supplier_uid, item_uid, price, date }.
 */
const purchaseHistory = computed<PurchaseHistoryEntry[]>(() => {
    const out: PurchaseHistoryEntry[] = []
    const pos = (purchaseOrderStore.data ?? []) as any[]
    for (const po of pos) {
        const date = po.po_date || po.created_at
        const details = Array.isArray(po.details) ? po.details : []
        for (const d of details) {
            if (!d?.supplier?.uid || !d?.item?.uid) continue
            out.push({
                supplier_uid: d.supplier.uid,
                supplier_name: d.supplier.name,
                item_uid: d.item.uid,
                price: Number(d.price) || 0,
                date,
            })
        }
    }
    return out
})

const loadSupplierData = async () => {
    supplierItem.value = []

    try {
        await supplierStore.fetch()

        supplierItem.value = supplierStore.data.map(supplier => ({
            title: supplier.name,
            value: supplier.uid
        }))
    } catch (err) {
        console.error("Error :", err)
    }
}

if (props.data.details && Array.isArray(props.data.details) && props.data.details.length > 0) {
    unitKeys.value = props.data.details.map(() => 0)
} else {
    unitKeys.value = [0]
}

const handleSelectItemRequest = async (value: string) => {
    loadItemData()
    loadSupplierData()

    if (value) {
        try {
            await itemRequestStore.fetchByUid(value)
        } catch (err: any) {
            message.setMessage({
                text: t('loadingFormError') + ' (Item Request Details)',
                timeout: 3000,
                color: 'error'
            })
            return
        }

        const reqData = itemRequestStore.current_data
        const req = Array.isArray(reqData) ? reqData.find((r: any) => r.uid === value) : reqData

        if (req) {
            const allDetails = Array.isArray(req.details) ? req.details : []

            // Helper: hitung stok yang tersedia untuk pasangan item+unit tertentu.
            const getStockQty = (d: any): number => {
                const stock = stockStore.data.find(s => s.item && s.item.uid === d.item?.uid)
                if (!stock || !Array.isArray(stock.stock_units)) return 0
                const su = stock.stock_units.find((u: any) => u.unit_uid === d.unit?.uid)
                return su?.qty ?? 0
            }

            // Direct Use: hanya tampilkan item yang masih kurang (qty > stock).
            const reqDetails = (req as any).requirement === 'Direct Use'
                ? allDetails.filter(d => d.qty > getStockQty(d))
                : allDetails

            setValues({
                project_name: req.project_name || '',
                details: reqDetails.map(d => {
                    const initialQty = d.qty ?? 0
                    return {
                        item_uid: d.item?.uid || '',
                        unit_uid: d.unit?.uid || '',
                        supplier_uid: '',
                        qty: initialQty,
                        price: 0,
                        total: 0,
                    }
                })
            })
            unitKeys.value = reqDetails.map(() => 0)

            // Set item options from item request details
            reqDetails.forEach((d, idx) => {
                if (d.item?.uid) {
                    const existing = itemsItem.value.find(i => i.value === d.item?.uid)
                    if (!existing) {
                        itemsItem.value.push({
                            title: String(d.item.name ?? d.item.uid ?? ''),
                            value: d.item.uid
                        })
                    }
                }
                if (d.unit?.uid) {
                    unitItem.value[idx] = [{
                        title: `${d.unit.name || ''} (${d.unit.symbol || ''})`,
                        value: d.unit.uid
                    }]
                }
            })
        }
    }

    item_request_uid.value = value
}

const handleSelectItem = async (value: string, idx: number) => {
    (itemRows.value[idx].value as { unit_uid: string | undefined }).unit_uid = undefined

    if (value) {
        (itemRows.value[idx].value as any).item_uid = value

        unitKeys.value[idx] = (unitKeys.value[idx] || 0) + 1
        const stock = stockStore.data.find(s => s.item && s.item.uid === value)

        if (stock && stock.item?.uid) {
            (itemRows.value[idx].value as any).stock_uid = stock.item.uid
        }

        if (stock) {
            if (Array.isArray(stock.stock_units) && stock.stock_units.length > 0) {
                await loadUnitData(idx, stock.stock_units)
            } else if (stock.unit) {
                await loadUnitData(idx, [{
                    unit_uid: stock.unit.uid,
                    unit_name: stock.unit.name,
                    unit_symbol: stock.unit.symbol
                }])
            } else {
                await loadUnitData(idx)
            }
        }
    }
}

const handleSelectUnit = async (value: string, idx: number) => {
    (itemRows.value[idx].value as { unit_uid: string | undefined }).unit_uid = value
}

const handleSelectSupplier = async (value: string, idx: number) => {
    const row = itemRows.value[idx].value as {
        supplier_uid: string | undefined
        tax_type_uid: string | null | undefined
        tax_amount: number | null | undefined
    }
    row.supplier_uid = value
    // Reset tax_type + tax_amount — daftar tax_type milik supplier baru bisa berbeda,
    // dan nilai manual dari supplier lama tidak boleh ikut terbawa.
    row.tax_type_uid = null
    row.tax_amount = null
}

/**
 * Reset nilai `tax_amount` saat tax type berubah. Untuk tipe auto (percentage
 * / formula) nilainya dihitung TaxAmountField — reset ini memastikan nilai
 * manual dari tax type sebelumnya tidak tertinggal di state.
 */
const handleChangeTaxType = (value: string | null, idx: number) => {
    const row = itemRows.value[idx].value as {
        tax_type_uid: string | null | undefined
        tax_amount: number | null | undefined
        dpp_formula_uid: string | null | undefined
        dpp_amount: number | null | undefined
    }
    row.tax_type_uid = value
    row.tax_amount = null

    // Reset pilihan DPP saat tax type berubah — konteks `uses_dpp` bisa berubah
    // dan formula lama belum tentu relevan untuk tax type baru.
    row.dpp_formula_uid = null
    row.dpp_amount = null
}

/** Set DPP amount baris sesuai perhitungan aktif dari DppField. */
const handleDppAmountUpdate = (value: number, idx: number) => {
    const row = itemRows.value[idx].value as { dpp_amount: number | null | undefined }
    row.dpp_amount = Number.isFinite(value) ? value : 0
}

/**
 * Set nilai diskon row — dikonversi ke integer persen (0-100) dan
 * langsung hitung ulang `discount_amount = subtotal * discount/100`.
 * String kosong / non-numeric → null supaya placeholder tetap tampil.
 */
const handleDiscountInput = (value: unknown, idx: number) => {
    const row = itemRows.value[idx].value as {
        qty: number
        price: number
        discount: number | null | undefined
        discount_amount: number | null | undefined
    }

    const raw = value === null || value === undefined || value === '' ? null : Number(value)
    const normalized = raw !== null && Number.isFinite(raw)
        ? Math.min(Math.max(Math.trunc(raw), 0), 100)
        : null

    row.discount = normalized
    row.discount_amount = normalized === null
        ? null
        : computeDiscountAmount(row.qty, row.price, normalized)
}

// Error input baru muncul setelah user klik "Simpan" minimal sekali.
const submitAttempted = ref(false)
const submit = () => {
    submitAttempted.value = true
    handleSubmit(async (values) => {
        emit('submit', values)
    }, (errors) => {
        console.log("Validation errors:", errors)
    })()
}

const handleClose = () => {
    submitAttempted.value = false
    resetForm()
    isReady.value = false
    emit('close')
}
</script>

<template>
    <v-dialog
        v-if="isReady"
        :persistent="props.modelValue && loadingForm"
        max-width="1300"
        scrollable
        :model-value="props.modelValue"
        @update:model-value="async value => { emit('update:modelValue', value); if (!value) handleClose() }"
    >
        <v-card class="rounded-lg d-flex flex-column" :loading="loadingForm" style="max-height: 92vh;">
            <!-- ════ Header ════ -->
            <v-card-title class="d-flex align-center ga-2 px-4 px-sm-6 py-3 py-sm-4">
                <v-icon :icon="MdiCartOutline" color="primary" />
                <span class="text-subtitle-1 text-sm-h6 font-weight-semibold text-truncate">
                    {{ props.title }}
                </span>
                <v-spacer />
                <v-btn
                    :icon="MdiClose"
                    variant="text"
                    size="small"
                    :disabled="loadingForm"
                    @click="handleClose"
                />
            </v-card-title>

            <v-divider />

            <v-form @submit.prevent="submit" class="d-flex flex-column grow" style="min-height: 0;">
                <v-card-text class="px-4 px-sm-6 pt-6 pt-sm-8 pb-4 grow overflow-y-auto">
                    <!-- ════ Section: Informasi Umum PO ════ -->
                    <div class="section-header mb-3">
                        <v-icon :icon="MdiInformationOutline" size="18" color="primary" />
                        <span class="text-subtitle-2 font-weight-bold text-primary">{{ t('generalInfo') }}</span>
                    </div>

                    <v-row dense>
                        <v-col cols="12" md="6">
                            <base-autocomplete
                                :items="itemRequestItem"
                                :label="t('itemRequest')"
                                v-model="item_request_uid"
                                :error-messages="submitAttempted ? errors.item_request_uid : undefined"
                                @update:model-value="handleSelectItemRequest"
                                :clearable="true"
                            >
                                <template v-slot:item="{ props: itemProps, item }">
                                    <v-list-item v-bind="itemProps" :title="item.raw.title" :subtitle="item.raw.subtitle" />
                                </template>
                            </base-autocomplete>
                        </v-col>
                        <v-col cols="12" md="6">
                            <base-date-input v-model="po_date" readonly />
                        </v-col>
                    </v-row>

                    <!-- ════ Context Panel: Ringkasan Item Request ════ -->
                    <v-expand-transition>
                        <div v-if="selectedRequest" class="mt-4 context-wrapper">
                            <!-- Banner -->
                            <div class="context-banner">
                                <div class="context-banner-icon">
                                    <v-icon :icon="MdiFileDocumentOutline" size="20" />
                                </div>
                                <div class="grow">
                                    <div class="context-banner-label">{{ t('itemRequest') }}</div>
                                    <div class="context-banner-title">{{ selectedRequest.request_number || '-' }}</div>
                                </div>
                                <v-chip
                                    v-if="selectedRequest.requirement"
                                    size="small"
                                    variant="flat"
                                    color="white"
                                    class="font-weight-bold text-primary"
                                >
                                    {{ selectedRequest.requirement }}
                                </v-chip>
                            </div>

                            <!-- Info grid -->
                            <div class="context-body">
                                <div class="context-grid">
                                    <div class="context-item">
                                        <div class="context-label">{{ t('requestDate') }}</div>
                                        <div class="context-value">{{ selectedRequest.request_date || '-' }}</div>
                                    </div>
                                    <div class="context-item">
                                        <div class="context-label">{{ t('warehouse') }}</div>
                                        <div class="context-value">{{ selectedRequest.warehouse?.name || '-' }}</div>
                                    </div>
                                    <div class="context-item">
                                        <div class="context-label">{{ t('departmentName') }}</div>
                                        <div class="context-value">{{ selectedRequest.department_name || '-' }}</div>
                                    </div>
                                    <div class="context-item">
                                        <div class="context-label">{{ t('projectName') }}</div>
                                        <div class="context-value">{{ selectedRequest.project_name || '-' }}</div>
                                    </div>
                                    <div class="context-item">
                                        <div class="context-label">{{ t('unitCode') }}</div>
                                        <div class="context-value">{{ selectedRequest.unit_code || '-' }}</div>
                                    </div>
                                    <div class="context-item">
                                        <div class="context-label">{{ t('woNumber') }}</div>
                                        <div class="context-value">{{ selectedRequest.wo_number || '-' }}</div>
                                    </div>
                                </div>

                                <!-- Details table -->
                                <div class="context-section-head">
                                    <span class="context-section-title">{{ t('details') }}</span>
                                    <v-chip size="x-small" color="primary" variant="tonal" class="font-weight-bold">
                                        {{ (selectedRequest.details || []).length }}
                                    </v-chip>
                                </div>
                                <div class="context-table-wrap">
                                    <v-table density="compact" class="context-table">
                                        <thead>
                                            <tr>
                                                <th class="text-center" style="width: 40px;">No.</th>
                                                <th>{{ t('item') }}</th>
                                                <th class="text-center" style="width: 110px;">{{ t('qty') }}</th>
                                                <th class="text-center" style="width: 100px;">{{ t('unit') }}</th>
                                                <th class="text-end" style="width: 110px;">{{ t('stock') || 'Stok' }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(d, i) in ((selectedRequest.details || []) as any[])" :key="i">
                                                <td class="text-center text-caption text-medium-emphasis">{{ Number(i) + 1 }}</td>
                                                <td>
                                                    <div class="font-weight-semibold">{{ d.item?.name || '-' }}</div>
                                                    <div v-if="d.item?.code" class="text-caption text-medium-emphasis font-mono">{{ d.item.code }}</div>
                                                </td>
                                                <td class="text-center font-weight-bold text-primary">{{ formatQty(d.qty) }}</td>
                                                <td class="text-center text-caption">{{ d.unit?.symbol || '-' }}</td>
                                                <td class="text-end text-caption">{{ formatQty(getStockFor(d.item?.uid, d.unit?.uid)) }}</td>
                                            </tr>
                                            <tr v-if="!(selectedRequest.details || []).length">
                                                <td colspan="5" class="text-center text-caption text-medium-emphasis py-3">
                                                    {{ t('noData') }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </v-table>
                                </div>
                            </div>
                        </div>
                    </v-expand-transition>

                    <v-divider class="my-5" />

                    <!-- ════ Section: Detail Pembelian ════ -->
                    <div class="section-header mb-3">
                        <v-icon :icon="MdiPackageVariantClosed" size="18" color="primary" />
                        <span class="text-subtitle-2 font-weight-bold text-primary">{{ t('details') }}</span>
                        <v-chip size="x-small" color="primary" variant="tonal" class="ms-2">
                            {{ itemRows.length }}
                        </v-chip>
                    </div>

                    <v-alert
                        v-if="!item_request_uid"
                        type="info"
                        variant="tonal"
                        density="compact"
                        class="mb-3"
                        :icon="MdiClipboardTextOutline"
                        :text="t('selectRequirementFirst') || 'Pilih permintaan barang terlebih dahulu'"
                    />

                    <div class="detail-card-list">
                        <div
                            v-for="(row, idx) in itemRows"
                            :key="row.key"
                            class="detail-card"
                        >
                            <header class="detail-card__head">
                                <v-chip
                                    size="small"
                                    color="primary"
                                    variant="tonal"
                                    class="font-weight-bold detail-card__no"
                                >
                                    #{{ idx + 1 }}
                                </v-chip>
                                <span class="detail-card__title text-caption text-medium-emphasis">
                                    {{ t('item') }} — {{ t('details') }}
                                </span>
                            </header>

                            <v-row dense>
                                <!-- Row 1: Item / Unit / Qty -->
                                <v-col cols="12" md="6">
                                    <base-autocomplete
                                        :items="itemsItem"
                                        :label="t('item')"
                                        v-model="(row.value as { item_uid: string }).item_uid"
                                        item-title="title"
                                        item-value="value"
                                        :error-messages="submitAttempted ? errors[`details[${idx}].item_uid` as keyof typeof errors] : undefined"
                                        :disabled="!item_request_uid"
                                        readonly
                                        @update:model-value="val => handleSelectItem(val, idx)"
                                    />
                                </v-col>
                                <v-col cols="6" md="3">
                                    <base-autocomplete
                                        :key="unitKeys[idx]"
                                        :items="unitItem[idx] || []"
                                        :label="t('unit')"
                                        v-model="(row.value as { unit_uid: string }).unit_uid"
                                        item-title="title"
                                        item-value="value"
                                        :error-messages="submitAttempted ? errors[`details[${idx}].unit_uid` as keyof typeof errors] : undefined"
                                        :disabled="!item_request_uid"
                                        readonly
                                        @update:model-value="val => handleSelectUnit(val, idx)"
                                    />
                                </v-col>
                                <v-col cols="6" md="3">
                                    <v-text-field
                                        v-model="(row.value as { qty: number }).qty"
                                        :label="t('qty')"
                                        type="number"
                                        variant="outlined"
                                        density="comfortable"
                                        min="1"
                                        hide-details="auto"
                                        :error-messages="submitAttempted ? errors[`details[${idx}].qty` as keyof typeof errors] : undefined"
                                        :disabled="!item_request_uid"
                                    />
                                </v-col>

                                <!-- Row 2: Supplier / Tax Type -->
                                <v-col cols="12" md="6">
                                    <base-supplier-picker
                                        :label="t('supplier')"
                                        v-model="(row.value as { supplier_uid: string }).supplier_uid"
                                        :item-uid="(row.value as { item_uid: string }).item_uid"
                                        :suppliers="supplierOptions"
                                        :history="purchaseHistory"
                                        :error-messages="submitAttempted ? errors[`details[${idx}].supplier_uid` as keyof typeof errors] : undefined"
                                        :disabled="!item_request_uid || !(row.value as { item_uid: string }).item_uid"
                                        @update:model-value="val => handleSelectSupplier(val as string, idx)"
                                        @select="(opt) => opt?.lastPrice && ((row.value as { price: number }).price = opt.lastPrice)"
                                    />
                                </v-col>
                                <v-col cols="12" md="6">
                                    <base-autocomplete
                                        :items="getTaxTypeOptions((row.value as { supplier_uid: string }).supplier_uid)"
                                        :label="t('taxType')"
                                        v-model="(row.value as { tax_type_uid: string | null }).tax_type_uid"
                                        item-title="title"
                                        item-value="value"
                                        :disabled="!(row.value as { supplier_uid: string }).supplier_uid"
                                        :clearable="true"
                                        @update:model-value="val => handleChangeTaxType(val as string | null, idx)"
                                    />
                                </v-col>

                                <!-- Row 3: Price / Subtotal / Tax Amount -->
                                <v-col cols="12" sm="6" md="4">
                                    <base-currency-input
                                        v-model="(row.value as { price: number }).price"
                                        :label="t('price')"
                                        :disabled="!item_request_uid
                                            || !(row.value as { item_uid: string }).item_uid
                                            || !(row.value as { supplier_uid: string }).supplier_uid
                                            || !(row.value as { tax_type_uid: string | null }).tax_type_uid"
                                        :error-messages="submitAttempted ? errors[`details[${idx}].price` as keyof typeof errors] : undefined"
                                    />
                                </v-col>
                                <v-col cols="12" sm="6" md="4">
                                    <base-currency-input
                                        :label="t('subtotal')"
                                        :model-value="subtotalOf(
                                            (row.value as { qty: number }).qty,
                                            (row.value as { price: number }).price,
                                        )"
                                        readonly
                                    />
                                </v-col>

                                <!-- Diskon (%) — integer 0-100. -->
                                <v-col cols="12" sm="6" md="4">
                                    <v-text-field
                                        :label="t('discount')"
                                        :model-value="(row.value as { discount: number | null }).discount"
                                        variant="outlined"
                                        density="comfortable"
                                        hide-details="auto"
                                        type="number"
                                        min="0"
                                        max="100"
                                        suffix="%"
                                        autocomplete="off"
                                        :disabled="!item_request_uid
                                            || !(row.value as { item_uid: string }).item_uid
                                            || !(row.value as { supplier_uid: string }).supplier_uid
                                            || !(row.value as { tax_type_uid: string | null }).tax_type_uid"
                                        @keypress="numberOnly"
                                        @update:model-value="val => handleDiscountInput(val, idx)"
                                    />
                                </v-col>

                                <!-- Nilai Diskon (readonly) — subtotal × discount%. -->
                                <v-col cols="12" sm="6" md="4">
                                    <base-currency-input
                                        :label="t('discountAmount')"
                                        :model-value="computeDiscountAmount(
                                            (row.value as { qty: number }).qty,
                                            (row.value as { price: number }).price,
                                            (row.value as { discount: number | null }).discount,
                                        )"
                                        readonly
                                    />
                                </v-col>

                                <!-- DPP amount: tampil setelah subtotal saat tax type aktif memiliki uses_dpp = true. -->
                                <v-col
                                    v-if="taxUsesDpp(getTaxMeta(
                                        (row.value as { supplier_uid: string }).supplier_uid,
                                        (row.value as { tax_type_uid: string | null }).tax_type_uid,
                                    ))"
                                    cols="12"
                                    sm="6"
                                    md="4"
                                >
                                    <dpp-field
                                        :formulas="dppFormulas"
                                        :qty="(row.value as { qty: number }).qty"
                                        :price="(row.value as { price: number }).price"
                                        :model-value="(row.value as { dpp_formula_uid: string | null }).dpp_formula_uid"
                                        :disabled="!item_request_uid || !(row.value as { item_uid: string }).item_uid"
                                        @update:model-value="val => ((row.value as { dpp_formula_uid: string | null }).dpp_formula_uid = val)"
                                        @update:amount="val => handleDppAmountUpdate(val, idx)"
                                    />
                                </v-col>

                                <v-col cols="12" sm="12" md="4">
                                    <tax-amount-field
                                        :meta="getTaxMeta(
                                            (row.value as { supplier_uid: string }).supplier_uid,
                                            (row.value as { tax_type_uid: string | null }).tax_type_uid,
                                        )"
                                        :qty="(row.value as { qty: number }).qty"
                                        :price="(row.value as { price: number }).price"
                                        :model-value="(row.value as { tax_amount: number | null }).tax_amount"
                                        :disabled="!item_request_uid || !(row.value as { item_uid: string }).item_uid"
                                        @update:model-value="val => ((row.value as { tax_amount: number | null }).tax_amount = val)"
                                    />
                                </v-col>
                            </v-row>
                        </div>
                    </div>
                </v-card-text>

                <v-divider />

                <v-card-actions class="pa-4 justify-end ga-2">
                    <v-btn variant="tonal" :disabled="loadingForm" @click="handleClose">
                        {{ t('cancel') }}
                    </v-btn>
                    <v-btn
                        type="submit"
                        color="primary"
                        variant="elevated"
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

.po-detail-table :deep(td) {
    vertical-align: middle;
}
.po-detail-table :deep(.v-input__details) {
    min-height: 18px;
    padding-top: 2px;
}

/* ════ Detail card (per-row, pengganti tabel yang sebelumnya mepet) ════ */
.detail-card-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.detail-card {
    position: relative;
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    border-radius: 12px;
    background: rgb(var(--v-theme-surface));
    padding: 14px;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.detail-card:hover {
    border-color: rgba(var(--v-theme-primary), 0.4);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
}
.detail-card__head {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}
.detail-card__no {
    letter-spacing: 0.02em;
}
.detail-card__title {
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 600;
}

@media (max-width: 600px) {
    .detail-card { padding: 12px; }
}

/* ════ Context panel (ringkasan item request) ════ */
.context-wrapper {
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    background: rgb(var(--v-theme-surface));
}

.context-banner {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: linear-gradient(135deg, rgb(var(--v-theme-primary)) 0%, rgba(var(--v-theme-primary), 0.82) 100%);
    color: #fff;
}
.context-banner-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: #fff;
}
.context-banner-label {
    font-size: 0.68rem;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    opacity: 0.85;
    font-weight: 500;
}
.context-banner-title {
    font-size: 1rem;
    font-weight: 700;
    line-height: 1.2;
}

.context-body {
    padding: 14px 16px 16px;
}

.context-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 10px 14px;
    margin-bottom: 12px;
}
.context-item {
    padding: 8px 10px;
    background: rgba(var(--v-theme-primary), 0.04);
    border-left: 3px solid rgba(var(--v-theme-primary), 0.6);
    border-radius: 6px;
}
.context-label {
    font-size: 0.68rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: rgba(var(--v-theme-on-surface), 0.55);
    font-weight: 500;
}
.context-value {
    font-size: 0.85rem;
    font-weight: 600;
    color: rgba(var(--v-theme-on-surface), 0.95);
    margin-top: 2px;
    word-break: break-word;
}

.context-section-head {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 14px 0 8px;
}
.context-section-title {
    font-size: 0.8rem;
    font-weight: 700;
    color: rgb(var(--v-theme-primary));
    letter-spacing: 0.02em;
}

.context-table-wrap {
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    border-radius: 8px;
    overflow: hidden;
}
.context-table :deep(thead th) {
    background: rgba(var(--v-theme-primary), 0.06);
    color: rgba(var(--v-theme-on-surface), 0.7);
    font-size: 0.7rem !important;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    font-weight: 600;
}
.context-table :deep(tbody tr:nth-child(even)) {
    background: rgba(var(--v-theme-on-surface), 0.015);
}

.font-mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
}
</style>