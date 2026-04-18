<script lang="ts" setup>
import { computed, ref, watch } from 'vue'
import { useTranslate } from '@/composables/useTranslate'
import { formatRupiah } from '@/utils/currency'
import { formatDate } from '@/utils/date'
import { api } from '@/api/api'
// Icons
import MdiClose from '~icons/mdi/close'
import MdiPackageVariantClosed from '~icons/mdi/package-variant-closed'
import MdiShape from '~icons/mdi/shape'
import MdiIdentifier from '~icons/mdi/identifier'
import MdiRuler from '~icons/mdi/ruler'
import MdiCashMultiple from '~icons/mdi/cash-multiple'
import MdiTextLong from '~icons/mdi/text-long'
import MdiWarehouse from '~icons/mdi/warehouse'
import MdiImageOutline from '~icons/mdi/image-outline'
import MdiDownload from '~icons/mdi/download'
import MdiClockOutline from '~icons/mdi/clock-outline'
import MdiSwapHorizontalBold from '~icons/mdi/swap-horizontal-bold'
import MdiArrowRight from '~icons/mdi/arrow-right'
import MdiCubeOutline from '~icons/mdi/cube-outline'
import MdiAlertCircleOutline from '~icons/mdi/alert-circle-outline'
import MdiCheckCircleOutline from '~icons/mdi/check-circle-outline'
import MdiBarcode from '~icons/mdi/barcode'
import MdiContentCopy from '~icons/mdi/content-copy'
import MdiPrinter from '~icons/mdi/printer'
import { useThemeStore } from '@/stores/theme'
import { useMessageStore } from '@/stores/message'

type StockDetail = {
    uid: string
    warehouse?: { uid: string; name: string } | null
    rack?: { uid: string; name: string } | null
    tank?: { uid: string; name: string } | null
    stock_units?: { uid: string; qty: number; unit_uid: string; unit_name: string; unit_symbol: string }[] | null
}

const model = defineModel<boolean>()
const themeStore = useThemeStore()
const message = useMessageStore()

const heroColor = computed(() => themeStore.resolvedTheme === 'dark' ? '#ffffff' : '#000000')

const props = defineProps<{
    item: ItemsList | null
    stocks?: StockDetail[] | null
    loading?: boolean
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'close'): void
}>()

const t = useTranslate()

const handleClose = () => {
    model.value = false
    emit('close')
}

// =========================
// Barcode
// =========================
const barcodeSrc = computed<string | null>(() => {
    const src = (props.item as any)?.barcode
    return typeof src === 'string' && src.length > 0 ? src : null
})

const barcodeValue = computed<string>(() => {
    const raw = (props.item as any)?.barcode_value ?? props.item?.code ?? props.item?.part_number ?? props.item?.uid
    return raw ? String(raw) : '-'
})

const handleCopyBarcode = async () => {
    if (!barcodeValue.value || barcodeValue.value === '-') return
    try {
        await navigator.clipboard.writeText(barcodeValue.value)
        message.setMessage({ text: t('copied'), timeout: 1500, color: 'success' })
    } catch {
        message.setMessage({ text: t('copyFailed'), timeout: 2500, color: 'error' })
    }
}

/** Buka jendela cetak berisi gambar barcode — cocok untuk label printer. */
const handlePrintBarcode = () => {
    if (!barcodeSrc.value) return
    const w = window.open('', 'printBarcode', 'width=520,height=360')
    if (!w) return
    const safeName = (props.item?.name ?? '').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    const safeCode = barcodeValue.value.replace(/</g, '&lt;').replace(/>/g, '&gt;')
    w.document.write(`
        <!doctype html><html><head><title>${safeCode}</title>
        <style>
            body { font-family: Arial, sans-serif; display:flex; flex-direction:column; align-items:center; justify-content:center; margin:24px; }
            .name { font-size:14px; margin-bottom:8px; font-weight:600; }
            .code { font-family: monospace; font-size:12px; margin-top:6px; letter-spacing:0.08em; }
            img { max-width:100%; height:auto; }
            @media print { body { margin:0; } }
        </style></head><body>
            <div class="name">${safeName}</div>
            <img src="${barcodeSrc.value}" alt="Barcode" />
            <div class="code">${safeCode}</div>
            <script>window.addEventListener('load', () => { window.print(); setTimeout(() => window.close(), 300); });<\/script>
        </body></html>
    `)
    w.document.close()
}

