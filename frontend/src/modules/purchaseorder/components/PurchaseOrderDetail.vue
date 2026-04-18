<script lang="ts" setup>
import { computed } from 'vue'
// Composables
import { useTranslate } from '@/composables/useTranslate'
// Utils
import { formatDate } from '@/utils/date'
import { formatRupiah } from '@/utils/currency'
// Icons
import SystemUiconsClose from '~icons/system-uicons/close'
import MingcuteTimeLine from '~icons/mingcute/time-line'
import UisCheck from '~icons/uis/check'
import CodexCross from '~icons/codex/cross'
import CiCheckAll from '~icons/ci/check-all'
import FluentNotepad24Filled from '~icons/fluent/notepad-24-filled'
import MaterialSymbolsLightDraftRounded from '~icons/material-symbols-light/draft-rounded'
import MdiCartOutline from '~icons/mdi/cart-outline'
import MdiCalendarOutline from '~icons/mdi/calendar-outline'
import MdiFileDocumentOutline from '~icons/mdi/file-document-outline'
import MdiBriefcaseOutline from '~icons/mdi/briefcase-outline'
import MdiIdentifier from '~icons/mdi/identifier'
import MdiCashMultiple from '~icons/mdi/cash-multiple'
import MdiPackageVariantClosed from '~icons/mdi/package-variant-closed'
import MdiCubeOutline from '~icons/mdi/cube-outline'
import MdiAccountOutline from '~icons/mdi/account-outline'
import MdiClockOutline from '~icons/mdi/clock-outline'
import MdiPencilOutline from '~icons/mdi/pencil-outline'
import MdiUpdate from '~icons/mdi/update'
import MdiHistory from '~icons/mdi/history'
import { useThemeStore } from '@/stores/theme'

const model = defineModel<boolean>()

const props = defineProps<{
    items: PurchaseOrderList
}>()

const emit = defineEmits<{
    (e: 'close'): void
}>()

const t = useTranslate()
const themeStore = useThemeStore()

const heroColor = computed(() => themeStore.resolvedTheme === 'dark' ? '#ffffff' : '#000000')
const handleClose = () => emit('close')

// =========================
// Status meta
// =========================
type StatusMeta = { color: string; bg: string; icon: any }

const STATUS_MAP: Record<string, StatusMeta> = {
    'waiting approval': { color: '#F59E0B', bg: 'rgba(245,158,11,0.18)', icon: MingcuteTimeLine },
    'approved':         { color: '#10B981', bg: 'rgba(16,185,129,0.18)', icon: UisCheck },
    'rejected':         { color: '#EF4444', bg: 'rgba(239,68,68,0.18)',  icon: CodexCross },
    'revised':          { color: '#F97316', bg: 'rgba(249,115,22,0.18)', icon: FluentNotepad24Filled },
    'completed':        { color: '#3B82F6', bg: 'rgba(59,130,246,0.18)', icon: CiCheckAll },
}

const statusMeta = computed<StatusMeta>(() => {
    const key = (props.items?.status ?? '').toLowerCase()
    return STATUS_MAP[key] ?? { color: '#9CA3AF', bg: 'rgba(156,163,175,0.18)', icon: MaterialSymbolsLightDraftRounded }
})

// =========================
// Derived
// =========================
const details = computed(() => (props.items?.details ?? []) as any[])
const totalItems = computed(() => details.value.length)
const totalQty = computed(() =>
    details.value.reduce((sum, d) => sum + (Number(d?.qty) || 0), 0)
)
const totalAmount = computed(() => Number(props.items?.total_amount) || 0)

const generalRows = computed(() => [
    { icon: MdiFileDocumentOutline, label: t('itemRequest'), value: (props.items?.item_request as any)?.request_number },
    { icon: MdiCalendarOutline,     label: t('poDate'),      value: formatDate(props.items?.po_date) },
    { icon: MdiBriefcaseOutline,    label: t('projectName'), value: (props.items as any)?.project_name || (props.items?.item_request as any)?.project_name },
    { icon: MdiIdentifier,          label: t('woNumber'),    value: (props.items as any)?.wo_number || (props.items?.item_request as any)?.wo_number },
])

