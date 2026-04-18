<script lang="ts" setup>
/**
 * BaseSupplierPicker
 * ------------------
 * Readonly textfield + tombol titik-tiga yang membuka dialog pemilih pemasok.
 * Pemasok diurutkan berdasarkan frekuensi pembelian untuk item terpilih
 * (paling sering → paling jarang), disertai harga terakhir, badge
 * "berlangganan" (≥ ambang batas), dan penanda "terakhir digunakan".
 *
 * Emits:
 *  - update:modelValue (supplier_uid | null)
 *  - select (SupplierStat | null)   ← parent bisa auto-isi harga, dsb
 */
import { computed, ref } from 'vue'
import { useTranslate } from '@/composables/useTranslate'
import { formatRupiah } from '@/utils/currency'
// Icons
import MdiDotsVertical from '~icons/mdi/dots-vertical'
import MdiMagnify from '~icons/mdi/magnify'
import MdiClose from '~icons/mdi/close'
import MdiCheck from '~icons/mdi/check'
import MdiStarCircle from '~icons/mdi/star-circle'
import MdiClockOutline from '~icons/mdi/clock-outline'
import MdiStorefrontOutline from '~icons/mdi/storefront-outline'

// ─── Public types ────────────────────────────────────────────────────────────
export interface SupplierOption {
    uid: string
    name: string
}

export interface PurchaseHistoryEntry {
    supplier_uid: string
    supplier_name?: string | null
    item_uid: string
    price: number
    date: string | Date | null | undefined
}

export interface SupplierStat {
    uid: string
    name: string
    count: number
    lastPrice: number | null
    lastUsedAt: Date | null
    isSubscribed: boolean
    isMostRecent: boolean
}

// ─── Constants ───────────────────────────────────────────────────────────────
const DEFAULT_SUBSCRIBED_THRESHOLD = 3
const LOCALE = 'id-ID'

// ─── Props / Emits ───────────────────────────────────────────────────────────
interface Props {
    modelValue: string | null | undefined
    itemUid?: string | null
    label?: string
    placeholder?: string
    errorMessages?: string | string[]
    disabled?: boolean
    suppliers?: SupplierOption[]
    history?: PurchaseHistoryEntry[]
    /** Minimal jumlah pembelian untuk ditandai "berlangganan". */
    subscribedThreshold?: number
}

const props = withDefaults(defineProps<Props>(), {
    suppliers: () => [],
    history: () => [],
    subscribedThreshold: DEFAULT_SUBSCRIBED_THRESHOLD,
    disabled: false,
})

const emit = defineEmits<{
    'update:modelValue': [value: string | null]
    'select': [option: SupplierStat | null]
}>()

const t = useTranslate()

// ─── Helpers ─────────────────────────────────────────────────────────────────
const toDate = (value: unknown): Date | null => {
    if (!value) return null
    const d = value instanceof Date ? value : new Date(value as string)
    return isNaN(d.getTime()) ? null : d
}

const formatShortDate = (d: Date | null): string => {
    if (!d) return '-'
    try {
        return d.toLocaleDateString(LOCALE, { day: '2-digit', month: 'short', year: 'numeric' })
    } catch {
        return d.toISOString().split('T')[0]
    }
}

