<script lang="ts" setup>
import { computed, ref, watch } from 'vue'
import { useField, useFieldArray, useForm } from 'vee-validate'
import { useTranslate } from '@/composables/useTranslate'
import { itemTransferSchema } from '@/validations/itemTransferSchema'
// API
import { api } from '@/api/api'
// Base components
import BaseAsyncSelect from '@/components/base/BaseAsyncSelect.vue'
import type { AsyncFetcher } from '@/components/base/BaseAsyncSelect.vue'
import BaseDateInput from '@/components/base/BaseDateInput.vue'
// Icons
import MdiClose from '~icons/mdi/close'
import MdiSwapHorizontalBold from '~icons/mdi/swap-horizontal-bold'
import MdiArrowRight from '~icons/mdi/arrow-right'
import MdiArrowLeft from '~icons/mdi/arrow-left'
import MdiContentSaveOutline from '~icons/mdi/content-save-outline'
import MdiWarehouse from '~icons/mdi/warehouse'
import MdiPackageVariantClosed from '~icons/mdi/package-variant-closed'
import MdiClipboardCheckOutline from '~icons/mdi/clipboard-check-outline'
import MdiPlus from '~icons/mdi/plus'
import MdiTrashCanOutline from '~icons/mdi/trash-can-outline'
import MdiLinkVariant from '~icons/mdi/link-variant'
import MdiSwapVerticalBold from '~icons/mdi/swap-vertical-bold'
import dayjs from 'dayjs'

// ─── Types ───────────────────────────────────────────────────────────────────
type Option = { title: string; value: string }

const props = defineProps<{
    modelValue: boolean
    title: string
    data: ItemTransferList
    saving?: boolean
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (
        e: 'submit',
        values: ItemTransferForm,
        opts?: { setupDisplacement?: boolean; occupants?: DestOccupant[] }
    ): void
    (e: 'close'): void
}>()

const t = useTranslate()

// ─── State ───────────────────────────────────────────────────────────────────
const currentStep = ref<1 | 2 | 3>(1)
const step1Valid = ref(false)
const step2Valid = ref(false)
// Error baru ditampilkan setelah user klik "Lanjut" minimal sekali
const submitAttempted = ref(false)

const STEP1_FIELDS = [
    'transfer_date',
    'from_warehouse_uid', 'from_rack_uid', 'from_tank_uid',
    'to_warehouse_uid', 'to_rack_uid', 'to_tank_uid',
] as const

const isEdit = computed(() => !!props.data.uid)

// ─── Form ────────────────────────────────────────────────────────────────────
const { handleSubmit, setValues, errors, validate, values } = useForm<ItemTransferForm>({
    validationSchema: itemTransferSchema(t),
})

setValues({
    transfer_date: props.data.transfer_date
        ? dayjs(props.data.transfer_date as string).format('YYYY-MM-DD')
        : dayjs().format('YYYY-MM-DD'),
    from_warehouse_uid: props.data.from_warehouse?.uid ?? null,
    from_rack_uid: props.data.from_rack?.uid ?? null,
    from_tank_uid: props.data.from_tank?.uid ?? null,
    to_warehouse_uid: props.data.to_warehouse?.uid ?? null,
    to_rack_uid: props.data.to_rack?.uid ?? null,
    to_tank_uid: props.data.to_tank?.uid ?? null,
    notes: props.data.notes ?? null,
    details: (props.data.details ?? []).map((d) => ({
        item_uid: d.item?.uid ?? null,
        unit_uid: d.unit?.uid ?? null,
        qty: d.qty ?? null,
        description: d.description ?? null,
    })),
})

const { value: transfer_date }      = useField<string>('transfer_date')
const { value: from_warehouse_uid } = useField<string | null>('from_warehouse_uid')
const { value: from_rack_uid }      = useField<string | null>('from_rack_uid')
const { value: from_tank_uid }      = useField<string | null>('from_tank_uid')
const { value: to_warehouse_uid }   = useField<string | null>('to_warehouse_uid')
const { value: to_rack_uid }        = useField<string | null>('to_rack_uid')
const { value: to_tank_uid }        = useField<string | null>('to_tank_uid')
const { value: notes }              = useField<string | null>('notes')

const { fields: detailRows, push: addRow, remove: removeRow } = useFieldArray<{
    item_uid: string | null
    unit_uid: string | null
    qty: number | null
    description: string | null
}>('details')

if (!detailRows.value.length) {
    addRow({ item_uid: null, unit_uid: null, qty: null, description: null })
}

const detailError = (idx: number, field: string): string =>
    (errors.value as Record<string, string>)[`details[${idx}].${field}`] ?? ''

// ─── Cascade reset ───────────────────────────────────────────────────────────
watch(from_warehouse_uid, (val, old) => {
    if (old !== undefined && val !== old) {
        from_rack_uid.value = null
        from_tank_uid.value = null
    }
})
watch(to_warehouse_uid, (val, old) => {
    if (old !== undefined && val !== old) {
        to_rack_uid.value = null
        to_tank_uid.value = null
    }
})
// Rack ↔ Tank mutually exclusive
watch(from_rack_uid, (val) => { if (val) from_tank_uid.value = null })
watch(from_tank_uid, (val) => { if (val) from_rack_uid.value = null })
watch(to_rack_uid, (val) => { if (val) to_tank_uid.value = null })
watch(to_tank_uid, (val) => { if (val) to_rack_uid.value = null })