// =========================
// Image download
// =========================
const imageUrl = computed<string | null>(() => {
    const img = props.item?.image
    return typeof img === 'string' ? img : null
})

const handleDownloadImage = async () => {
    if (!imageUrl.value) return
    try {
        const res = await fetch(imageUrl.value, { credentials: 'omit' })
        if (!res.ok) throw new Error('Failed to fetch image')
        const blob = await res.blob()
        const ext = (blob.type.split('/')[1] || 'jpg').split('+')[0]
        const safeName = (props.item?.code || props.item?.name || 'image').replace(/[^a-z0-9_-]/gi, '_')
        const filename = `${safeName}.${ext}`
        const objectUrl = URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = objectUrl
        a.download = filename
        a.rel = 'noopener'
        document.body.appendChild(a)
        a.click()
        a.remove()
        URL.revokeObjectURL(objectUrl)
    } catch {
        window.open(imageUrl.value, '_blank', 'noopener,noreferrer')
    }
}

// =========================
// Stock
// =========================
const totalStockQty = computed(() => {
    if (!props.stocks?.length) return 0
    return props.stocks.reduce((sum, s) => sum + (s.stock_units?.[0]?.qty ?? 0), 0)
})
const hasStock = computed(() => (props.stocks?.length ?? 0) > 0)

type StatusMeta = { label: string; color: string; bg: string; icon: any }
const stockStatus = computed<StatusMeta>(() => {
    const min = props.item?.min_qty ?? 0
    if (!hasStock.value) {
        return { label: t('outOfStock'), color: '#EF4444', bg: 'rgba(239,68,68,0.18)', icon: MdiAlertCircleOutline }
    }
    if (totalStockQty.value < min) {
        return { label: t('lowStock'), color: '#F59E0B', bg: 'rgba(245,158,11,0.18)', icon: MdiAlertCircleOutline }
    }
    return { label: t('available'), color: '#10B981', bg: 'rgba(16,185,129,0.18)', icon: MdiCheckCircleOutline }
})

const requestTypeNames = computed(() => props.item?.request_types?.map(r => r.name).join(', ') || '-')
const unitTypeNames = computed(() => props.item?.unit_types?.map(u => u.name).join(', ') || '-')

const formatQty = (n: number | null | undefined) =>
    Number(n ?? 0).toLocaleString('id-ID')

// =========================
// Transfer history
// =========================
type TransferHistoryItem = {
    uid: string
    transfer_number: string
    transfer_date: string
    status: string
    from_warehouse: { uid: string; name: string } | null
    from_rack: { uid: string; name: string } | null
    from_tank: { uid: string; name: string } | null
    to_warehouse: { uid: string; name: string } | null
    to_rack: { uid: string; name: string } | null
    to_tank: { uid: string; name: string } | null
    details: Array<{
        item: { uid: string; name: string } | null
        unit: { uid: string; name: string; symbol: string } | null
        qty: number
    }> | null
    created_by_name: string
}

const transferHistory = ref<TransferHistoryItem[]>([])
const loadingTransferHistory = ref(false)

const formatLocation = (
    warehouse: { name: string } | null,
    rack: { name: string } | null,
    tank: { name: string } | null,
) => {
    if (!warehouse) return '-'
    const parts = [warehouse.name]
    if (rack) parts.push(`(${rack.name})`)
    if (tank) parts.push(`(${tank.name})`)
    return parts.join(' ')
}

const getTransferQty = (trf: TransferHistoryItem): string => {
    if (!props.item?.uid || !trf.details) return '-'
    const matched = trf.details.filter(d => d.item?.uid === props.item?.uid)
    if (!matched.length) return '-'
    return matched.map(d => `${formatQty(d.qty)} ${d.unit?.symbol ?? ''}`).join(', ')
}

const transferStatusColor = (status: string) => {
    const s = (status ?? '').toLowerCase()
    if (s === 'approved') return 'success'
    if (s === 'rejected') return 'error'
    if (s === 'cancelled') return 'grey'
    if (s === 'pending displacement') return 'deep-orange'
    return 'warning'
}