// ─── Stats (agregasi history per supplier untuk item terpilih) ──────────────
const stats = computed<SupplierStat[]>(() => {
    const byUid = new Map<string, SupplierStat>()

    // Seed daftar master supaya semua pemasok tetap tampil walau belum ada history
    for (const s of props.suppliers) {
        if (!s?.uid) continue
        byUid.set(s.uid, {
            uid: s.uid,
            name: s.name || s.uid,
            count: 0,
            lastPrice: null,
            lastUsedAt: null,
            isSubscribed: false,
            isMostRecent: false,
        })
    }

    // Agregasi history untuk item yang sedang dipilih
    const itemUid = props.itemUid
    for (const h of props.history) {
        if (!h?.supplier_uid) continue
        if (itemUid && h.item_uid !== itemUid) continue

        const key = h.supplier_uid
        const entry = byUid.get(key) ?? {
            uid: key,
            name: h.supplier_name || key,
            count: 0,
            lastPrice: null,
            lastUsedAt: null,
            isSubscribed: false,
            isMostRecent: false,
        }

        entry.count += 1
        const d = toDate(h.date)
        const price = Number.isFinite(h.price) ? Number(h.price) : null

        if (d && (!entry.lastUsedAt || d > entry.lastUsedAt)) {
            entry.lastUsedAt = d
            if (price !== null) entry.lastPrice = price
        } else if (entry.lastPrice === null && price !== null) {
            entry.lastPrice = price
        }

        byUid.set(key, entry)
    }

    const list = [...byUid.values()]

    // Tandai "berlangganan"
    const threshold = Math.max(1, props.subscribedThreshold)
    for (const s of list) {
        s.isSubscribed = s.count >= threshold
    }

    // Tandai pemasok terakhir dipakai (paling baru secara keseluruhan)
    let mostRecent: SupplierStat | null = null
    for (const s of list) {
        if (!s.lastUsedAt) continue
        if (!mostRecent || s.lastUsedAt > (mostRecent.lastUsedAt as Date)) {
            mostRecent = s
        }
    }
    if (mostRecent) mostRecent.isMostRecent = true

    // Urutkan: count desc → lastUsedAt desc → name asc
    list.sort((a, b) => {
        if (b.count !== a.count) return b.count - a.count
        const at = a.lastUsedAt?.getTime() ?? 0
        const bt = b.lastUsedAt?.getTime() ?? 0
        if (bt !== at) return bt - at
        return a.name.localeCompare(b.name)
    })

    return list
})

// ─── Search ──────────────────────────────────────────────────────────────────
const search = ref('')

const filtered = computed<SupplierStat[]>(() => {
    const q = search.value.trim().toLowerCase()
    if (!q) return stats.value
    return stats.value.filter(s => s.name.toLowerCase().includes(q))
})

// ─── Display ─────────────────────────────────────────────────────────────────
const selectedStat = computed<SupplierStat | null>(() => {
    if (!props.modelValue) return null
    return stats.value.find(s => s.uid === props.modelValue) ?? null
})

const displayText = computed(() => selectedStat.value?.name ?? '')

// ─── Dialog ──────────────────────────────────────────────────────────────────
const dialog = ref(false)

const openDialog = () => {
    if (props.disabled) return
    search.value = ''
    dialog.value = true
}

const closeDialog = () => {
    dialog.value = false
}

const pick = (opt: SupplierStat) => {
    emit('update:modelValue', opt.uid)
    emit('select', opt)
    closeDialog()
}

const clear = () => {
    emit('update:modelValue', null)
    emit('select', null)
}
</script>