// Saat lokasi sumber berubah, reset item di detail rows agar tidak pakai
// item yang sudah tidak tersedia di lokasi baru.
watch([from_warehouse_uid, from_rack_uid, from_tank_uid], (_, [oldWh, oldRack, oldTank]) => {
    if (oldWh === undefined && oldRack === undefined && oldTank === undefined) return
    detailRows.value.forEach((row) => {
        (row.value as any).item_uid = null
        ;(row.value as any).unit_uid = null
    })
})

// ─── Fetcher factory (BaseAsyncSelect) ──────────────────────────────────────
const makeFetcher = (
    url: string | (() => string | null),
    mapper: (item: any) => Option = (i) => ({ title: i.name, value: i.uid })
): AsyncFetcher<Option> => {
    return async ({ search, page, perPage, signal }) => {
        const endpoint = typeof url === 'function' ? url() : url
        if (!endpoint) return { items: [], hasMore: false }

        const res = await api.get(endpoint, {
            params: { page, per_page: perPage, search, sort_by: 'name', sort_dir: 'asc' },
            signal,
        })
        const payload = res.data ?? {}
        const meta = payload.meta ?? payload.pagination ?? {}
        return {
            items: (payload.data ?? []).map(mapper),
            hasMore: Number(meta.current_page ?? page) < Number(meta.last_page ?? 1),
        }
    }
}

const fetchWarehouse = makeFetcher('/warehouse')
const fetchFromRack = makeFetcher(() => from_warehouse_uid.value ? `/rack/${from_warehouse_uid.value}` : null)
const fetchFromTank = makeFetcher(() => from_warehouse_uid.value ? `/tank/${from_warehouse_uid.value}` : null)
const fetchToRack = makeFetcher(() => to_warehouse_uid.value ? `/rack/${to_warehouse_uid.value}` : null)
const fetchToTank = makeFetcher(() => to_warehouse_uid.value ? `/tank/${to_warehouse_uid.value}` : null)
/**
 * Fetch item dari stock sumber — hanya menampilkan item yang tersedia di
 * lokasi (warehouse / rack / tank) yang user pilih di step 1.
 * Jika sumber belum dipilih, fallback ke semua item (mode create tanpa lokasi).
 */
const fetchItem: AsyncFetcher<Option> = async ({ search, page, perPage, signal }) => {
    // Fallback: belum ada lokasi sumber → pakai endpoint items biasa
    if (!from_warehouse_uid.value) {
        const res = await api.get('/items', {
            params: { page, per_page: perPage, search, sort_by: 'name', sort_dir: 'asc' },
            signal,
        })
        const payload = res.data ?? {}
        const meta = payload.meta ?? payload.pagination ?? {}
        return {
            items: (payload.data ?? []).map((i: any) => ({ title: i.name, value: i.uid })),
            hasMore: Number(meta.current_page ?? page) < Number(meta.last_page ?? 1),
        }
    }

    // Dengan lokasi sumber → ambil stock di lokasi tersebut
    const params: Record<string, any> = {
        page,
        per_page: perPage,
        search,
        sort_by: 'item_name',
        sort_dir: 'asc',
        warehouse_uid: from_warehouse_uid.value,
        with_stock_only: 1,
    }
    if (from_rack_uid.value) params.rack_uid = from_rack_uid.value
    if (from_tank_uid.value) params.tank_uid = from_tank_uid.value

    const res = await api.get('/stock', { params, signal })
    const payload = res.data ?? {}
    const meta = payload.meta ?? payload.pagination ?? {}

    // Deduplicate by item_uid (satu item bisa muncul di beberapa stock record bila tanpa filter rak/tangki)
    const seen = new Set<string>()
    const items: Option[] = []
    for (const stock of payload.data ?? []) {
        const uid = stock.item?.uid
        if (!uid || seen.has(uid)) continue
        seen.add(uid)
        items.push({ title: stock.item.name, value: uid })
    }

    return {
        items,
        hasMore: Number(meta.current_page ?? page) < Number(meta.last_page ?? 1),
    }
}

/**
 * Factory fetcher satuan per baris detail — hanya menampilkan satuan yang
 * tersedia untuk item di lokasi sumber (berdasarkan stock_units di stock record).
 * Fallback: semua satuan jika item belum dipilih atau sumber belum dipilih.
 */
const makeUnitFetcher = (getItemUid: () => string | null | undefined): AsyncFetcher<Option> => {
    return async ({ search, page, perPage, signal }) => {
        const itemUid = getItemUid()

        // Tanpa item terpilih atau lokasi sumber → fallback ke semua satuan
        if (!itemUid || !from_warehouse_uid.value) {
            const res = await api.get('/item_units', {
                params: { page, per_page: perPage, search, sort_by: 'name', sort_dir: 'asc' },
                signal,
            })
            const payload = res.data ?? {}
            const meta = payload.meta ?? payload.pagination ?? {}
            return {
                items: (payload.data ?? []).map((u: any) => ({
                    title: `${u.symbol} – ${u.name}`,
                    value: u.uid,
                })),
                hasMore: Number(meta.current_page ?? page) < Number(meta.last_page ?? 1),
            }
        }

        // Ambil stock untuk item ini di lokasi sumber — deduce available units
        const params: Record<string, any> = {
            page: 1,
            per_page: -1,
            warehouse_uid: from_warehouse_uid.value,
            with_stock_only: 1,
        }
        if (from_rack_uid.value) params.rack_uid = from_rack_uid.value
        if (from_tank_uid.value) params.tank_uid = from_tank_uid.value

        const res = await api.get('/stock', { params, signal })
        const stocks = res.data?.data ?? []

        // Filter stock untuk item yang dipilih
        const stockForItem = stocks.filter((s: any) => s.item?.uid === itemUid)

        // Kumpulkan semua unit unik dari stock_units (atau base unit bila tidak ada stock_units)
        const seen = new Set<string>()
        const items: Option[] = []

        for (const stock of stockForItem) {
            if (Array.isArray(stock.stock_units) && stock.stock_units.length > 0) {
                for (const su of stock.stock_units) {
                    const uid = su.unit_uid ?? su.unit?.uid
                    if (!uid || seen.has(uid)) continue
                    seen.add(uid)
                    const name = su.unit_name ?? su.unit?.name ?? ''
                    const symbol = su.unit_symbol ?? su.unit?.symbol ?? ''
                    items.push({ title: `${symbol} – ${name}`, value: uid })
                }
            } else if (stock.unit) {
                // Fallback: pakai base unit dari stock
                const uid = stock.unit.uid
                if (!seen.has(uid)) {
                    seen.add(uid)
                    items.push({ title: `${stock.unit.symbol} – ${stock.unit.name}`, value: uid })
                }
            }
        }

        // Client-side search filter (dataset kecil, tidak masalah)
        const filtered = search
            ? items.filter((i) => i.title.toLowerCase().includes(search.toLowerCase()))
            : items

        return { items: filtered, hasMore: false }
    }
}