const auditRows = computed(() => [
    { icon: MdiAccountOutline, label: t('createdBy'), value: props.items?.created_by_name },
    { icon: MdiClockOutline,   label: t('createdAt'), value: formatDate(props.items?.created_at) },
    { icon: MdiPencilOutline,  label: t('updatedBy'), value: props.items?.updated_by_name },
    { icon: MdiUpdate,         label: t('updatedAt'), value: formatDate(props.items?.updated_at) },
])

const approvalLogs = computed(() => (props.items?.approval_logs ?? []) as any[])

const formatQty = (n: number | null | undefined) =>
    Number(n ?? 0).toLocaleString('id-ID')
</script>

<template>
    <v-overlay
        v-model="model"
        class="custom-overlay"
        persistent
        @click:outside="handleClose"
    />

    <v-navigation-drawer
        v-model="model"
        class="custom detail-drawer"
        location="right"
        width="560"
        temporary
        :scrim="false"
    >
        <!-- ============ Hero ============ -->
        <div class="hero" :style="{ color: heroColor }">
            <div class="hero-content">
                <div class="d-flex justify-space-between align-start mb-4">
                    <div class="hero-badge">
                        <component :is="MdiCartOutline" class="hero-badge-icon" :style="{ color: heroColor }" />
                    </div>
                    <v-btn
                        icon
                        density="comfortable"
                        variant="text"
                        color="white"
                        :aria-label="t('close')"
                        @click="handleClose"
                    >
                        <component :is="SystemUiconsClose" class="close-icon" :style="{ color: heroColor }" />
                    </v-btn>
                </div>
                <div class="hero-label">{{ t('poNumber') }}</div>
                <div class="hero-title">{{ items?.po_number || '-' }}</div>
                <div class="hero-meta">
                    <component :is="MdiCalendarOutline" class="hero-meta-icon" />
                    <span>{{ formatDate(items?.po_date) || '-' }}</span>
                    <span class="hero-meta-dot">•</span>
                    <component :is="MdiFileDocumentOutline" class="hero-meta-icon" />
                    <span>{{ (items?.item_request as any)?.request_number || '-' }}</span>
                </div>
                <div
                    class="status-pill mt-3"
                    :style="{ background: statusMeta.bg, color: statusMeta.color }"
                >
                    <component :is="statusMeta.icon" class="status-pill-icon" />
                    <span class="text-capitalize">{{ (items?.status || '-').toLowerCase() }}</span>
                </div>
            </div>
        </div>

        <!-- ============ Content ============ -->
        <div v-if="items" class="content-wrap">
            <!-- Stats -->
            <div class="stat-row">
                <div class="stat-tile">
                    <component :is="MdiCubeOutline" class="stat-icon" />
                    <div>
                        <div class="stat-value">{{ totalItems }}</div>
                        <div class="stat-label">{{ t('totalItems') }}</div>
                    </div>
                </div>
                <div class="stat-tile">
                    <component :is="MdiPackageVariantClosed" class="stat-icon" />
                    <div>
                        <div class="stat-value">{{ formatQty(totalQty) }}</div>
                        <div class="stat-label">{{ t('totalQty') }}</div>
                    </div>
                </div>
                <div class="stat-tile stat-tile--accent">
                    <component :is="MdiCashMultiple" class="stat-icon stat-icon--accent" />
                    <div>
                        <div class="stat-value">{{ formatRupiah(totalAmount) }}</div>
                        <div class="stat-label">{{ t('totalAmount') }}</div>
                    </div>
                </div>
            </div>

            <!-- General Info -->
            <section class="section">
                <div class="section-head">
                    <span class="section-accent" />
                    <h3 class="section-title">{{ t('generalInfo') }}</h3>
                </div>
                <div class="info-list">
                    <div v-for="row in generalRows" :key="row.label" class="info-row">
                        <div class="info-row-icon">
                            <component :is="row.icon" />
                        </div>
                        <div class="info-row-body">
                            <div class="info-row-label">{{ row.label }}</div>
                            <div class="info-row-value">{{ row.value || '-' }}</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Items -->
            <section class="section">
                <div class="section-head">
                    <span class="section-accent" />
                    <h3 class="section-title">{{ t('details') }}</h3>
                    <span class="section-count">{{ totalItems }}</span>
                </div>
                <div v-if="details.length" class="items-list">
                    <div v-for="(d, i) in details" :key="i" class="item-card">
                        <div class="item-index">{{ i + 1 }}</div>
                        <div class="item-body">
                            <div class="item-name">{{ d.item?.name || '-' }}</div>
                            <div v-if="d.item?.code" class="item-code">{{ d.item.code }}</div>
                            <div class="item-supplier" v-if="d.supplier?.name">
                                <component :is="MdiAccountOutline" class="item-supplier-icon" />
                                {{ d.supplier.name }}
                            </div>
                        </div>
                        <div class="item-meta">
                            <div class="item-qty">
                                <span class="item-qty-value">{{ formatQty(d.qty) }}</span>
                                <span class="item-qty-unit">{{ d.unit?.symbol || '-' }}</span>
                            </div>
                            <div class="item-price">{{ formatRupiah(d.price) }}</div>
                            <div class="item-total">{{ formatRupiah(d.total) }}</div>
                        </div>
                    </div>
                </div>
                <div v-else class="empty-state">
                    <component :is="MdiCubeOutline" class="empty-icon" />
                    <div class="empty-text">{{ t('noData') }}</div>
                </div>
            </section>

            <!-- Approval logs -->
            <section v-if="approvalLogs.length" class="section">
                <div class="section-head">
                    <span class="section-accent" />
                    <h3 class="section-title">{{ t('approvalHistory') || 'Riwayat Approval' }}</h3>
                    <component :is="MdiHistory" class="section-aux-icon" />
                </div>
                <div class="timeline">
                    <div
                        v-for="log in approvalLogs"
                        :key="log.uid"
                        class="timeline-item"
                        :class="`timeline-item--${(log.status || '').toLowerCase()}`"
                    >
                        <div class="timeline-dot" />
                        <div class="timeline-card">
                            <div class="d-flex align-center justify-space-between flex-wrap ga-2">
                                <div>
                                    <div class="timeline-title">
                                        Level {{ log.approval_level }} — {{ log.role_name }}
                                    </div>
                                    <div class="timeline-meta">
                                        {{ log.approved_by_name || '-' }} · {{ formatDate(log.created_at) }}
                                    </div>
                                </div>
                                <span
                                    class="timeline-chip"
                                    :class="log.status === 'Approved' ? 'timeline-chip--ok' : 'timeline-chip--bad'"
                                >
                                    {{ log.status }}
                                </span>
                            </div>
                            <div v-if="log.notes" class="timeline-notes">"{{ log.notes }}"</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Audit -->
            <section class="section">
                <div class="section-head">
                    <span class="section-accent" />
                    <h3 class="section-title">{{ t('information', { field: '' }) }}</h3>
                </div>
                <div class="audit-grid">
                    <div v-for="row in auditRows" :key="row.label" class="audit-row">
                        <component :is="row.icon" class="audit-icon" />
                        <div class="audit-body">
                            <div class="audit-label">{{ row.label }}</div>
                            <div class="audit-value">{{ row.value || '-' }}</div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- ============ Footer ============ -->
        <template v-slot:append>
            <v-divider />
            <div class="d-flex justify-end pa-3">
                <v-btn variant="tonal" color="primary" size="small" @click="handleClose">
                    {{ t('close') }}
                </v-btn>
            </div>
        </template>
    </v-navigation-drawer>
