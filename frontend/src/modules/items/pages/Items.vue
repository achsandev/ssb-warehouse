<script lang="ts" setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
// Import Types
import type { DataTableHeader } from 'vuetify'
// Import Components
import BaseSearchInput from '@/components/base/BaseSearchInput.vue'
import BaseRefreshButton from '@/components/base/BaseRefreshButton.vue'
import BaseCreateButton from '@/components/base/BaseCreateButton.vue'
import ActionsMenu from '@/components/common/ActionsMenu.vue'
import StockConversionDialog from '@/modules/items/components/StockConversionDialog.vue'
import ItemsForm from '@/modules/items/components/ItemsForm.vue'
import ItemsDetail from '@/modules/items/components/ItemsDetail.vue'
import ConfirmDeleteDialog from '@/components/common/ConfirmDeleteDialog.vue'
// Icons
import MdiDatabaseSyncOutline from '~icons/mdi/database-sync-outline'
// Import Stores
import { useItemsStore } from '@/stores/items'
import { useStockStore } from '@/stores/stock'
import { useStockLookupStore } from '@/stores/lookup/stock.store'
import { useMessageStore } from '@/stores/message'
// Import Composables
import { useTranslate } from '@/composables/useTranslate'
import { useAbility } from '@/composables/useAbility'
// Import Utils
import { formatRupiah } from '@/utils/currency'

const t = useTranslate()
const { can } = useAbility()
const store = useItemsStore()
const stockStore = useStockStore()
const stockLookupStore = useStockLookupStore()
const message = useMessageStore()

// ─── Stock summary per item (uid → { qty, status }) ─────────────────────────
type StockStatus = { label: string; color: 'success' | 'warning' | 'error' }
type StockUnitBreakdown = { unit_uid: string; symbol: string; qty: number }
type StockSummaryEntry = {
    /** Total qty pada satuan dasar item (untuk perhitungan status). */
    baseQty: number
    /** Rincian qty per satuan (agregasi lintas semua stock records). */
    units: StockUnitBreakdown[]
    status: StockStatus
}

const stockSummary = computed<Record<string, StockSummaryEntry>>(() => {
    // Agregasi semua stock_units per item
    const agg: Record<string, Map<string, StockUnitBreakdown>> = {}

    for (const stock of stockLookupStore.data ?? []) {
        const itemUid = (stock as any).item?.uid
        if (!itemUid) continue

        const units = (stock as any).stock_units ?? []
        if (!agg[itemUid]) agg[itemUid] = new Map()

        for (const su of units) {
            const key = su.unit_uid ?? su.unit?.uid
            if (!key) continue
            const existing = agg[itemUid].get(key)
            const qty = Number(su.qty ?? 0)
            const symbol = su.unit_symbol ?? su.unit?.symbol ?? ''
            if (existing) {
                existing.qty += qty
            } else {
                agg[itemUid].set(key, { unit_uid: key, symbol, qty })
            }
        }
    }

    const result: Record<string, StockSummaryEntry> = {}

    for (const item of store.data) {
        const unitsMap = agg[item.uid]
        const units: StockUnitBreakdown[] = unitsMap ? Array.from(unitsMap.values()) : []

        // Qty pada satuan dasar item (untuk status); fallback ke 0 bila tidak ada
        const baseUnitUid = item.unit?.uid
        const baseQty = baseUnitUid
            ? (unitsMap?.get(baseUnitUid)?.qty ?? 0)
            : (units[0]?.qty ?? 0)

        const min = item.min_qty ?? 0
        let status: StockStatus
        if (baseQty <= 0 && units.every(u => u.qty <= 0)) {
            status = { label: t('outOfStock'), color: 'error' }
        } else if (baseQty < min) {
            status = { label: t('lowStock'), color: 'warning' }
        } else {
            status = { label: t('available'), color: 'success' }
        }

        result[item.uid] = { baseQty, units, status }
    }

    return result
})

const route = useRoute()
const router = useRouter()