// ─── initialSelected helpers (untuk edit mode) ──────────────────────────────
const toOpt = (o: { uid: string; name: string } | null | undefined): Option | null =>
    o ? { title: o.name, value: o.uid } : null

const initialFromWarehouse = computed(() => toOpt(props.data.from_warehouse))
const initialFromRack = computed(() => toOpt(props.data.from_rack))
const initialFromTank = computed(() => toOpt(props.data.from_tank))
const initialToWarehouse = computed(() => toOpt(props.data.to_warehouse))
const initialToRack = computed(() => toOpt(props.data.to_rack))
const initialToTank = computed(() => toOpt(props.data.to_tank))

const initialItems = computed<Option[]>(() => {
    const list: Option[] = []
    for (const d of props.data.details ?? []) {
        if (d.item) list.push({ title: d.item.name, value: d.item.uid })
    }
    return list
})
const initialUnits = computed<Option[]>(() => {
    const list: Option[] = []
    for (const d of props.data.details ?? []) {
        if (d.unit) list.push({ title: `${d.unit.symbol} – ${d.unit.name}`, value: d.unit.uid })
    }
    return list
})

// ─── Stepper navigation ─────────────────────────────────────────────────────
const goToStep = async (target: 1 | 2 | 3) => {
    // Tandai submit attempted agar error baru mulai ditampilkan setelah klik Next
    submitAttempted.value = true

    const { errors: errs } = await validate()
    const errKeys = Object.keys(errs)

    if (target >= 2) {
        if (errKeys.some((k) => (STEP1_FIELDS as readonly string[]).includes(k))) {
            currentStep.value = 1
            return
        }
        step1Valid.value = true
    }
    if (target >= 3) {
        if (errKeys.some((k) => k.startsWith('details'))) {
            currentStep.value = 2
            return
        }
        step2Valid.value = true
    }
    currentStep.value = target
}

const back = () => {
    if (currentStep.value > 1) currentStep.value = (currentStep.value - 1) as 1 | 2
}

// ─── Submit ──────────────────────────────────────────────────────────────────
const handleSubmitError = (ctx: any) => {
    const keys = Object.keys(ctx?.errors ?? {})
    if (keys.some((k) => (STEP1_FIELDS as readonly string[]).includes(k))) currentStep.value = 1
    else if (keys.some((k) => k.startsWith('details'))) currentStep.value = 2
}

/** Simpan transfer saja (tanpa displacement). */
const onSubmit = handleSubmit(
    (formValues) => emit('submit', formValues),
    handleSubmitError,
)

/** Simpan transfer + trigger wizard displacement untuk item yang ada di destinasi. */
const onSubmitWithDisplacement = handleSubmit(
    (formValues) => {
        emit('submit', formValues, {
            setupDisplacement: true,
            occupants: conflictOccupants.value,
        })
    },
    handleSubmitError,
)

const onClose = () => emit('close')

// ─── Review summary helpers ─────────────────────────────────────────────────
const formatLocation = (warehouse: Option | null, rack: Option | null, tank: Option | null) => {
    if (!warehouse) return '-'
    const parts = [warehouse.title]
    if (rack) parts.push(`Rak: ${rack.title}`)
    if (tank) parts.push(`Tangki: ${tank.title}`)
    return parts.join(' • ')
}

// ─── Selected option cache (agar label tampil di Review saat create) ────────
const selectedFromWarehouse = ref<Option | null>(initialFromWarehouse.value)
const selectedFromRack      = ref<Option | null>(initialFromRack.value)
const selectedFromTank      = ref<Option | null>(initialFromTank.value)
const selectedToWarehouse   = ref<Option | null>(initialToWarehouse.value)
const selectedToRack        = ref<Option | null>(initialToRack.value)
const selectedToTank        = ref<Option | null>(initialToTank.value)
const selectedItems = ref<Record<number, Option | null>>({})
const selectedUnits = ref<Record<number, Option | null>>({})

const sourceLabel = computed(() =>
    formatLocation(
        selectedFromWarehouse.value,
        from_rack_uid.value ? selectedFromRack.value : null,
        from_tank_uid.value ? selectedFromTank.value : null,
    )
)
const destLabel = computed(() =>
    formatLocation(
        selectedToWarehouse.value,
        to_rack_uid.value ? selectedToRack.value : null,
        to_tank_uid.value ? selectedToTank.value : null,
    )
)