</template>

<style scoped>
.custom { z-index: 1007 !important; }
.custom-overlay { z-index: 1006 !important; }

/* Hero */
.hero {
    position: relative;
    overflow: hidden;
    padding: 20px 24px 22px;
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
.hero-bg::before { width: 180px; height: 180px; top: -60px; right: -40px; }
.hero-bg::after  { width: 120px; height: 120px; bottom: -40px; left: -30px; }
.hero-content { position: relative; }
.hero-badge {
    width: 44px; height: 44px;
    border-radius: 12px;
    background: rgba(180,180,180,0.18);
    display: flex; align-items: center; justify-content: center;
}
.hero-badge-icon { width: 22px; height: 22px; color: #fff; }
.close-icon { width: 18px; height: 18px; }
.hero-label {
    font-size: 0.7rem; letter-spacing: 0.08em;
    text-transform: uppercase; opacity: 0.8; font-weight: 500;
}
.hero-title {
    font-size: 1.35rem; font-weight: 700;
    line-height: 1.3; margin-top: 2px; letter-spacing: -0.01em;
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
    font-size: 0.75rem; font-weight: 600;
}
.status-pill-icon { width: 14px; height: 14px; }

/* Content */
.content-wrap {
    padding: 18px 20px 20px;
    display: flex; flex-direction: column; gap: 20px;
}

/* Stats */
.stat-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}
.stat-tile {
    grid-column: span 1;
    display: flex; align-items: center; gap: 10px;
    padding: 12px 14px;
    border-radius: 12px;
    background: rgb(var(--v-theme-surface));
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
.stat-tile--accent {
    grid-column: span 2;
    background: linear-gradient(135deg, rgba(var(--v-theme-primary), 0.08), rgba(var(--v-theme-primary), 0.02));
    border-color: rgba(var(--v-theme-primary), 0.25);
}
.stat-icon {
    width: 28px; height: 28px;
    padding: 6px;
    border-radius: 8px;
    background: rgba(var(--v-theme-primary), 0.12);
    color: rgb(var(--v-theme-primary));
    box-sizing: content-box;
}
.stat-icon--accent {
    background: rgb(var(--v-theme-primary));
    color: #fff;
}
.stat-value { font-size: 1.1rem; font-weight: 700; line-height: 1.1; }
.stat-label {
    font-size: 0.7rem;
    color: rgba(var(--v-theme-on-surface), 0.6);
    margin-top: 2px;
}

/* Sections */
.section { display: flex; flex-direction: column; gap: 10px; }
.section-head { display: flex; align-items: center; gap: 8px; }
.section-accent {
    width: 3px; height: 16px;
    background: rgb(var(--v-theme-primary));
    border-radius: 2px;
}
.section-title {
    font-size: 0.85rem; font-weight: 700;
    letter-spacing: 0.02em; margin: 0; flex: 1;
}
.section-count {
    font-size: 0.7rem; padding: 2px 8px;
    border-radius: 999px; font-weight: 600;
    background: rgba(var(--v-theme-primary), 0.12);
    color: rgb(var(--v-theme-primary));
}
.section-aux-icon {
    width: 16px; height: 16px;
    color: rgba(var(--v-theme-on-surface), 0.5);
}

/* Info list */
.info-list {
    background: rgb(var(--v-theme-surface));
    border-radius: 12px;
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    overflow: hidden;
}
.info-row {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 14px; transition: background 0.15s;
}
.info-row:hover { background: rgba(var(--v-theme-primary), 0.04); }
.info-row + .info-row {
    border-top: 1px solid rgba(var(--v-border-color), calc(var(--v-border-opacity) * 0.7));
}
.info-row-icon {
    width: 32px; height: 32px;
    border-radius: 8px;
    background: rgba(var(--v-theme-primary), 0.08);
    color: rgb(var(--v-theme-primary));
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.info-row-icon > * { width: 16px; height: 16px; }
.info-row-body { flex: 1; min-width: 0; }
.info-row-label {
    font-size: 0.7rem;
    color: rgba(var(--v-theme-on-surface), 0.6);
    font-weight: 500;
}
.info-row-value {
    font-size: 0.85rem; font-weight: 500;
    word-break: break-word; margin-top: 1px;
}

/* Items */
.items-list { display: flex; flex-direction: column; gap: 8px; }
.item-card {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 14px;
    border-radius: 12px;
    background: rgb(var(--v-theme-surface));
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    transition: border-color 0.15s, box-shadow 0.15s;
}
.item-card:hover {
    border-color: rgba(var(--v-theme-primary), 0.4);
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
.item-index {
    width: 26px; height: 26px;
    border-radius: 8px;
    background: rgba(var(--v-theme-primary), 0.12);
    color: rgb(var(--v-theme-primary));
    font-size: 0.75rem; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.item-body { flex: 1; min-width: 0; }
.item-name {
    font-size: 0.875rem; font-weight: 600; line-height: 1.25;
}
.item-code {
    font-size: 0.7rem; margin-top: 2px;
    color: rgba(var(--v-theme-on-surface), 0.55);
    font-family: ui-monospace, monospace;
}
.item-supplier {
    font-size: 0.7rem; margin-top: 4px;
    color: rgba(var(--v-theme-on-surface), 0.7);
    display: inline-flex; align-items: center; gap: 4px;
}
.item-supplier-icon { width: 12px; height: 12px; }

.item-meta {
    text-align: right; flex-shrink: 0;
    padding-left: 10px;
    border-left: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
.item-qty {
    display: flex; align-items: baseline; justify-content: flex-end; gap: 4px;
}
.item-qty-value {
    font-size: 1rem; font-weight: 700;
    color: rgb(var(--v-theme-primary));
}
.item-qty-unit {
    font-size: 0.7rem;
    color: rgba(var(--v-theme-on-surface), 0.6);
}
.item-price {
    font-size: 0.7rem; margin-top: 2px;
    color: rgba(var(--v-theme-on-surface), 0.6);
}
.item-total {
    font-size: 0.78rem; font-weight: 700;
    margin-top: 4px;
    color: rgba(var(--v-theme-on-surface), 0.95);
}

.empty-state {
    padding: 32px 12px; text-align: center;
    background: rgb(var(--v-theme-surface));
    border-radius: 12px;
    border: 1px dashed rgba(var(--v-border-color), var(--v-border-opacity));
}
.empty-icon { width: 36px; height: 36px; color: rgba(var(--v-theme-on-surface), 0.3); }
.empty-text {
    font-size: 0.8rem; margin-top: 6px;
    color: rgba(var(--v-theme-on-surface), 0.55);
}

/* Timeline */
.timeline {
    position: relative;
    padding-left: 16px;
}
.timeline::before {
    content: '';
    position: absolute;
    left: 6px; top: 4px; bottom: 4px;
    width: 2px;
    background: rgba(var(--v-border-color), var(--v-border-opacity));
    border-radius: 1px;
}
.timeline-item {
    position: relative;
    padding-bottom: 10px;
}
.timeline-item:last-child { padding-bottom: 0; }
.timeline-dot {
    position: absolute;
    left: -16px; top: 12px;
    width: 14px; height: 14px;
    border-radius: 50%;
    border: 2px solid rgb(var(--v-theme-surface));
    background: rgba(var(--v-theme-on-surface), 0.4);
    z-index: 1;
}
.timeline-item--approved .timeline-dot { background: #10B981; }
.timeline-item--rejected .timeline-dot { background: #EF4444; }
.timeline-card {
    margin-left: 8px;
    background: rgb(var(--v-theme-surface));
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    border-radius: 10px;
    padding: 10px 12px;
}
.timeline-title { font-size: 0.82rem; font-weight: 600; }
.timeline-meta {
    font-size: 0.7rem;
    color: rgba(var(--v-theme-on-surface), 0.6);
    margin-top: 2px;
}
.timeline-notes {
    margin-top: 6px;
    font-size: 0.72rem;
    font-style: italic;
    color: rgba(var(--v-theme-on-surface), 0.7);
    background: rgba(var(--v-theme-on-surface), 0.04);
    padding: 6px 8px;
    border-radius: 6px;
    border-left: 3px solid rgba(var(--v-theme-on-surface), 0.2);
}
.timeline-chip {
    font-size: 0.65rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 999px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.timeline-chip--ok { background: rgba(16,185,129,0.15); color: #10B981; }
.timeline-chip--bad { background: rgba(239,68,68,0.15); color: #EF4444; }

/* Audit */
.audit-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}
.audit-row {
    display: flex; align-items: flex-start; gap: 8px;
    padding: 10px 12px;
    border-radius: 10px;
    background: rgb(var(--v-theme-surface));
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
.audit-icon {
    width: 16px; height: 16px;
    color: rgba(var(--v-theme-on-surface), 0.55);
    margin-top: 2px;
    flex-shrink: 0;
}
.audit-body { min-width: 0; }
.audit-label {
    font-size: 0.65rem;
    color: rgba(var(--v-theme-on-surface), 0.55);
    text-transform: uppercase;
    letter-spacing: 0.04em;
    font-weight: 500;
}
.audit-value {
    font-size: 0.78rem;
    font-weight: 500;
    margin-top: 1px;
    word-break: break-word;
}
</style>