const loadTransferHistory = async (itemUid: string) => {
    loadingTransferHistory.value = true
    try {
        const res = await api.get('/item_transfer', {
            params: {
                page: 1,
                per_page: 20,
                sort_by: 'created_at',
                sort_dir: 'desc',
                item_uid: itemUid,
            },
        })
        transferHistory.value = res.data?.data ?? []
    } catch (err) {
        console.error('[ItemsDetail] loadTransferHistory error:', err)
        transferHistory.value = []
    } finally {
        loadingTransferHistory.value = false
    }
}

watch(
    () => props.item?.uid,
    (uid) => {
        if (uid) loadTransferHistory(uid)
        else transferHistory.value = []
    },
    { immediate: true }
)

// =========================
// Info rows (DRY)
// =========================
const classificationRows = computed(() => [
    { label: t('materialGroup'),     value: props.item?.material_group?.name },
    { label: t('subMaterialGroup'),  value: props.item?.sub_material_group?.name },
    { label: t('itemCategory'),      value: props.item?.category?.name },
    { label: t('brand'),             value: props.item?.brand?.name },
    { label: t('movementCategory'),  value: props.item?.movement_category?.name },
])

const identityRows = computed(() => [
    { label: t('partNumber'),      value: props.item?.part_number },
    { label: t('interchangePart'), value: props.item?.interchange_part },
    { label: t('expDate'),         value: props.item?.exp_date ? formatDate(props.item.exp_date) : null },
])

const unitRows = computed(() => [
    { label: t('valuationMethod'), value: props.item?.valuation_method?.name },
    { label: t('unitTypes'),       value: unitTypeNames.value },
    { label: t('requestTypes'),    value: requestTypeNames.value },
])

const auditRows = computed(() => [
    { label: t('createdAt'), value: props.item?.created_at ? formatDate(props.item.created_at) : null },
    { label: t('createdBy'), value: props.item?.created_by_name },
    { label: t('updatedAt'), value: props.item?.updated_at ? formatDate(props.item.updated_at) : null },
    { label: t('updatedBy'), value: props.item?.updated_by_name },
])
</script>