<template>
    <v-text-field
        :model-value="displayText"
        :label="label"
        :placeholder="placeholder"
        :error-messages="errorMessages"
        :disabled="disabled"
        variant="outlined"
        density="comfortable"
        readonly
        hide-details="auto"
        @click="openDialog"
    >
        <template v-slot:append-inner>
            <button
                v-if="modelValue && !disabled"
                type="button"
                class="picker-trigger picker-trigger--sm"
                :aria-label="t('clear') || 'Clear'"
                @click.stop="clear"
            >
                <component :is="MdiClose" class="picker-icon-sm" />
            </button>
            <button
                type="button"
                class="picker-trigger"
                :disabled="disabled"
                :aria-label="t('selectSupplier')"
                @click.stop="openDialog"
            >
                <component :is="MdiDotsVertical" class="picker-icon" />
            </button>
        </template>
    </v-text-field>

    <!-- Dialog -->
    <v-dialog v-model="dialog" max-width="820" scrollable>
        <v-card class="rounded-lg d-flex flex-column" style="max-height: 86vh;">
            <!-- Header -->
            <div class="picker-header">
                <div class="picker-header-bg" />
                <div class="picker-header-inner">
                    <div class="d-flex align-center ga-3">
                        <div class="picker-badge">
                            <component :is="MdiStorefrontOutline" class="picker-badge-icon" />
                        </div>
                        <div class="picker-head-text">
                            <div class="picker-title">{{ t('selectSupplier') }}</div>
                            <div class="picker-subtitle">
                                {{ stats.length }} {{ t('suppliers') }}
                            </div>
                        </div>
                        <v-spacer />
                        <v-btn
                            icon
                            variant="text"
                            color="white"
                            density="comfortable"
                            :aria-label="t('close')"
                            @click="closeDialog"
                        >
                            <component :is="MdiClose" class="picker-icon" />
                        </v-btn>
                    </div>
                </div>
            </div>

            <!-- Search -->
            <div class="px-4 pt-3 pb-2">
                <v-text-field
                    v-model="search"
                    :placeholder="`${t('search')}...`"
                    variant="outlined"
                    density="comfortable"
                    hide-details
                    clearable
                    autofocus
                >
                    <template v-slot:prepend-inner>
                        <component :is="MdiMagnify" class="picker-icon text-medium-emphasis" />
                    </template>
                </v-text-field>
            </div>

            <!-- List -->
            <v-card-text class="px-3 pb-3 pt-1 grow overflow-y-auto">
                <div v-if="filtered.length" class="d-flex flex-column ga-2">
                    <div
                        v-for="opt in filtered"
                        :key="opt.uid"
                        class="supplier-row"
                        :class="{ 'supplier-row--selected': opt.uid === modelValue }"
                        role="button"
                        tabindex="0"
                        @keyup.enter="pick(opt)"
                    >
                        <!-- Rank / star -->
                        <div class="supplier-rank" aria-hidden="true">
                            <component v-if="opt.isSubscribed" :is="MdiStarCircle" class="rank-icon rank-icon--sub" />
                            <span v-else class="rank-count">{{ opt.count }}</span>
                        </div>

                        <!-- Info -->
                        <div class="supplier-info">
                            <div class="d-flex align-center ga-2 flex-wrap">
                                <span class="supplier-name">{{ opt.name }}</span>
                                <v-chip
                                    v-if="opt.isSubscribed"
                                    size="x-small"
                                    color="amber-darken-2"
                                    variant="tonal"
                                    class="font-weight-bold"
                                >
                                    <component :is="MdiStarCircle" class="chip-icon me-1" />
                                    {{ t('subscribed') }}
                                </v-chip>
                                <v-chip
                                    v-if="opt.isMostRecent"
                                    size="x-small"
                                    color="primary"
                                    variant="tonal"
                                >
                                    <component :is="MdiClockOutline" class="chip-icon me-1" />
                                    {{ t('lastUsed') }}
                                </v-chip>
                            </div>
                            <div class="supplier-meta">
                                <span class="meta-item">
                                    <span class="meta-label">{{ t('lastPrice') }}:</span>
                                    <span class="meta-value">{{ opt.lastPrice !== null ? formatRupiah(opt.lastPrice) : '-' }}</span>
                                </span>
                                <span class="meta-sep">•</span>
                                <span class="meta-item">
                                    <span class="meta-label">{{ t('totalQty') }}:</span>
                                    <span class="meta-value">{{ opt.count }}×</span>
                                </span>
                                <span class="meta-sep">•</span>
                                <span class="meta-item">
                                    <span class="meta-label">{{ t('lastUsed') }}:</span>
                                    <span class="meta-value">{{ formatShortDate(opt.lastUsedAt) }}</span>
                                </span>
                            </div>
                        </div>

                        <!-- Action -->
                        <div class="supplier-action">
                            <v-btn
                                :color="opt.uid === modelValue ? 'success' : 'primary'"
                                :variant="opt.uid === modelValue ? 'flat' : 'tonal'"
                                size="small"
                                @click.stop="pick(opt)"
                            >
                                <component v-if="opt.uid === modelValue" :is="MdiCheck" class="me-1 picker-icon-sm" />
                                {{ t('select') }}
                            </v-btn>
                        </div>
                    </div>
                </div>

                <div v-else class="empty-state">
                    <component :is="MdiStorefrontOutline" class="empty-icon" />
                    <div class="empty-text">{{ t('noSupplierHistory') }}</div>
                </div>
            </v-card-text>

            <v-divider />
            <v-card-actions class="px-4 py-3">
                <v-spacer />
                <v-btn variant="tonal" size="small" @click="closeDialog">
                    {{ t('close') }}
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<style scoped>
.picker-icon {
    width: 18px;
    height: 18px;
}
.picker-icon-sm {
    width: 14px;
    height: 14px;
}

