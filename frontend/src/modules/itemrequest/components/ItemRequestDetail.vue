<script lang="ts" setup>
import { computed } from 'vue'
// Import Composables
import { useTranslate } from '@/composables/useTranslate'
// Import Utils
import { formatDate } from '@/utils/date'
// Import Icons
import SystemUiconsClose from '~icons/system-uicons/close'
import MingcuteTimeLine from '~icons/mingcute/time-line'
import UisCheck from '~icons/uis/check'
import CodexCross from '~icons/codex/cross'
import CiCheckAll from '~icons/ci/check-all'
import FluentNotepad24Filled from '~icons/fluent/notepad-24-filled'
import MaterialSymbolsLightDraftRounded from '~icons/material-symbols-light/draft-rounded'
import MdiFileDocumentOutline from '~icons/mdi/file-document-outline'
import MdiCalendarOutline from '~icons/mdi/calendar-outline'
import MdiTruckOutline from '~icons/mdi/truck-outline'
import MdiBriefcaseOutline from '~icons/mdi/briefcase-outline'
import MdiOfficeBuildingOutline from '~icons/mdi/office-building-outline'
import MdiWarehouse from '~icons/mdi/warehouse'
import MdiClipboardTextOutline from '~icons/mdi/clipboard-text-outline'
import MdiIdentifier from '~icons/mdi/identifier'
import MdiPackageVariantClosed from '~icons/mdi/package-variant-closed'
import MdiAccountOutline from '~icons/mdi/account-outline'
import MdiClockOutline from '~icons/mdi/clock-outline'
import MdiPencilOutline from '~icons/mdi/pencil-outline'
import MdiUpdate from '~icons/mdi/update'
import MdiCubeOutline from '~icons/mdi/cube-outline'
import { useThemeStore } from '@/stores/theme'

const model = defineModel<boolean>()

const props = defineProps<{
    items: ItemRequestList
}>()

const emit = defineEmits<{
    (e: 'close'): void
}>()

const t = useTranslate()
const themeStore = useThemeStore()

const heroColor = computed(() => themeStore.resolvedTheme === 'dark' ? '#ffffff' : '#000000')

const handleClose = () => emit('close')

// =========================
// Derived
// =========================
type StatusMeta = { color: string; icon: any; bg: string }

const STATUS_MAP: Record<string, StatusMeta> = {
    'waiting approval': { color: '#F59E0B', bg: 'rgba(245,158,11,0.18)', icon: MingcuteTimeLine },
    'approved':         { color: '#10B981', bg: 'rgba(16,185,129,0.18)', icon: UisCheck },
    'rejected':         { color: '#EF4444', bg: 'rgba(239,68,68,0.18)',  icon: CodexCross },
    'revised':          { color: '#F97316', bg: 'rgba(249,115,22,0.18)', icon: FluentNotepad24Filled },
    'converted':        { color: '#3B82F6', bg: 'rgba(59,130,246,0.18)', icon: CiCheckAll },
}

const statusMeta = computed<StatusMeta>(() => {
    const key = (props.items?.status ?? '').toLowerCase()
    return STATUS_MAP[key] ?? { color: '#9CA3AF', bg: 'rgba(156,163,175,0.18)', icon: MaterialSymbolsLightDraftRounded }
})

const details = computed(() => props.items?.details ?? [])
const totalItems = computed(() => details.value.length)
const totalQty = computed(() =>
    details.value.reduce((sum, d) => sum + (Number(d?.qty) || 0), 0)
)

const generalRows = computed(() => [
    { icon: MdiClipboardTextOutline, label: t('requirement'), value: props.items?.requirement },
    { icon: MdiCalendarOutline,      label: t('requestDate'), value: formatDate(props.items?.request_date) },
    { icon: MdiTruckOutline,         label: t('unitCode'),    value: props.items?.unit_code },
    { icon: MdiIdentifier,           label: t('woNumber'),    value: props.items?.wo_number },
    { icon: MdiWarehouse,            label: t('warehouse'),   value: props.items?.warehouse?.name },
    { icon: MdiBriefcaseOutline,     label: t('projectName'), value: props.items?.project_name },
    { icon: MdiOfficeBuildingOutline,label: t('departmentName'), value: props.items?.department_name },
])