/**
 * Buka dialog detail otomatis bila URL punya `?view={uid}` —
 * dipicu oleh QR barcode item saat discan dari perangkat lain.
 */
const openDetailFromQuery = async () => {
    const uid = typeof route.query.view === 'string' ? route.query.view : null
    if (!uid) return

    try {
        await store.getByUid(uid)
        const item = store.data as unknown as ItemsList
        if (item && (item as any).uid) {
            await handleOpenDetailDialog(item)
        }
    } catch (err) {
        console.error('[Items] openDetailFromQuery error:', err)
    } finally {
        // Bersihkan query agar tidak re-trigger saat navigasi internal.
        router.replace({ query: { ...route.query, view: undefined } })
    }
}

onMounted(async () => {
    stockLookupStore.fetch()
    await openDetailFromQuery()
})

// =========================
// State
// =========================
/**
 * `submitting` menutupi seluruh alur save: item create/update → stock
 * create/update → reload data. Semua tombol di-disable selama proses ini
 * dan overlay loading ditampilkan. Flag di-release di `finally` supaya
 * error parsial tidak bikin UI terkunci permanen.
 */
const submitting = ref<boolean>(false)
const formDialog = ref<boolean>(false)
const confirmDialog = ref<boolean>(false)
const detailDialog = ref<boolean>(false)
const detailItem = ref<ItemsList | null>(null)
const detailStocks = ref<StockList[]>([])
const formAction = ref<'create' | 'update'>('create')

// Stock data yang terkait dengan item (untuk edit). Minimal 1 record per item.
type UidNamed = { uid: string; name: string }
const stockInitial = ref<{
    uid: string | null
    warehouse_uid: string | null
    rack_uid: string | null
    tank_uid: string | null
    qty: number | null
    // Object snapshot supaya AsyncSelect menampilkan nama (bukan UID) di edit mode.
    warehouse: UidNamed | null
    rack: UidNamed | null
    tank: UidNamed | null
} | null>(null)

// Stock conversion state
const conversionDialog = ref(false)
const conversionItem = ref<ItemsList | null>(null)
const conversionStocks = ref<StockList[]>([])

const formField = reactive<ItemsList>({
    uid: '',
    code: '',
    name: '',
    brand: null,
    category: null,
    unit: null,
    min_qty: 0,
    part_number: '',
    interchange_part: '',
    image: undefined,
    movement_category: null,
    valuation_method: null,
    material_group: null,
    sub_material_group: null,
    supplier: null,
    request_types: null,
    unit_types: null,
    price: '',
    exp_date: undefined,
    additional_info: '',
    created_at: '',
    updated_at: '',
    created_by_name: '',
    updated_by_name: ''
})

const formTitle = computed(() => 
    formAction.value === 'create'
        ? t('createDataDialogTitle')
        : t('updateDataDialogTitle')
)

// =========================
// Table Options
// =========================
const itemPerPageOptions = [
    { title: '5', value: 5 },
    { title: '10', value: 10 },
    { title: '25', value: 25 },
    { title: '50', value: 50 },
    { title: '100', value: 100 },
    { title: 'All', value: -1 }
]

const options = ref<TableParams>({
    page: 1,
    itemsPerPage: 5,
    sortBy: [{ key: 'created_at', order: 'desc' }],
    search: ''
})

const headers = computed<DataTableHeader[]>(() => [
    { title: t('actions'), key: 'action', align: 'center', width: '20', sortable: false },
    { title: t('code'), key: 'code', align: 'start', nowrap: true },
    { title: t('brand'), key: 'brand', align: 'start', nowrap: true },
    { title: t('name'), key: 'name', align: 'start', nowrap: true },
    { title: t('basicUnit'), key: 'unit', align: 'start', nowrap: true },
    { title: t('latestPrice'), key: 'price', align: 'end', nowrap: true },
    { title: t('minQty'), key: 'min_qty', align: 'end', nowrap: true },
    { title: t('stock'), key: 'stock_qty', align: 'end', nowrap: true, sortable: false },
    { title: t('status'), key: 'stock_status', align: 'center', nowrap: true, sortable: false },
])