<template>
    <v-dialog
        v-model="model"
        max-width="960"
        scrollable
        @update:model-value="v => !v && handleClose()"
    >
        <v-card
            v-if="item"
            class="rounded-lg d-flex flex-column detail-card"
            :loading="loading"
            style="max-height: 92vh;"
        >
            <!-- ══════ Hero ══════ -->
            <div class="hero" :style="{ color: heroColor }">
                <div class="hero-content">
                    <div class="d-flex justify-space-between align-start mb-3">
                        <div class="hero-badge">
                            <v-icon :icon="MdiPackageVariantClosed" class="hero-badge-icon" :color="heroColor" />
                        </div>
                        <v-btn
                            icon
                            density="comfortable"
                            variant="text"
                            color="white"
                            :aria-label="t('close')"
                            @click="handleClose"
                        >
                            <component :is="MdiClose" class="close-icon" :style="{ color: heroColor }" />
                        </v-btn>
                    </div>
                    <div class="hero-label">{{ t('item') }}</div>
                    <div class="hero-title">{{ item.name || '-' }}</div>
                    <div class="hero-meta">
                        <component :is="MdiIdentifier" class="hero-meta-icon" />
                        <span>{{ item.code || '-' }}</span>
                        <span v-if="item.unit?.symbol" class="hero-meta-dot">•</span>
                        <span v-if="item.unit?.symbol">{{ item.unit.name }} ({{ item.unit.symbol }})</span>
                    </div>
                    <div
                        class="status-pill mt-3"
                        :style="{ background: stockStatus.bg, color: stockStatus.color }"
                    >
                        <component :is="stockStatus.icon" class="status-pill-icon" />
                        <span>{{ stockStatus.label }}</span>
                    </div>
                </div>
            </div>

            <!-- ══════ Content ══════ -->
            <v-card-text class="pa-5 pa-sm-6 grow overflow-y-auto content">
                <!-- Stats -->
                <div class="stat-row">
                    <div class="stat-tile">
                        <component :is="MdiCubeOutline" class="stat-icon" />
                        <div>
                            <div class="stat-value">
                                {{ formatQty(totalStockQty) }}
                                <span class="stat-unit">{{ item.unit?.symbol || '' }}</span>
                            </div>
                            <div class="stat-label">{{ t('totalStock') }}</div>
                        </div>
                    </div>
                    <div class="stat-tile">
                        <component :is="MdiAlertCircleOutline" class="stat-icon" />
                        <div>
                            <div class="stat-value">
                                {{ formatQty(item.min_qty) }}
                                <span class="stat-unit">{{ item.unit?.symbol || '' }}</span>
                            </div>
                            <div class="stat-label">{{ t('minQty') }}</div>
                        </div>
                    </div>
                    <div class="stat-tile stat-tile--accent">
                        <component :is="MdiCashMultiple" class="stat-icon stat-icon--accent" />
                        <div>
                            <div class="stat-value">{{ item.price ? formatRupiah(item.price) : '-' }}</div>
                            <div class="stat-label">{{ t('latestPrice') }}</div>
                        </div>
                    </div>
                </div>

                <!-- Image -->
                <div
                    v-if="imageUrl"
                    class="image-card"
                    role="button"
                    tabindex="0"
                    :title="t('downloadImage')"
                    @click="handleDownloadImage"
                    @keydown.enter.space.prevent="handleDownloadImage"
                >
                    <div class="image-preview">
                        <v-img :src="imageUrl" cover class="rounded" />
                    </div>
                    <div class="image-body">
                        <div class="image-title">
                            <component :is="MdiImageOutline" class="image-title-icon" />
                            <span>{{ t('image') }}</span>
                        </div>
                        <div class="image-desc">{{ t('clickToDownload') }}</div>
                    </div>
                    <div class="image-action">
                        <component :is="MdiDownload" class="image-action-icon" />
                    </div>
                </div>

                <!-- Barcode -->
                <section v-if="barcodeSrc" class="section">
                    <div class="section-head">
                        <span class="section-accent" />
                        <component :is="MdiBarcode" class="section-icon" />
                        <h3 class="section-title">{{ t('barcode') }}</h3>
                    </div>
                    <div class="barcode-card">
                        <div class="barcode-preview">
                            <img :src="barcodeSrc" :alt="barcodeValue" />
                        </div>
                        <div class="barcode-body">
                            <div class="barcode-value">{{ barcodeValue }}</div>
                            <div class="barcode-hint">{{ t('scanOrCopy') }}</div>
                        </div>
                        <div class="barcode-actions">
                            <v-btn
                                :icon="MdiContentCopy"
                                variant="tonal"
                                density="comfortable"
                                size="small"
                                :title="t('copy')"
                                @click="handleCopyBarcode"
                            />
                            <v-btn
                                :icon="MdiPrinter"
                                variant="tonal"
                                color="primary"
                                density="comfortable"
                                size="small"
                                :title="t('print')"
                                @click="handlePrintBarcode"
                            />
                        </div>
                    </div>
                </section>

                <!-- Klasifikasi -->
                <section class="section">
                    <div class="section-head">
                        <span class="section-accent" />
                        <component :is="MdiShape" class="section-icon" />
                        <h3 class="section-title">{{ t('classification') }}</h3>
                    </div>
                    <div class="info-grid">
                        <div v-for="row in classificationRows" :key="row.label" class="info-tile">
                            <div class="info-label">{{ row.label }}</div>
                            <div class="info-value">{{ row.value || '-' }}</div>
                        </div>
                    </div>
                </section>

                <!-- Identitas -->
                <section class="section">
                    <div class="section-head">
                        <span class="section-accent" />
                        <component :is="MdiIdentifier" class="section-icon" />
                        <h3 class="section-title">{{ t('identity') }}</h3>
                    </div>
                    <div class="info-grid">
                        <div v-for="row in identityRows" :key="row.label" class="info-tile">
                            <div class="info-label">{{ row.label }}</div>
                            <div class="info-value">{{ row.value || '-' }}</div>
                        </div>
                    </div>
                </section>

                <!-- Satuan & Tipe -->
                <section class="section">
                    <div class="section-head">
                        <span class="section-accent" />
                        <component :is="MdiRuler" class="section-icon" />
                        <h3 class="section-title">{{ t('unitAndTypes') }}</h3>
                    </div>
                    <div class="info-grid">
                        <div v-for="row in unitRows" :key="row.label" class="info-tile">
                            <div class="info-label">{{ row.label }}</div>
                            <div class="info-value">{{ row.value || '-' }}</div>
                        </div>
                    </div>
                </section>

                <!-- Supplier -->
                <section class="section">
                    <div class="section-head">
                        <span class="section-accent" />
                        <component :is="MdiCashMultiple" class="section-icon" />
                        <h3 class="section-title">{{ t('pricingAndSupplier') }}</h3>
                    </div>
                    <div class="info-grid">
                        <div class="info-tile">
                            <div class="info-label">{{ t('supplier') }}</div>
                            <div class="info-value">{{ item.supplier?.name || '-' }}</div>
                        </div>
                    </div>
                </section>

                <!-- Info tambahan -->
                <section v-if="item.additional_info" class="section">
                    <div class="section-head">
                        <span class="section-accent" />
                        <component :is="MdiTextLong" class="section-icon" />
                        <h3 class="section-title">{{ t('additionalInfo') }}</h3>
                    </div>
                    <div class="note-block">{{ item.additional_info }}</div>
                </section>

                <!-- Stok -->
                <section class="section">
                    <div class="section-head">
                        <span class="section-accent" />
                        <component :is="MdiWarehouse" class="section-icon" />
                        <h3 class="section-title">{{ t('stock') }}</h3>
                        <span v-if="hasStock" class="section-count">{{ stocks?.length }}</span>
                    </div>

                    <div v-if="!hasStock" class="empty-state">
                        <component :is="MdiWarehouse" class="empty-icon" />
                        <div class="empty-text">{{ t('noStockData') }}</div>
                    </div>

                    <div v-else class="stock-list">
                        <div v-for="(stock, idx) in stocks" :key="stock.uid" class="stock-card">
                            <div class="stock-index">{{ idx + 1 }}</div>
                            <div class="stock-body">
                                <div class="stock-warehouse">
                                    <component :is="MdiWarehouse" class="stock-warehouse-icon" />
                                    {{ stock.warehouse?.name || '-' }}
                                </div>
                                <div class="stock-sub">
                                    <span v-if="stock.rack">{{ t('rack') }}: <b>{{ stock.rack.name }}</b></span>
                                    <span v-if="stock.tank">{{ t('tank') }}: <b>{{ stock.tank.name }}</b></span>
                                    <span v-if="!stock.rack && !stock.tank" class="text-muted">—</span>
                                </div>
                            </div>
                            <div class="stock-qty">
                                <div
                                    v-for="su in stock.stock_units ?? []"
                                    :key="su.uid"
                                    class="stock-qty-line"
                                >
                                    <span class="stock-qty-value">{{ formatQty(su.qty) }}</span>
                                    <span class="stock-qty-unit">{{ su.unit_symbol }}</span>
                                </div>
                                <span v-if="!stock.stock_units?.length" class="text-muted">-</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Riwayat Pemindahan -->
                <section class="section">
                    <div class="section-head">
                        <span class="section-accent" />
                        <component :is="MdiSwapHorizontalBold" class="section-icon" />
                        <h3 class="section-title">{{ t('transferHistory') }}</h3>
                        <span v-if="transferHistory.length" class="section-count">{{ transferHistory.length }}</span>
                    </div>

                    <div v-if="loadingTransferHistory" class="empty-state">
                        <v-progress-circular indeterminate size="20" width="2" color="primary" />
                        <div class="empty-text mt-2">{{ t('loading') }}</div>
                    </div>

                    <div v-else-if="!transferHistory.length" class="empty-state">
                        <component :is="MdiSwapHorizontalBold" class="empty-icon" />
                        <div class="empty-text">{{ t('noTransferHistory') }}</div>
                    </div>

                    <div v-else class="transfer-list">
                        <div v-for="trf in transferHistory" :key="trf.uid" class="transfer-card">
                            <div class="transfer-head">
                                <div>
                                    <div class="transfer-number">{{ trf.transfer_number }}</div>
                                    <div class="transfer-date">{{ formatDate(trf.transfer_date) }}</div>
                                </div>
                                <v-chip
                                    size="x-small"
                                    :color="transferStatusColor(trf.status)"
                                    variant="tonal"
                                    class="font-weight-bold text-uppercase"
                                >
                                    {{ trf.status }}
                                </v-chip>
                            </div>
                            <div class="transfer-route">
                                <span class="route-from">{{ formatLocation(trf.from_warehouse, trf.from_rack, trf.from_tank) }}</span>
                                <component :is="MdiArrowRight" class="route-arrow" />
                                <span class="route-to">{{ formatLocation(trf.to_warehouse, trf.to_rack, trf.to_tank) }}</span>
                            </div>
                            <div class="transfer-qty">
                                <span class="transfer-qty-label">{{ t('qty') }}:</span>
                                <span class="transfer-qty-value">{{ getTransferQty(trf) }}</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Audit -->
                <section class="section">
                    <div class="section-head">
                        <span class="section-accent" />
                        <component :is="MdiClockOutline" class="section-icon" />
                        <h3 class="section-title">{{ t('auditTrail') }}</h3>
                    </div>
                    <div class="audit-grid">
                        <div v-for="row in auditRows" :key="row.label" class="audit-row">
                            <div class="audit-label">{{ row.label }}</div>
                            <div class="audit-value">{{ row.value || '-' }}</div>
                        </div>
                    </div>
                </section>
            </v-card-text>

            <v-divider />
            <v-card-actions class="pa-3 justify-end">
                <v-btn variant="tonal" color="primary" size="small" @click="handleClose">
                    {{ t('close') }}
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<style scoped>
.detail-card { overflow: hidden; }