const auditRows = computed(() => [
    { icon: MdiAccountOutline, label: t('createdBy'), value: props.items?.created_by_name },
    { icon: MdiClockOutline,   label: t('createdAt'), value: formatDate(props.items?.created_at) },
    { icon: MdiPencilOutline,  label: t('updatedBy'), value: props.items?.updated_by_name },
    { icon: MdiUpdate,         label: t('updatedAt'), value: formatDate(props.items?.updated_at) },
])
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
        width="540"
        temporary
        :scrim="false"
    >
        <!-- ============ Hero Header ============ -->
        <div class="hero" :style="{ color: heroColor }">
            <div class="hero-content">
                <div class="d-flex justify-space-between align-start mb-4">
                    <div class="hero-badge">
                        <v-icon :icon="MdiFileDocumentOutline" class="hero-badge-icon" :color="heroColor" />
                    </div>
                    <v-btn
                        icon
                        density="comfortable"
                        variant="text"
                        color="white"
                        @click="handleClose"
                    >
                        <component :is="SystemUiconsClose" class="close-icon" :style="{ color: heroColor }" />
                    </v-btn>
                </div>
                <div class="hero-label">{{ t('requestNumber') }}</div>
                <div class="hero-title">{{ items?.request_number || '-' }}</div>
                <div class="hero-meta">
                    <component :is="MdiCalendarOutline" class="hero-meta-icon" />
                    <span>{{ formatDate(items?.request_date) || '-' }}</span>
                    <span class="hero-meta-dot">•</span>
                    <component :is="MdiOfficeBuildingOutline" class="hero-meta-icon" />
                    <span>{{ items?.department_name || '-' }}</span>
                </div>
                <div
                    class="status-pill mt-3"
                    :style="{ background: statusMeta.bg, color: statusMeta.color }"
                >
                    <component :is="statusMeta.icon" class="status-pill-icon" />
                    <span class="text-capitalize">{{ (items?.status || '-').toLowerCase() }}</span>
                </div>
                <div v-if="items?.reject_reason" class="reject-note mt-3">
                    <div class="reject-note-label">{{ t('rejectReason') }}</div>
                    <div class="reject-note-value">{{ items.reject_reason }}</div>
                </div>
            </div>
        </div>

        <!-- ============ Content ============ -->
        <div v-if="items" class="content-wrap">
            <!-- Stat tiles -->
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
                        <div class="stat-value">{{ totalQty.toLocaleString() }}</div>
                        <div class="stat-label">{{ t('totalQty') }}</div>
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

            <!-- Details -->
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
                            <div v-if="(d as any).description" class="item-desc">{{ (d as any).description }}</div>
                        </div>
                        <div class="item-qty">
                            <div class="item-qty-value">{{ Number(d.qty || 0).toLocaleString() }}</div>
                            <div class="item-qty-unit">{{ d.unit?.symbol || '-' }}</div>
                        </div>
                    </div>
                </div>
                <div v-else class="empty-state">
                    <component :is="MdiCubeOutline" class="empty-icon" />
                    <div class="empty-text">{{ t('noData') }}</div>
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
.custom {
    z-index: 1007 !important;
}
.custom-overlay {
    z-index: 1006 !important;
}

/* ============ Hero ============ */
.hero {
    position: relative;
    padding: 20px 24px 22px;
    color: #fff;
    overflow: hidden;
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
    background: rgba(255, 255, 255, 0.08);
}
.hero-bg::before {
    width: 180px; height: 180px;
    top: -60px; right: -40px;
}
.hero-bg::after {
    width: 120px; height: 120px;
    bottom: -40px; left: -30px;
}
.hero-content {
    position: relative;
}
.hero-badge {
    width: 44px; height: 44px;
    border-radius: 12px;
    background: rgba(180, 180, 180, 0.18);
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
}
.hero-badge-icon {
    width: 22px; height: 22px;
    color: #fff;
}
.close-icon {
    width: 18px; height: 18px;
}
.hero-label {
    font-size: 0.7rem;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    opacity: 0.8;
    font-weight: 500;
}
.hero-title {
    font-size: 1.35rem;
    font-weight: 700;
    line-height: 1.3;
    margin-top: 2px;
    letter-spacing: -0.01em;
}
.hero-meta {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
    opacity: 0.9;
    margin-top: 8px;
    flex-wrap: wrap;
}
.hero-meta-icon {
    width: 14px; height: 14px;
}
.hero-meta-dot {
    opacity: 0.5;
    margin: 0 2px;
}
.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
}
.status-pill-icon {
    width: 14px; height: 14px;
}
.reject-note {
    background: rgba(180, 180, 180, 0.12);
    border-left: 3px solid rgba(180, 180, 180, 0.6);
    border-radius: 6px;
    padding: 8px 12px;
}
.reject-note-label {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    opacity: 0.8;
    font-weight: 600;
}
.reject-note-value {
    font-size: 0.8rem;
    margin-top: 2px;
    line-height: 1.35;
    word-break: break-word;
}