const totalItems = computed(() => detailRows.value.length)
const totalQty = computed(() =>
    detailRows.value.reduce((sum, row) => sum + (Number((row.value as any).qty) || 0), 0)
)

// ─── Deteksi Item Eksisting di Destinasi (Chain / Displacement) ─────────────
type DestOccupant = {
    item_uid: string
    item_name: string
    unit_uid: string
    unit_name: string
    unit_symbol: string
    qty: number
}

const destOccupants = ref<DestOccupant[]>([])
const loadingDestOccupants = ref(false)

/** Item yang sedang akan dipindah (agar tidak dianggap "mengganggu" destinasi). */
const incomingItemUids = computed<Set<string>>(() => {
    const set = new Set<string>()
    for (const row of detailRows.value) {
        const uid = (row.value as any).item_uid
        if (uid) set.add(uid)
    }
    return set
})

/** Occupants yang "konflik" — item berbeda dari yang dikirim. */
const conflictOccupants = computed<DestOccupant[]>(() =>
    destOccupants.value.filter((o) => !incomingItemUids.value.has(o.item_uid))
)

const hasDisplacement = computed(() => conflictOccupants.value.length > 0)

const loadDestOccupants = async () => {
    if (!to_warehouse_uid.value) {
        destOccupants.value = []
        return
    }

    loadingDestOccupants.value = true
    try {
        const params: Record<string, any> = {
            page: 1,
            per_page: -1,
            warehouse_uid: to_warehouse_uid.value,
            with_stock_only: 1,
        }
        if (to_rack_uid.value) {
            params.rack_uid = to_rack_uid.value
        } else if (to_tank_uid.value) {
            params.tank_uid = to_tank_uid.value
        } else {
            // Level gudang (tanpa rak/tangki) — tidak perlu deteksi displacement karena
            // multi item bisa koeksis. Skip.
            destOccupants.value = []
            return
        }

        const res = await api.get('/stock', { params })
        const stocks = res.data?.data ?? []

        const list: DestOccupant[] = []
        const seen = new Set<string>()
        for (const stock of stocks) {
            const itemUid = stock.item?.uid
            if (!itemUid || seen.has(itemUid)) continue
            seen.add(itemUid)

            // Ambil qty total dari stock_units (first unit) atau qty
            const firstUnit = stock.stock_units?.[0]
            const qty = firstUnit?.qty ?? stock.qty ?? 0
            const unit = firstUnit?.unit ?? stock.unit ?? {}

            list.push({
                item_uid: itemUid,
                item_name: stock.item?.name ?? '-',
                unit_uid: firstUnit?.unit_uid ?? unit.uid ?? '',
                unit_name: firstUnit?.unit_name ?? unit.name ?? '',
                unit_symbol: firstUnit?.unit_symbol ?? unit.symbol ?? '',
                qty: Number(qty),
            })
        }

        destOccupants.value = list
    } catch (err) {
        console.error('[ItemTransferForm] loadDestOccupants error:', err)
        destOccupants.value = []
    } finally {
        loadingDestOccupants.value = false
    }
}

// ─── Stock Preview di Sumber (untuk step 2) ─────────────────────────────────
type SourceStockEntry = { item_uid: string; unit_uid: string; qty: number; unit_symbol: string }

const sourceStockMap = ref<Record<string, number>>({})
const sourceStockSymbols = ref<Record<string, string>>({})
const loadingSourceStock = ref(false)

const stockKey = (itemUid: string, unitUid: string) => `${itemUid}::${unitUid}`

const loadSourceStock = async () => {
    if (!from_warehouse_uid.value) {
        sourceStockMap.value = {}
        sourceStockSymbols.value = {}
        return
    }

    loadingSourceStock.value = true
    try {
        const params: Record<string, any> = {
            page: 1,
            per_page: -1,
            warehouse_uid: from_warehouse_uid.value,
            with_stock_only: 1,
        }
        if (from_rack_uid.value) params.rack_uid = from_rack_uid.value
        else if (from_tank_uid.value) params.tank_uid = from_tank_uid.value

        const res = await api.get('/stock', { params })
        const stocks = res.data?.data ?? []

        const map: Record<string, number> = {}
        const symMap: Record<string, string> = {}

        for (const stock of stocks) {
            const itemUid = stock.item?.uid
            if (!itemUid) continue

            if (Array.isArray(stock.stock_units) && stock.stock_units.length > 0) {
                for (const su of stock.stock_units) {
                    const unitUid = su.unit_uid ?? su.unit?.uid
                    if (!unitUid) continue
                    const k = stockKey(itemUid, unitUid)
                    map[k] = (map[k] ?? 0) + Number(su.qty ?? 0)
                    symMap[k] = su.unit_symbol ?? su.unit?.symbol ?? ''
                }
            } else if (stock.unit) {
                const k = stockKey(itemUid, stock.unit.uid)
                map[k] = (map[k] ?? 0) + Number(stock.qty ?? 0)
                symMap[k] = stock.unit.symbol ?? ''
            }
        }

        sourceStockMap.value = map
        sourceStockSymbols.value = symMap
    } catch (err) {
        console.error('[ItemTransferForm] loadSourceStock error:', err)
    } finally {
        loadingSourceStock.value = false
    }
}

/** Qty tersedia untuk row tertentu (item_uid + unit_uid). */
const availableQtyFor = (row: any): { qty: number; symbol: string; hasData: boolean } => {
    const itemUid = row.item_uid
    const unitUid = row.unit_uid
    if (!itemUid || !unitUid) return { qty: 0, symbol: '', hasData: false }
    const k = stockKey(itemUid, unitUid)
    const qty = sourceStockMap.value[k]
    if (qty === undefined) return { qty: 0, symbol: '', hasData: false }
    return {
        qty,
        symbol: sourceStockSymbols.value[k] ?? '',
        hasData: true,
    }
}