/* Hero */
.hero {
    position: relative;
    overflow: hidden;
    padding: 20px 22px 135px;
    color: #fff;
}
.hero-bg {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgb(var(--v-theme-primary)) 0%, rgba(var(--v-theme-primary), 0.75) 100%);
}
.hero-bg::before,
.hero-bg::after {
    content: '';
    position: absolute;
    border-radius: 50%;
    background: rgba(255,255,255,0.08);
}
.hero-bg::before { width: 200px; height: 200px; top: -70px; right: -50px; }
.hero-bg::after  { width: 140px; height: 140px; bottom: -50px; left: -40px; }
.hero-content { position: relative; }
.hero-badge {
    width: 44px; height: 44px;
    border-radius: 12px;
    background: rgba(180,180,180,0.2);
    display: flex; align-items: center; justify-content: center;
}
.hero-badge-icon { width: 22px; height: 22px; color: #fff; }
.close-icon { width: 18px; height: 18px; }
.hero-label {
    font-size: 0.7rem; letter-spacing: 0.08em;
    text-transform: uppercase; opacity: 0.8; font-weight: 500;
}
.hero-title {
    font-size: 1.4rem; font-weight: 700;
    line-height: 1.25; margin-top: 2px; letter-spacing: -0.01em;
    word-break: break-word;
}
.hero-meta {
    display: flex; align-items: center; flex-wrap: wrap;
    gap: 6px; font-size: 0.75rem; opacity: 0.9; margin-top: 8px;
}
.hero-meta-icon { width: 14px; height: 14px; }
.hero-meta-dot { opacity: 0.5; margin: 0 2px; }
.status-pill {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 5px 12px; border-radius: 999px;
    font-size: 0.75rem; font-weight: 700;
}
.status-pill-icon { width: 14px; height: 14px; }

/* Content */
.content { background: rgb(var(--v-theme-background)); }

/* Stats */
.stat-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 10px;
    margin-bottom: 20px;
}
.stat-tile {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 14px;
    border-radius: 12px;
    background: rgb(var(--v-theme-surface));
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
.stat-tile--accent {
    background: linear-gradient(135deg, rgba(var(--v-theme-primary), 0.08), rgba(var(--v-theme-primary), 0.02));
    border-color: rgba(var(--v-theme-primary), 0.25);
}
.stat-icon {
    width: 28px; height: 28px;
    padding: 6px; border-radius: 8px;
    background: rgba(var(--v-theme-primary), 0.12);
    color: rgb(var(--v-theme-primary));
    box-sizing: content-box;
    flex-shrink: 0;
}
.stat-icon--accent { background: rgb(var(--v-theme-primary)); color: #fff; }
.stat-value { font-size: 1.05rem; font-weight: 700; line-height: 1.2; word-break: break-word; }
.stat-unit {
    font-size: 0.7rem; font-weight: 500;
    color: rgba(var(--v-theme-on-surface), 0.6);
    margin-left: 2px;
}
.stat-label {
    font-size: 0.7rem; margin-top: 2px;
    color: rgba(var(--v-theme-on-surface), 0.6);
}

/* Image card */
.image-card {
    display: flex; align-items: center; gap: 14px;
    padding: 12px;
    border-radius: 12px;
    background: rgb(var(--v-theme-surface));
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    cursor: pointer;
    margin-bottom: 20px;
    transition: border-color 0.15s, background 0.15s, transform 0.15s;
    outline: none;
}
.image-card:hover {
    border-color: rgba(var(--v-theme-primary), 0.5);
    background: rgba(var(--v-theme-primary), 0.03);
}
.image-card:focus-visible {
    outline: 2px solid rgb(var(--v-theme-primary));
    outline-offset: 2px;
}
.image-card:active { transform: scale(0.995); }
.image-preview {
    width: 56px; height: 56px; flex-shrink: 0;
    border-radius: 8px; overflow: hidden;
    background: rgba(var(--v-theme-on-surface), 0.04);
}
.image-body { flex: 1; min-width: 0; }
.image-title {
    display: flex; align-items: center; gap: 6px;
    font-size: 0.85rem; font-weight: 600;
}
.image-title-icon { width: 16px; height: 16px; color: rgb(var(--v-theme-primary)); }
.image-desc {
    font-size: 0.72rem; margin-top: 2px;
    color: rgba(var(--v-theme-on-surface), 0.6);
}
.image-action {
    width: 36px; height: 36px;
    border-radius: 8px;
    background: rgba(var(--v-theme-primary), 0.1);
    color: rgb(var(--v-theme-primary));
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.image-action-icon { width: 18px; height: 18px; }

/* Barcode card */
.barcode-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px 14px;
    border-radius: 12px;
    background: rgb(var(--v-theme-surface));
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    border-left: 3px solid rgba(var(--v-theme-primary), 0.5);
}
.barcode-preview {
    flex-shrink: 0;
    padding: 8px 12px;
    background: #ffffff;
    border-radius: 8px;
    border: 1px solid rgba(0, 0, 0, 0.08);
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 180px;
    max-width: 260px;
}
.barcode-preview img {
    max-width: 100%;
    height: auto;
    display: block;
}
.barcode-body { flex: 1; min-width: 0; }
.barcode-value {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 0.95rem;
    font-weight: 600;
    letter-spacing: 0.06em;
    word-break: break-all;
}
.barcode-hint {
    font-size: 0.72rem;
    color: rgba(var(--v-theme-on-surface), 0.55);
    margin-top: 4px;
}
.barcode-actions {
    display: flex;
    gap: 6px;
    flex-shrink: 0;
}

@media (max-width: 600px) {
    .barcode-card {
        flex-direction: column;
        align-items: stretch;
    }
    .barcode-preview { max-width: 100%; }
    .barcode-actions { justify-content: flex-end; }
}

/* Sections */
.section { margin-bottom: 22px; }
.section-head {
    display: flex; align-items: center; gap: 8px;
    margin-bottom: 10px;
}
.section-accent {
    width: 3px; height: 16px;
    background: rgb(var(--v-theme-primary));
    border-radius: 2px;
}
.section-icon { width: 18px; height: 18px; color: rgb(var(--v-theme-primary)); }
.section-title {
    font-size: 0.85rem; font-weight: 700;
    margin: 0; letter-spacing: 0.02em; flex: 1;
    color: rgba(var(--v-theme-on-surface), 0.95);
}
.section-count {
    font-size: 0.7rem; padding: 2px 8px;
    border-radius: 999px; font-weight: 600;
    background: rgba(var(--v-theme-primary), 0.12);
    color: rgb(var(--v-theme-primary));
}

/* Info grid */
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 8px;
}
.info-tile {
    padding: 10px 12px;
    border-radius: 10px;
    background: rgb(var(--v-theme-surface));
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    border-left: 3px solid rgba(var(--v-theme-primary), 0.5);
}
.info-label {
    font-size: 0.65rem;
    color: rgba(var(--v-theme-on-surface), 0.55);
    text-transform: uppercase;
    letter-spacing: 0.04em;
    font-weight: 600;
}
.info-value {
    font-size: 0.85rem; font-weight: 600;
    color: rgba(var(--v-theme-on-surface), 0.95);
    margin-top: 2px; word-break: break-word;
}

/* Note block */
.note-block {
    padding: 12px 14px;
    border-radius: 10px;
    background: rgba(var(--v-theme-primary), 0.04);
    border-left: 3px solid rgba(var(--v-theme-primary), 0.5);
    font-size: 0.85rem;
    line-height: 1.5;
    white-space: pre-wrap;
    word-break: break-word;
}

/* Stock list */
.stock-list { display: flex; flex-direction: column; gap: 8px; }
.stock-card {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 14px;
    border-radius: 12px;
    background: rgb(var(--v-theme-surface));
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    transition: border-color 0.15s, box-shadow 0.15s;
}
.stock-card:hover {
    border-color: rgba(var(--v-theme-primary), 0.4);
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
.stock-index {
    width: 26px; height: 26px; flex-shrink: 0;
    border-radius: 8px;
    background: rgba(var(--v-theme-primary), 0.12);
    color: rgb(var(--v-theme-primary));
    font-size: 0.75rem; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
}
.stock-body { flex: 1; min-width: 0; }
.stock-warehouse {
    display: flex; align-items: center; gap: 4px;
    font-size: 0.875rem; font-weight: 600;
}
.stock-warehouse-icon {
    width: 14px; height: 14px;
    color: rgb(var(--v-theme-primary));
}
.stock-sub {
    display: flex; gap: 10px; flex-wrap: wrap;
    margin-top: 2px;
    font-size: 0.72rem;
    color: rgba(var(--v-theme-on-surface), 0.65);
}
.stock-qty {
    text-align: right; flex-shrink: 0;
    padding-left: 10px;
    border-left: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
.stock-qty-line {
    display: flex; align-items: baseline; justify-content: flex-end; gap: 4px;
}
.stock-qty-value {
    font-size: 1rem; font-weight: 700;
    color: rgb(var(--v-theme-primary));
}
.stock-qty-unit {
    font-size: 0.7rem;
    color: rgba(var(--v-theme-on-surface), 0.6);
}

/* Transfer list */
.transfer-list { display: flex; flex-direction: column; gap: 8px; }
.transfer-card {
    padding: 12px 14px;
    border-radius: 12px;
    background: rgb(var(--v-theme-surface));
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    display: flex; flex-direction: column; gap: 6px;
}
.transfer-head {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 6px;
}
.transfer-number { font-size: 0.85rem; font-weight: 700; }
.transfer-date {
    font-size: 0.7rem;
    color: rgba(var(--v-theme-on-surface), 0.55);
    margin-top: 1px;
}
.transfer-route {
    display: flex; align-items: center; gap: 6px;
    flex-wrap: wrap;
    font-size: 0.78rem;
    padding: 6px 8px;
    border-radius: 8px;
    background: rgba(var(--v-theme-on-surface), 0.03);
}
.route-from { color: #EF4444; font-weight: 500; }
.route-to   { color: #10B981; font-weight: 500; }
.route-arrow { width: 14px; height: 14px; color: rgba(var(--v-theme-on-surface), 0.4); }
.transfer-qty {
    font-size: 0.76rem;
    display: flex; gap: 6px;
}
.transfer-qty-label { color: rgba(var(--v-theme-on-surface), 0.6); }
.transfer-qty-value { font-weight: 700; }

/* Empty */
.empty-state {
    padding: 28px 12px;
    text-align: center;
    border-radius: 12px;
    background: rgb(var(--v-theme-surface));
    border: 1px dashed rgba(var(--v-border-color), var(--v-border-opacity));
}
.empty-icon { width: 36px; height: 36px; color: rgba(var(--v-theme-on-surface), 0.3); }
.empty-text {
    font-size: 0.8rem;
    color: rgba(var(--v-theme-on-surface), 0.55);
    margin-top: 6px;
}

/* Audit */
.audit-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 8px;
}
.audit-row {
    padding: 10px 12px;
    border-radius: 10px;
    background: rgb(var(--v-theme-surface));
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
.audit-label {
    font-size: 0.65rem;
    color: rgba(var(--v-theme-on-surface), 0.55);
    text-transform: uppercase;
    letter-spacing: 0.04em;
    font-weight: 600;
}
.audit-value {
    font-size: 0.8rem; font-weight: 500;
    margin-top: 2px;
    word-break: break-word;
}

.text-muted { color: rgba(var(--v-theme-on-surface), 0.45); }

/* Responsive */
@media (max-width: 600px) {
    .hero { padding: 16px 16px 18px; }
    .hero-title { font-size: 1.15rem; }
    .stock-card { flex-wrap: wrap; }
    .stock-qty {
        border-left: 0;
        border-top: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
        padding-left: 0; padding-top: 8px; margin-top: 4px;
        width: 100%; text-align: left;
    }
    .stock-qty-line { justify-content: flex-start; }
}
</style>