/* ============ Content ============ */
.content-wrap {
    padding: 18px 20px 20px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* Stats */
.stat-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.stat-tile {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    border-radius: 12px;
    background: rgb(var(--v-theme-surface));
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
.stat-icon {
    width: 28px; height: 28px;
    padding: 6px;
    border-radius: 8px;
    background: rgba(var(--v-theme-primary), 0.12);
    color: rgb(var(--v-theme-primary));
    box-sizing: content-box;
}
.stat-value {
    font-size: 1.1rem;
    font-weight: 700;
    line-height: 1.1;
}
.stat-label {
    font-size: 0.7rem;
    color: rgba(var(--v-theme-on-surface), 0.6);
    margin-top: 2px;
}

/* Sections */
.section {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.section-head {
    display: flex;
    align-items: center;
    gap: 8px;
}
.section-accent {
    width: 3px;
    height: 16px;
    background: rgb(var(--v-theme-primary));
    border-radius: 2px;
}
.section-title {
    font-size: 0.85rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    margin: 0;
    flex: 1;
}
.section-count {
    font-size: 0.7rem;
    padding: 2px 8px;
    border-radius: 999px;
    background: rgba(var(--v-theme-primary), 0.12);
    color: rgb(var(--v-theme-primary));
    font-weight: 600;
}

/* Info list */
.info-list {
    background: rgb(var(--v-theme-surface));
    border-radius: 12px;
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    overflow: hidden;
}
.info-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    transition: background 0.15s;
}
.info-row:hover {
    background: rgba(var(--v-theme-primary), 0.04);
}
.info-row + .info-row {
    border-top: 1px solid rgba(var(--v-border-color), calc(var(--v-border-opacity) * 0.7));
}
.info-row-icon {
    width: 32px; height: 32px;
    border-radius: 8px;
    background: rgba(var(--v-theme-primary), 0.08);
    color: rgb(var(--v-theme-primary));
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.info-row-icon > * {
    width: 16px; height: 16px;
}
.info-row-body {
    flex: 1;
    min-width: 0;
}
.info-row-label {
    font-size: 0.7rem;
    color: rgba(var(--v-theme-on-surface), 0.6);
    font-weight: 500;
}
.info-row-value {
    font-size: 0.85rem;
    font-weight: 500;
    word-break: break-word;
    margin-top: 1px;
}

/* Items list */
.items-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.item-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 12px;
    background: rgb(var(--v-theme-surface));
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    transition: all 0.15s;
}
.item-card:hover {
    border-color: rgba(var(--v-theme-primary), 0.4);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}
.item-index {
    width: 26px; height: 26px;
    border-radius: 8px;
    background: rgba(var(--v-theme-primary), 0.12);
    color: rgb(var(--v-theme-primary));
    font-size: 0.75rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.item-body {
    flex: 1;
    min-width: 0;
}
.item-name {
    font-size: 0.875rem;
    font-weight: 600;
    line-height: 1.25;
}
.item-code {
    font-size: 0.7rem;
    color: rgba(var(--v-theme-on-surface), 0.55);
    margin-top: 2px;
    font-family: ui-monospace, monospace;
}
.item-desc {
    font-size: 0.72rem;
    color: rgba(var(--v-theme-on-surface), 0.7);
    margin-top: 4px;
    line-height: 1.3;
}
.item-qty {
    text-align: right;
    flex-shrink: 0;
    padding-left: 10px;
    border-left: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
.item-qty-value {
    font-size: 1rem;
    font-weight: 700;
    line-height: 1.1;
    color: rgb(var(--v-theme-primary));
}
.item-qty-unit {
    font-size: 0.7rem;
    color: rgba(var(--v-theme-on-surface), 0.6);
    margin-top: 2px;
}

.empty-state {
    padding: 32px 12px;
    text-align: center;
    background: rgb(var(--v-theme-surface));
    border-radius: 12px;
    border: 1px dashed rgba(var(--v-border-color), var(--v-border-opacity));
}
.empty-icon {
    width: 36px; height: 36px;
    color: rgba(var(--v-theme-on-surface), 0.3);
}
.empty-text {
    font-size: 0.8rem;
    color: rgba(var(--v-theme-on-surface), 0.55);
    margin-top: 6px;
}

/* Audit */
.audit-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}
.audit-row {
    display: flex;
    align-items: flex-start;
    gap: 8px;
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
.audit-body {
    min-width: 0;
}
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