/** Error message untuk input qty — gabungkan error validasi + cek stok. */
const qtyErrorFor = (idx: number, row: any): string | undefined => {
    if (submitAttempted.value) {
        const err = detailError(idx, 'qty')
        if (err) return err
    }
    const reqQty = Number(row.qty ?? 0)
    if (!reqQty) return undefined
    const avail = availableQtyFor(row)
    if (!avail.hasData) return undefined
    if (reqQty > avail.qty) {
        return t('qtyOverAvailable', { available: avail.qty, symbol: avail.symbol })
    }
    return undefined
}

// Load source stock saat lokasi sumber berubah & saat masuk step 2
watch([from_warehouse_uid, from_rack_uid, from_tank_uid], () => {
    if (currentStep.value >= 2) loadSourceStock()
})

watch(currentStep, (step) => {
    if (step === 2) loadSourceStock()
    if (step === 3) loadDestOccupants()
})

// ─── Exclude values — cegah pilihan ganda ───────────────────────────────────
/** Item yang sudah dipilih di baris lain (untuk exclude di dropdown item baris tertentu). */
const excludedItemsFor = (rowIdx: number): string[] => {
    return detailRows.value
        .map((r, i) => (i === rowIdx ? null : (r.value as any).item_uid))
        .filter((v): v is string => !!v)
}

/** Rak yang sudah dipilih di sumber atau tujuan (supaya tidak dipilih dua kali
 *  bila gudang sumber sama dengan gudang tujuan). */
const excludedFromRack = computed<string[]>(() => {
    // Kalau sumber dan tujuan di gudang yang sama, rak tujuan tidak boleh sama dengan rak sumber.
    if (from_warehouse_uid.value && from_warehouse_uid.value === to_warehouse_uid.value && to_rack_uid.value) {
        return [to_rack_uid.value]
    }
    return []
})
const excludedToRack = computed<string[]>(() => {
    if (from_warehouse_uid.value && from_warehouse_uid.value === to_warehouse_uid.value && from_rack_uid.value) {
        return [from_rack_uid.value]
    }
    return []
})
const excludedFromTank = computed<string[]>(() => {
    if (from_warehouse_uid.value && from_warehouse_uid.value === to_warehouse_uid.value && to_tank_uid.value) {
        return [to_tank_uid.value]
    }
    return []
})
const excludedToTank = computed<string[]>(() => {
    if (from_warehouse_uid.value && from_warehouse_uid.value === to_warehouse_uid.value && from_tank_uid.value) {
        return [from_tank_uid.value]
    }
    return []
})
</script>