// =========================
// Methods
// =========================
const loadData = async () => {
    await store.fetch(options.value)
    // Refresh ringkasan stok agar kolom Stok & Status selalu terkini.
    stockLookupStore.fetch()
}

const resetForm = () => {
    Object.assign(formField, {
        uid: '',
        code: '',
        name: '',
        brand: null,
        category: null,
        unit: null,
        min_qty: 0,
        part_number: '',
        interchange_part: '',
        image: undefined,
        movement_category: null,
        valuation_method: null,
        material_group: null,
        sub_material_group: null,
        supplier: null,
        price: '',
        exp_date: undefined,
        additional_info: '',
        created_at: '',
        updated_at: '',
        created_by_name: '',
        updated_by_name: ''
    })
}

const handleClose = () => {
    resetForm()
    stockInitial.value = null
    formDialog.value = false
    detailDialog.value = false
    confirmDialog.value = false
}

// =========================
// Functions
// =========================
const actionMenusFor = (item: ItemsList): string[] => {
    const menus: string[] = ['detail']
    const isAdmin = can('manage', 'all')
    if (isAdmin || can('update', 'items')) {
        menus.push('update')
        // Konversi hanya muncul kalau item sudah punya stok
        const hasStock = (stockSummary.value[item.uid]?.units?.length ?? 0) > 0
        if (hasStock) menus.push('convert')
    }
    if (isAdmin || can('delete', 'items')) menus.push('delete')
    return menus
}

const handleActionMenu = (action: string, data: ItemsList) => {
    if (action === 'detail') return handleOpenDetailDialog(data)
    if (action === 'update') return handleOpenDialog(data)
    if (action === 'convert') return handleOpenConversion(data)
    return handleOpenConfirmDialog(data.uid)
}

const handleOpenConversion = async (item: ItemsList) => {
    conversionItem.value = item
    conversionStocks.value = []
    // Fetch fresh stocks untuk item ini
    await stockStore.getByItemUid(item.uid)
    conversionStocks.value = stockStore.data ?? []
    conversionDialog.value = true
}

const handleConversionSuccess = async () => {
    // Refresh item list + stock lookup agar kolom stok terupdate
    await loadData()
    conversionDialog.value = false
}

const handleOpenDialog = async (item: ItemsList | null) => {
    stockInitial.value = null

    if (item) {
        formAction.value = 'update'
        Object.assign(formField, item)

        // Load existing stock untuk item ini (ambil record pertama jika ada).
        try {
            await stockStore.getByItemUid(item.uid)
            const existing = stockStore.data?.[0]
            if (existing) {
                // Helper: hanya kirim snapshot object jika uid + name keduanya ada.
                const snap = (o: { uid?: string | null; name?: string | null } | null | undefined): UidNamed | null =>
                    o?.uid && o?.name ? { uid: o.uid, name: o.name } : null

                stockInitial.value = {
                    uid: existing.uid ?? null,
                    warehouse_uid: existing.warehouse?.uid ?? null,
                    rack_uid: existing.rack?.uid ?? null,
                    tank_uid: existing.tank?.uid ?? null,
                    qty: existing.stock_units?.[0]?.qty ?? null,
                    warehouse: snap(existing.warehouse),
                    rack: snap(existing.rack),
                    tank: snap(existing.tank),
                }
            }
        } catch (err) {
            console.error('Error loading stock:', err)
        }
    } else {
        formAction.value = 'create'
        resetForm()
    }
    formDialog.value = true
}

const handleOpenConfirmDialog = (uid: string) => {
    formField.uid = uid
    confirmDialog.value = true
}

const handleOpenDetailDialog = async (item: ItemsList) => {
    detailItem.value = item
    detailStocks.value = []
    detailDialog.value = true

    try {
        await stockStore.getByItemUid(item.uid)
        detailStocks.value = stockStore.data ?? []
    } catch (err) {
        console.error('Error loading stock for detail:', err)
    }
}