/* Trigger button — menyatu dengan input, hanya icon yang terlihat */
.picker-trigger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    margin-inline-start: 2px;
    padding: 0;
    border: 0;
    background: transparent;
    color: rgba(var(--v-theme-on-surface), 0.65);
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.15s, color 0.15s;
}
.picker-trigger:hover:not(:disabled) {
    background: rgba(var(--v-theme-on-surface), 0.06);
    color: rgb(var(--v-theme-primary));
}
.picker-trigger:focus-visible {
    outline: 2px solid rgba(var(--v-theme-primary), 0.4);
    outline-offset: 2px;
}
.picker-trigger:disabled {
    cursor: not-allowed;
    opacity: 0.45;
}
.picker-trigger--sm {
    width: 22px;
    height: 22px;
}

/* Header */
.picker-header {
    position: relative;
    overflow: hidden;
    color: #fff;
}
.picker-header-bg {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgb(var(--v-theme-primary)) 0%, rgba(var(--v-theme-primary), 0.78) 100%);
}
.picker-header-bg::before {
    content: '';
    position: absolute;
    width: 180px;
    height: 180px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.08);
    top: -60px;
    right: -40px;
}
.picker-header-inner {
    position: relative;
    padding: 14px 16px;
}
.picker-head-text {
    min-width: 0;
}
.picker-badge {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.picker-badge-icon {
    width: 22px;
    height: 22px;
    color: #fff;
}
.picker-title {
    font-size: 1rem;
    font-weight: 700;
    line-height: 1.2;
}
.picker-subtitle {
    font-size: 0.72rem;
    opacity: 0.85;
    margin-top: 2px;
}

/* Rows */
.supplier-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 12px;
    background: rgb(var(--v-theme-surface));
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
    outline: none;
}
.supplier-row:hover,
.supplier-row:focus-visible {
    border-color: rgba(var(--v-theme-primary), 0.4);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}
.supplier-row--selected {
    border-color: rgb(var(--v-theme-success));
    background: rgba(var(--v-theme-success), 0.06);
}

.supplier-rank {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(var(--v-theme-primary), 0.1);
    color: rgb(var(--v-theme-primary));
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    flex-shrink: 0;
}
.rank-count {
    font-size: 0.85rem;
}
.rank-icon {
    width: 22px;
    height: 22px;
}
.rank-icon--sub {
    color: #F59E0B;
}

.supplier-info {
    flex: 1;
    min-width: 0;
}
.supplier-name {
    font-size: 0.9rem;
    font-weight: 600;
    line-height: 1.3;
    word-break: break-word;
}
.chip-icon {
    width: 12px;
    height: 12px;
}
.supplier-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
    margin-top: 6px;
    font-size: 0.72rem;
    color: rgba(var(--v-theme-on-surface), 0.7);
}
.meta-label {
    opacity: 0.7;
    margin-right: 4px;
}
.meta-value {
    font-weight: 600;
    color: rgba(var(--v-theme-on-surface), 0.95);
}
.meta-sep {
    opacity: 0.35;
}

.supplier-action {
    flex-shrink: 0;
}

.empty-state {
    padding: 36px 12px;
    text-align: center;
}
.empty-icon {
    width: 40px;
    height: 40px;
    color: rgba(var(--v-theme-on-surface), 0.3);
}
.empty-text {
    font-size: 0.85rem;
    color: rgba(var(--v-theme-on-surface), 0.6);
    margin-top: 8px;
}
</style>