<template>
    <v-dialog
        :model-value="modelValue"
        max-width="1100"
        persistent
        scrollable
    >
        <v-card class="rounded-lg d-flex flex-column" :loading="props.saving" style="max-height: 90vh;">
            <!-- Header -->
            <v-card-title class="d-flex align-center ga-2 px-4 px-sm-6 py-3 py-sm-4">
                <v-icon :icon="MdiSwapHorizontalBold" color="primary" />
                <span class="text-subtitle-1 text-sm-h6 font-weight-semibold text-truncate">{{ title }}</span>
                <v-spacer />
                <v-btn
                    :icon="MdiClose"
                    variant="text"
                    size="small"
                    :disabled="props.saving"
                    @click="onClose"
                />
            </v-card-title>

            <!-- Stepper header -->
            <v-sheet class="stepper-wrapper shrink-0 bg-surface" :elevation="0">
                <v-stepper
                    v-model="currentStep"
                    hide-actions
                    flat
                    editable
                    class="bg-transparent"
                >
                    <v-stepper-header>
                        <v-stepper-item
                            :value="1"
                            :title="t('locationSourceDest')"
                            :complete="currentStep > 1 && step1Valid"
                            color="primary"
                        />
                        <v-divider />
                        <v-stepper-item
                            :value="2"
                            :title="t('itemAndQty')"
                            :editable="step1Valid"
                            :complete="currentStep > 2 && step2Valid"
                            color="primary"
                        />
                        <v-divider />
                        <v-stepper-item
                            :value="3"
                            :title="t('reviewAndSubmit')"
                            :editable="step1Valid && step2Valid"
                            color="primary"
                        />
                    </v-stepper-header>
                </v-stepper>
            </v-sheet>

            <v-divider />

            <v-form @submit.prevent="onSubmit" class="d-flex flex-column grow" style="min-height: 0;">
                <v-card-text class="form-scroll px-4 px-sm-6 pt-6 pt-sm-8 pb-4 pb-sm-6 grow">
                    <v-stepper-window v-model="currentStep" class="ma-0">

                        <!-- ════ STEP 1: LOKASI ════ -->
                        <v-stepper-window-item :value="1">
                            <v-row dense>
                                <v-col cols="12" md="6">
                                    <base-date-input v-model="transfer_date" :label="t('transferDate')" />
                                </v-col>
                            </v-row>

                            <v-divider class="my-4" />

                            <div class="section-header mb-3">
                                <v-icon :icon="MdiWarehouse" size="18" color="error" />
                                <span class="text-subtitle-2 font-weight-bold text-error">{{ t('source') }}</span>
                            </div>
                            <v-row dense>
                                <v-col cols="12" md="4">
                                    <base-async-select
                                        v-model="from_warehouse_uid"
                                        :fetcher="fetchWarehouse"
                                        :initial-selected="initialFromWarehouse"
                                        :label="t('fromWarehouse')"
                                        :error-messages="submitAttempted ? (errors as any).from_warehouse_uid : undefined"
                                        :disabled="isEdit && props.data.status === 'Approved'"
                                        @select="(item) => selectedFromWarehouse = item as Option | null"
                                    />
                                </v-col>
                                <v-col cols="12" md="4">
                                    <base-async-select
                                        :key="`from-rack-${from_warehouse_uid}`"
                                        v-model="from_rack_uid"
                                        :fetcher="fetchFromRack"
                                        :initial-selected="initialFromRack"
                                        :label="`${t('rack')} (${t('optional')})`"
                                        :disabled="!from_warehouse_uid || !!from_tank_uid"
                                        :exclude-values="excludedFromRack"
                                        @select="(item) => selectedFromRack = item as Option | null"
                                    />
                                </v-col>
                                <v-col cols="12" md="4">
                                    <base-async-select
                                        :key="`from-tank-${from_warehouse_uid}`"
                                        v-model="from_tank_uid"
                                        :fetcher="fetchFromTank"
                                        :initial-selected="initialFromTank"
                                        :label="`${t('tank')} (${t('optional')})`"
                                        :disabled="!from_warehouse_uid || !!from_rack_uid"
                                        :exclude-values="excludedFromTank"
                                        @select="(item) => selectedFromTank = item as Option | null"
                                    />
                                </v-col>
                            </v-row>

                            <v-divider class="my-4" />

                            <div class="section-header mb-3">
                                <v-icon :icon="MdiWarehouse" size="18" color="success" />
                                <span class="text-subtitle-2 font-weight-bold text-success">{{ t('destination') }}</span>
                            </div>
                            <v-row dense>
                                <v-col cols="12" md="4">
                                    <base-async-select
                                        v-model="to_warehouse_uid"
                                        :fetcher="fetchWarehouse"
                                        :initial-selected="initialToWarehouse"
                                        :label="t('toWarehouse')"
                                        :error-messages="submitAttempted ? (errors as any).to_warehouse_uid : undefined"
                                        @select="(item) => selectedToWarehouse = item as Option | null"
                                    />
                                </v-col>
                                <v-col cols="12" md="4">
                                    <base-async-select
                                        :key="`to-rack-${to_warehouse_uid}`"
                                        v-model="to_rack_uid"
                                        :fetcher="fetchToRack"
                                        :initial-selected="initialToRack"
                                        :label="`${t('rack')} (${t('optional')})`"
                                        :disabled="!to_warehouse_uid || !!to_tank_uid"
                                        :exclude-values="excludedToRack"
                                        @select="(item) => selectedToRack = item as Option | null"
                                    />
                                </v-col>
                                <v-col cols="12" md="4">
                                    <base-async-select
                                        :key="`to-tank-${to_warehouse_uid}`"
                                        v-model="to_tank_uid"
                                        :fetcher="fetchToTank"
                                        :initial-selected="initialToTank"
                                        :label="`${t('tank')} (${t('optional')})`"
                                        :disabled="!to_warehouse_uid || !!to_rack_uid"
                                        :exclude-values="excludedToTank"
                                        @select="(item) => selectedToTank = item as Option | null"
                                    />
                                </v-col>
                            </v-row>

                            <v-alert
                                v-if="!from_rack_uid && !from_tank_uid && from_warehouse_uid"
                                type="info"
                                variant="tonal"
                                density="compact"
                                class="mt-4"
                                :text="t('locationLevelHint')"
                            />
                        </v-stepper-window-item>

                        <!-- ════ STEP 2: ITEM ════ -->
                        <v-stepper-window-item :value="2">
                            <div class="d-flex align-center justify-space-between mb-3 flex-wrap ga-2">
                                <div class="section-header">
                                    <v-icon :icon="MdiPackageVariantClosed" size="18" color="primary" />
                                    <span class="text-subtitle-2 font-weight-bold text-primary">{{ t('details') }}</span>
                                    <v-chip size="x-small" color="primary" variant="tonal" class="ms-2">
                                        {{ detailRows.length }}
                                    </v-chip>
                                </div>
                                <v-btn
                                    size="small"
                                    color="primary"
                                    variant="tonal"
                                    :prepend-icon="MdiPlus"
                                    @click="addRow({ item_uid: null, unit_uid: null, qty: null, description: null })"
                                >
                                    {{ t('addItem') }}
                                </v-btn>
                            </div>

                            <v-sheet rounded="lg" border class="overflow-hidden">
                                <v-table density="compact" class="detail-table">
                                    <thead>
                                        <tr>
                                            <th class="text-center" style="width: 40px;">No.</th>
                                            <th>{{ t('item') }}</th>
                                            <th style="width: 200px;">{{ t('unit') }}</th>
                                            <th class="text-end" style="width: 130px;">{{ t('qty') }}</th>
                                            <th>{{ t('description') }}</th>
                                            <th style="width: 56px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(row, idx) in detailRows" :key="row.key">
                                            <td class="text-center text-caption text-medium-emphasis">{{ idx + 1 }}</td>
                                            <td class="py-2">
                                                <base-async-select
                                                    :key="`item-${idx}-${from_warehouse_uid}-${from_rack_uid}-${from_tank_uid}`"
                                                    v-model="(row.value as any).item_uid"
                                                    :fetcher="fetchItem"
                                                    :initial-selected="initialItems[idx] || null"
                                                    :label="t('item')"
                                                    :error-messages="submitAttempted ? detailError(idx, 'item_uid') : undefined"
                                                    :exclude-values="excludedItemsFor(idx)"
                                                    density="compact"
                                                    @select="(item) => selectedItems[idx] = item as Option | null"
                                                />
                                            </td>
                                            <td class="py-2">
                                                <base-async-select
                                                    :key="`unit-${idx}-${(row.value as any).item_uid}-${from_warehouse_uid}-${from_rack_uid}-${from_tank_uid}`"
                                                    v-model="(row.value as any).unit_uid"
                                                    :fetcher="makeUnitFetcher(() => (row.value as any).item_uid)"
                                                    :initial-selected="initialUnits[idx] || null"
                                                    :label="t('unit')"
                                                    :error-messages="submitAttempted ? detailError(idx, 'unit_uid') : undefined"
                                                    :disabled="!(row.value as any).item_uid"
                                                    density="compact"
                                                    @select="(item) => selectedUnits[idx] = item as Option | null"
                                                />
                                            </td>
                                            <td class="py-2">
                                                <v-number-input
                                                    v-model="(row.value as any).qty"
                                                    :error-messages="qtyErrorFor(idx, row.value)"
                                                    :min="0.01"
                                                    :step="0.01"
                                                    density="compact"
                                                    variant="outlined"
                                                    hide-details="auto"
                                                    control-variant="stacked"
                                                />
                                            </td>
                                            <td class="py-2">
                                                <v-text-field
                                                    v-model="(row.value as any).description"
                                                    :placeholder="t('description')"
                                                    density="compact"
                                                    variant="outlined"
                                                    hide-details="auto"
                                                />
                                            </td>
                                            <td class="text-center">
                                                <v-btn
                                                    :icon="MdiTrashCanOutline"
                                                    variant="text"
                                                    size="x-small"
                                                    color="error"
                                                    :disabled="detailRows.length <= 1"
                                                    @click="removeRow(idx)"
                                                />
                                            </td>
                                        </tr>
                                    </tbody>
                                </v-table>
                            </v-sheet>

                            <div
                                v-if="(errors as any).details && typeof (errors as any).details === 'string'"
                                class="text-caption text-error mt-2 ms-1"
                            >
                                {{ (errors as any).details }}
                            </div>

                            <v-divider class="my-4" />

                            <v-row dense>
                                <v-col cols="12">
                                    <v-textarea
                                        v-model="notes"
                                        :label="`${t('notes')} (${t('optional')})`"
                                        variant="outlined"
                                        density="comfortable"
                                        rows="2"
                                        auto-grow
                                        hide-details="auto"
                                    />
                                </v-col>
                            </v-row>
                        </v-stepper-window-item>

                        <!-- ════ STEP 3: REVIEW ════ -->
                        <v-stepper-window-item :value="3">
                            <div class="section-header mb-3">
                                <v-icon :icon="MdiClipboardCheckOutline" size="18" color="primary" />
                                <span class="text-subtitle-2 font-weight-bold text-primary">{{ t('reviewAndSubmit') }}</span>
                            </div>

                            <v-row dense>
                                <v-col cols="12" md="6">
                                    <v-sheet rounded="lg" border class="pa-4 h-100">
                                        <div class="d-flex align-center ga-2 mb-2">
                                            <v-icon :icon="MdiWarehouse" color="error" size="20" />
                                            <span class="text-overline">{{ t('source') }}</span>
                                        </div>
                                        <div class="text-body-2 font-weight-medium">{{ sourceLabel }}</div>
                                    </v-sheet>
                                </v-col>
                                <v-col cols="12" md="6">
                                    <v-sheet rounded="lg" border class="pa-4 h-100">
                                        <div class="d-flex align-center ga-2 mb-2">
                                            <v-icon :icon="MdiWarehouse" color="success" size="20" />
                                            <span class="text-overline">{{ t('destination') }}</span>
                                        </div>
                                        <div class="text-body-2 font-weight-medium">{{ destLabel }}</div>
                                    </v-sheet>
                                </v-col>
                                <v-col cols="6" md="3">
                                    <v-sheet rounded="lg" border class="pa-4">
                                        <div class="text-overline mb-1">{{ t('transferDate') }}</div>
                                        <div class="text-body-2 font-weight-medium">{{ values.transfer_date }}</div>
                                    </v-sheet>
                                </v-col>
                                <v-col cols="6" md="3">
                                    <v-sheet rounded="lg" border class="pa-4">
                                        <div class="text-overline mb-1">{{ t('totalItems') }}</div>
                                        <div class="text-body-2 font-weight-medium">{{ totalItems }}</div>
                                    </v-sheet>
                                </v-col>
                                <v-col cols="12" md="6">
                                    <v-sheet rounded="lg" border class="pa-4">
                                        <div class="text-overline mb-1">{{ t('totalQty') }}</div>
                                        <div class="text-body-2 font-weight-medium">{{ totalQty }}</div>
                                    </v-sheet>
                                </v-col>
                            </v-row>

                            <div class="section-header mt-4 mb-3">
                                <v-icon :icon="MdiPackageVariantClosed" size="18" color="primary" />
                                <span class="text-subtitle-2 font-weight-bold text-primary">{{ t('details') }}</span>
                            </div>
                            <v-sheet rounded="lg" border class="overflow-hidden">
                                <v-table density="compact">
                                    <thead>
                                        <tr>
                                            <th class="text-center" style="width: 40px;">No.</th>
                                            <th>{{ t('item') }}</th>
                                            <th class="text-end">{{ t('qty') }}</th>
                                            <th>{{ t('description') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(row, idx) in detailRows" :key="row.key">
                                            <td class="text-center text-caption">{{ idx + 1 }}</td>
                                            <td class="text-body-2">
                                                {{ selectedItems[idx]?.title || initialItems[idx]?.title || '-' }}
                                            </td>
                                            <td class="text-end font-weight-medium">
                                                {{ (row.value as any).qty }}
                                                <span class="text-medium-emphasis">
                                                    {{ selectedUnits[idx]?.title || initialUnits[idx]?.title || '' }}
                                                </span>
                                            </td>
                                            <td class="text-body-2 text-medium-emphasis">{{ (row.value as any).description || '-' }}</td>
                                        </tr>
                                    </tbody>
                                </v-table>
                            </v-sheet>

                            <v-alert v-if="notes" type="info" variant="tonal" density="compact" class="mt-4">
                                <strong>{{ t('notes') }}:</strong> {{ notes }}
                            </v-alert>

                            <v-alert
                                type="warning"
                                variant="tonal"
                                density="compact"
                                class="mt-4"
                                :text="t('approvalRequiredHint')"
                            />

                            <!-- ═══ Displacement detection — lokasi tujuan terisi ═══ -->
                            <v-sheet
                                v-if="loadingDestOccupants"
                                rounded="lg"
                                border
                                class="pa-4 mt-4 text-center"
                            >
                                <v-progress-circular indeterminate size="20" width="2" color="primary" />
                                <span class="ms-2 text-body-2 text-medium-emphasis">
                                    {{ t('checkingDestination') }}
                                </span>
                            </v-sheet>

                            <v-alert
                                v-else-if="hasDisplacement"
                                type="warning"
                                variant="tonal"
                                density="compact"
                                class="mt-4"
                                :prepend-icon="MdiLinkVariant"
                            >
                                <div class="text-body-2 font-weight-bold mb-2">
                                    {{ t('displacementDetected') }}
                                </div>
                                <div class="text-caption mb-2">
                                    {{ t('displacementHint') }}
                                </div>
                                <v-sheet rounded="sm" class="pa-2 mb-1" color="surface-variant">
                                    <div
                                        v-for="occ in conflictOccupants"
                                        :key="occ.item_uid"
                                        class="text-caption d-flex align-center ga-2"
                                    >
                                        <v-icon :icon="MdiPackageVariantClosed" size="14" />
                                        <span class="font-weight-medium">{{ occ.item_name }}</span>
                                        <span class="text-medium-emphasis">
                                            {{ occ.qty }} {{ occ.unit_symbol }}
                                        </span>
                                    </div>
                                </v-sheet>
                            </v-alert>
                        </v-stepper-window-item>
                    </v-stepper-window>
                </v-card-text>

                <v-divider />

                <v-card-actions class="pa-4 justify-end ga-2">
                    <v-btn variant="tonal" :disabled="props.saving" @click="onClose">
                        {{ t('cancel') }}
                    </v-btn>

                    <v-btn
                        v-if="currentStep > 1"
                        variant="outlined"
                        :disabled="props.saving"
                        :prepend-icon="MdiArrowLeft"
                        @click="back"
                    >
                        {{ t('back') }}
                    </v-btn>

                    <v-btn
                        v-if="currentStep < 3"
                        color="primary"
                        variant="elevated"
                        :disabled="props.saving"
                        :append-icon="MdiArrowRight"
                        @click="goToStep((currentStep + 1) as 2 | 3)"
                    >
                        {{ t('next') }}
                    </v-btn>

                    <!-- Step 3: save only (tanpa displacement) -->
                    <v-btn
                        v-else
                        type="submit"
                        color="primary"
                        variant="elevated"
                        :loading="props.saving"
                        :prepend-icon="MdiContentSaveOutline"
                    >
                        {{ t('save') }}
                    </v-btn>

                    <!-- Step 3: save + setup displacement (muncul saat ada konflik) -->
                    <v-btn
                        v-if="currentStep === 3 && hasDisplacement"
                        color="deep-orange"
                        variant="elevated"
                        :loading="props.saving"
                        :prepend-icon="MdiSwapVerticalBold"
                        @click="onSubmitWithDisplacement"
                    >
                        {{ t('saveAndArrangeDisplacement') }}
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

.stepper-wrapper {
    position: relative;
    z-index: 1;
    background: rgb(var(--v-theme-surface));
    border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.08);
}

/* Scrollable area dengan padding top ekstra agar floating label Vuetify
   (yang posisinya sedikit di atas border input) tidak ter-clip saat scroll */
.form-scroll {
    overflow-y: auto;
    /* Pastikan floating label field paling atas tidak terkena border-top v-card-text */
    padding-top: 28px !important;
}

@media (min-width: 600px) {
    .form-scroll {
        padding-top: 36px !important;
    }
}

.stepper-wrapper :deep(.v-stepper) {
    border-radius: 0;
    box-shadow: none !important;
}

.stepper-wrapper :deep(.v-stepper-header) {
    box-shadow: none !important;
    min-height: 64px;
}

.stepper-wrapper :deep(.v-stepper-actions) {
    display: none !important;
}

/* Detail table — input segaris vertikal */
.detail-table :deep(td) {
    vertical-align: middle;
}

.detail-table :deep(.v-input__details) {
    /* Reserve consistent space untuk error/hint text agar input tidak naik-turun */
    min-height: 18px;
    padding-top: 2px;
}

.detail-table :deep(.v-field) {
    /* Ratakan tinggi semua field di compact density */
    --v-field-padding-top: 6px;
    --v-field-padding-bottom: 6px;
}
</style>