const handleSubmit = async (value: ItemsForm) => {
    // Pisahkan payload item vs stock agar proses tetap sesuai API masing-masing.
    const {
        stock_warehouse_uid,
        stock_rack_uid,
        stock_tank_uid,
        stock_qty,
        ...itemPayload
    } = value

    submitting.value = true
    try {
        let itemUid: string | null = null

        // ── 1. Item create / update ─────────────────────────────────────────
        if (formAction.value === 'create') {
            const created = await store.create(itemPayload as ItemsForm)
            itemUid = (created as any)?.uid ?? null
        } else if (formField.uid) {
            await store.update(formField.uid, itemPayload as ItemsForm)
            itemUid = formField.uid
        }

        if (store.error) return

        // ── 2. Stock create / update (opsional) ─────────────────────────────
        if (itemUid && stock_warehouse_uid) {
            const stockPayload: StockForm = {
                item_uid: itemUid,
                warehouse_uid: stock_warehouse_uid,
                rack_uid: stock_rack_uid ?? null,
                tank_uid: stock_tank_uid ?? null,
                unit_uid: value.unit_uid ?? null,
                qty: stock_qty ?? 0,
            }

            try {
                if (stockInitial.value?.uid) {
                    await stockStore.update(stockInitial.value.uid, stockPayload)
                } else {
                    await stockStore.create(stockPayload)
                }
            } catch (err: any) {
                message.setMessage({
                    text: err?.response?.data?.message || 'Gagal menyimpan data stok',
                    timeout: 3000,
                    color: 'error',
                })
                return
            }
        }

        // ── 3. Reload data tabel + ringkasan stok (tunggu sampai selesai) ──
        await loadData()

        // ── 4. Tutup dialog hanya setelah semua proses sukses ──────────────
        handleClose()
    } finally {
        submitting.value = false
    }
}

const handleDelete = () => {
    if (formField.uid) {
        store.delete(formField.uid)
    }
    handleClose()
}
</script>

<template>
    <v-card>
        <v-card-title class="d-flex flex-column flex-sm-row-reverse justify-space-between align-sm-center">
            <base-create-button
                :loading="store.loading || submitting"
                :disabled="store.loading || submitting"
                @click="handleOpenDialog(null)"
            />

            <div class="d-flex align-center">
                <base-search-input
                    :loading="store.loading || submitting"
                    v-model="options.search"
                    @update:model-value="loadData"
                />

                <base-refresh-button
                    :loading="store.loading || submitting"
                    :disabled="store.loading || submitting"
                    @click="loadData"
                />
            </div>
        </v-card-title>
        <v-card-text>
            <v-data-table-server
                v-model:options="options"
                :loading="store.loading"
                :headers="headers"
                :items="store.data"
                :items-length="store.total"
                :items-per-page="options.itemsPerPage"
                :items-per-page-text="t('itemsPerPage')"
                :items-per-page-options="itemPerPageOptions"
                @update:options="loadData"
            >
                <template v-slot:item.action="{ item }">
                    <actions-menu
                        :menus="actionMenusFor(item)"
                        :data="item"
                        @click="handleActionMenu"
                    />
                </template>

                <template v-slot:item.brand="{ item }">
                    {{ item.brand?.name }}
                </template>

                <template v-slot:item.category="{ item }">
                    {{ item.category?.name }}
                </template>

                <template v-slot:item.unit="{ item }">
                    {{ item.unit?.name }}
                </template>

                <template v-slot:item.price="{ item }">
                    {{ formatRupiah(item.price) }}
                </template>

                <template v-slot:item.movement_category="{ item }">
                    {{ item.movement_category?.name }}
                </template>

                <template v-slot:item.valuation_method="{ item }">
                    {{ item.valuation_method?.name }}
                </template>

                <template v-slot:item.stock_qty="{ item }">
                    <template v-if="stockSummary[item.uid]?.units?.length">
                        <div class="d-flex flex-column align-end ga-0">
                            <span
                                v-for="u in stockSummary[item.uid].units"
                                :key="u.unit_uid"
                                class="text-body-2 font-weight-medium"
                            >
                                {{ u.qty }}
                                <span class="text-medium-emphasis">{{ u.symbol }}</span>
                            </span>
                        </div>
                    </template>
                    <span v-else class="text-medium-emphasis">0</span>
                </template>

                <template v-slot:item.stock_status="{ item }">
                    <v-chip
                        :color="stockSummary[item.uid]?.status.color || 'error'"
                        size="x-small"
                        variant="tonal"
                        class="text-uppercase font-weight-bold"
                    >
                        {{ stockSummary[item.uid]?.status.label || t('outOfStock') }}
                    </v-chip>
                </template>
            </v-data-table-server>
        </v-card-text>
    </v-card>

    <items-form
        v-if="formDialog"
        v-model="formDialog"
        :title="formTitle"
        :data="formField"
        :stock-initial="stockInitial"
        :saving="store.loading || stockStore.loading || submitting"
        @submit="handleSubmit"
        @close="handleClose"
    />

    <!-- ════ Global saving overlay ════ -->
    <v-overlay
        :model-value="submitting"
        persistent
        class="items-saving-overlay align-center justify-center"
        scrim="rgba(0, 0, 0, 0.55)"
    >
        <div class="items-loader" role="status" aria-live="polite">
            <div class="items-loader__ring">
                <v-progress-circular
                    :size="72"
                    :width="3"
                    indeterminate
                    color="primary"
                />
                <v-icon
                    :icon="MdiDatabaseSyncOutline"
                    size="28"
                    class="items-loader__icon"
                    color="primary"
                />
            </div>
            <div class="items-loader__title">
                {{ t('savingData') }}<span class="items-loader__dots">…</span>
            </div>
            <div class="items-loader__sub">{{ t('pleaseWait') }}</div>
        </div>
    </v-overlay>

    <confirm-delete-dialog
        v-model="confirmDialog"
        @delete="handleDelete"
        @close="handleClose"
    />

    <items-detail
        v-model="detailDialog"
        :item="detailItem"
        :stocks="detailStocks"
        :loading="stockStore.loading"
    />

    <stock-conversion-dialog
        v-if="conversionDialog"
        v-model="conversionDialog"
        :item="conversionItem"
        :stocks="conversionStocks"
        @success="handleConversionSuccess"
    />
</template>

<style scoped>
/* ════ Loading overlay ════ */
.items-saving-overlay :deep(.v-overlay__content) {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
}

.items-loader {
    min-width: 240px;
    padding: 22px 28px;
    border-radius: 18px;
    background: rgb(var(--v-theme-surface));
    border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
    box-shadow:
        0 12px 28px rgba(0, 0, 0, 0.24),
        0 2px 4px rgba(0, 0, 0, 0.08);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    animation: loader-in 220ms ease-out;
}

@keyframes loader-in {
    from { opacity: 0; transform: translateY(8px) scale(0.97); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

.items-loader__ring {
    position: relative;
    width: 72px;
    height: 72px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.items-loader__icon {
    position: absolute;
    animation: loader-pulse 1.6s ease-in-out infinite;
}

@keyframes loader-pulse {
    0%, 100% { transform: scale(1);    opacity: 0.92; }
    50%      { transform: scale(1.15); opacity: 1; }
}

.items-loader__title {
    font-size: 0.95rem;
    font-weight: 700;
    color: rgba(var(--v-theme-on-surface), 0.95);
    margin-top: 4px;
    letter-spacing: 0.01em;
}
.items-loader__dots {
    display: inline-block;
    animation: loader-dots 1.2s steps(4, end) infinite;
    width: 1.2em;
    text-align: left;
    overflow: hidden;
    vertical-align: bottom;
}
@keyframes loader-dots {
    0%   { width: 0; }
    100% { width: 1.2em; }
}

.items-loader__sub {
    font-size: 0.78rem;
    color: rgba(var(--v-theme-on-surface), 0.6);
    letter-spacing: 0.02em;
}
</style>