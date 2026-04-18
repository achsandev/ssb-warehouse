<script lang="ts" setup>
import { computed } from 'vue'
// Composables
import { useTranslate } from '@/composables/useTranslate'
// Utils
import { formatDate } from '@/utils/date'
// Icons
import MdiClose from '~icons/mdi/close'
import MdiWarehouse from '~icons/mdi/warehouse'
import MdiMapMarkerOutline from '~icons/mdi/map-marker-outline'
import MdiTextBoxOutline from '~icons/mdi/text-box-outline'
import MdiInformationOutline from '~icons/mdi/information-outline'
import MdiCalendarClockOutline from '~icons/mdi/calendar-clock-outline'
import MdiAccountOutline from '~icons/mdi/account-outline'
import MdiAccountEditOutline from '~icons/mdi/account-edit-outline'
import MdiViewGridOutline from '~icons/mdi/view-grid-outline'
import MdiWaterOutline from '~icons/mdi/water-outline'
import MdiPackageVariantClosed from '~icons/mdi/package-variant-closed'

// ─── Props / Model ──────────────────────────────────────────────────────────
const model = defineModel<boolean>()

const props = defineProps<{
    items: WarehouseList
}>()

const t = useTranslate()

// ─── Derivations ────────────────────────────────────────────────────────────
/** Rak & tangki dibungkus sebagai array terproteksi agar render aman meski
 * backend suatu saat mengembalikan null/undefined. */
const racks = computed<Array<{ name: string }>>(() =>
    Array.isArray(props.items?.racks) ? (props.items.racks as any[]) : [],
)
const tanks = computed<Array<{ name: string }>>(() =>
    Array.isArray(props.items?.tanks) ? (props.items.tanks as any[]) : [],
)

const handleClose = () => {
    model.value = false
}
</script>

<template>
    <v-navigation-drawer
        v-model="model"
        class="wh-detail-drawer"
        location="right"
        temporary
        :width="480"
    >
        <!-- ════ Header ════ -->
        <v-toolbar density="comfortable" flat color="transparent" class="px-1">
            <template #prepend>
                <v-icon :icon="MdiWarehouse" color="primary" class="ms-2" />
            </template>
            <v-toolbar-title class="text-subtitle-1 font-weight-bold">
                {{ t('detail') }}
            </v-toolbar-title>
            <template #append>
                <v-btn
                    :icon="MdiClose"
                    variant="text"
                    density="comfortable"
                    :aria-label="t('close')"
                    @click="handleClose"
                />
            </template>
        </v-toolbar>

        <v-divider />

        <div class="wh-detail-body">
            <!-- ════ Hero ════ -->
            <div class="hero-card">
                <div class="hero-gradient" aria-hidden="true" />
                <div class="hero-content">
                    <div class="hero-icon">
                        <v-icon :icon="MdiWarehouse" size="24" />
                    </div>
                    <div class="hero-text">
                        <div class="hero-label">{{ t('warehouse') }}</div>
                        <h2 class="hero-title">{{ items.name || '-' }}</h2>
                        <div v-if="items.address" class="hero-meta">
                            <v-icon :icon="MdiMapMarkerOutline" size="14" />
                            <span>{{ items.address }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ════ Informasi Umum ════ -->
            <section class="section">
                <header class="section-head">
                    <v-icon :icon="MdiInformationOutline" size="16" />
                    <span>{{ t('generalInfo') }}</span>
                </header>
                <dl class="info-list">
                    <div class="info-row">
                        <dt class="info-label">
                            <v-icon :icon="MdiTextBoxOutline" size="14" />
                            {{ t('additionalInfo') }}
                        </dt>
                        <dd class="info-value">
                            {{ items.additional_info || '-' }}
                        </dd>
                    </div>
                </dl>
            </section>

            <!-- ════ Audit ════ -->
            <section class="section">
                <header class="section-head">
                    <v-icon :icon="MdiCalendarClockOutline" size="16" />
                    <span>{{ t('auditTrail') }}</span>
                </header>
                <div class="audit-grid">
                    <div class="audit-item">
                        <div class="audit-label">
                            <v-icon :icon="MdiCalendarClockOutline" size="14" />
                            {{ t('createdAt') }}
                        </div>
                        <div class="audit-value">{{ formatDate(items.created_at) || '-' }}</div>
                    </div>
                    <div class="audit-item">
                        <div class="audit-label">
                            <v-icon :icon="MdiAccountOutline" size="14" />
                            {{ t('createdBy') }}
                        </div>
                        <div class="audit-value">{{ items.created_by_name || '-' }}</div>
                    </div>
                    <div class="audit-item">
                        <div class="audit-label">
                            <v-icon :icon="MdiCalendarClockOutline" size="14" />
                            {{ t('updatedAt') }}
                        </div>
                        <div class="audit-value">{{ formatDate(items.updated_at) || '-' }}</div>
                    </div>
                    <div class="audit-item">
                        <div class="audit-label">
                            <v-icon :icon="MdiAccountEditOutline" size="14" />
                            {{ t('updatedBy') }}
                        </div>
                        <div class="audit-value">{{ items.updated_by_name || '-' }}</div>
                    </div>
                </div>
            </section>

            <!-- ════ Rak ════ -->
            <section class="section">
                <header class="section-head">
                    <v-icon :icon="MdiViewGridOutline" size="16" />
                    <span>{{ t('rack') }}</span>
                    <v-chip
                        size="x-small"
                        variant="tonal"
                        color="primary"
                        class="font-weight-bold ms-auto"
                    >
                        {{ racks.length }}
                    </v-chip>
                </header>
                <div v-if="racks.length === 0" class="empty">
                    <v-icon :icon="MdiPackageVariantClosed" size="28" class="empty-icon" />
                    <span>{{ t('noData') }}</span>
                </div>
                <ul v-else class="item-list">
                    <li
                        v-for="(rack, idx) in racks"
                        :key="rack.name + idx"
                        class="item-row"
                    >
                        <span class="item-index">{{ idx + 1 }}</span>
                        <span class="item-name">{{ rack.name }}</span>
                    </li>
                </ul>
            </section>

            <!-- ════ Tangki ════ -->
            <section class="section">
                <header class="section-head">
                    <v-icon :icon="MdiWaterOutline" size="16" />
                    <span>{{ t('tank') }}</span>
                    <v-chip
                        size="x-small"
                        variant="tonal"
                        color="primary"
                        class="font-weight-bold ms-auto"
                    >
                        {{ tanks.length }}
                    </v-chip>
                </header>
                <div v-if="tanks.length === 0" class="empty">
                    <v-icon :icon="MdiPackageVariantClosed" size="28" class="empty-icon" />
                    <span>{{ t('noData') }}</span>
                </div>
                <ul v-else class="item-list">
                    <li
                        v-for="(tank, idx) in tanks"
                        :key="tank.name + idx"
                        class="item-row"
                    >
                        <span class="item-index">{{ idx + 1 }}</span>
                        <span class="item-name">{{ tank.name }}</span>
                    </li>
                </ul>
            </section>
        </div>

        <!-- ════ Footer ════ -->
        <template #append>
            <v-divider />
            <div class="d-flex justify-end pa-3">
                <v-btn variant="text" @click="handleClose">
                    {{ t('close') }}
                </v-btn>
            </div>
        </template>
    </v-navigation-drawer>
</template>

<style scoped>
/* ════ Drawer chrome ════ */
.wh-detail-body {
    padding: 12px 14px 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    overflow-y: auto;
}

/* ════ Hero ════ */
.hero-card {
    position: relative;
    overflow: hidden;
    border-radius: 14px;
    color: #fff;
}
.hero-gradient {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(800px 140px at 0% 0%, rgba(255, 255, 255, 0.18), transparent 60%),
        linear-gradient(135deg,
            rgb(var(--v-theme-primary)) 0%,
            color-mix(in srgb, rgb(var(--v-theme-primary)) 75%, #1a237e) 100%);
    z-index: 0;
}
.hero-content {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px;
}
.hero-icon {
    width: 44px;
    height: 44px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.18);
    backdrop-filter: blur(4px);
}
.hero-text {
    min-width: 0;
    flex: 1;
}
.hero-label {
    font-size: 0.66rem;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    font-weight: 500;
    opacity: 0.85;
}
.hero-title {
    font-size: clamp(1rem, 2vw, 1.15rem);
    font-weight: 700;
    line-height: 1.2;
    margin: 2px 0 6px;
    word-break: break-word;
}
.hero-meta {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.78rem;
    background: rgba(255, 255, 255, 0.14);
    padding: 3px 8px;
    border-radius: 999px;
    word-break: break-word;
    max-width: 100%;
}

/* ════ Section ════ */
.section {
    border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
    border-radius: 12px;
    padding: 12px;
    background: rgba(var(--v-theme-on-surface), 0.02);
}
.section-head {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: rgba(var(--v-theme-on-surface), 0.72);
    margin-bottom: 10px;
    padding-bottom: 8px;
    border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.08);
}

/* ════ Info list ════ */
.info-list {
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.info-row {
    display: flex;
    flex-direction: column;
    gap: 3px;
}
.info-label {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.72rem;
    font-weight: 500;
    color: rgba(var(--v-theme-on-surface), 0.6);
}
.info-value {
    margin: 0;
    font-size: 0.85rem;
    color: rgba(var(--v-theme-on-surface), 0.92);
    word-break: break-word;
    white-space: pre-wrap;
}

/* ════ Audit grid ════ */
.audit-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 8px;
}
.audit-item {
    padding: 8px 10px;
    border-radius: 8px;
    background: rgb(var(--v-theme-surface));
    border: 1px solid rgba(var(--v-theme-on-surface), 0.06);
}
.audit-label {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.66rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: rgba(var(--v-theme-on-surface), 0.55);
    font-weight: 500;
}
.audit-value {
    font-size: 0.82rem;
    font-weight: 600;
    color: rgba(var(--v-theme-on-surface), 0.92);
    margin-top: 2px;
    word-break: break-word;
}

/* ════ Item list (racks/tanks) ════ */
.item-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 6px;
    max-height: 260px;
    overflow-y: auto;
}
.item-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    border-radius: 8px;
    background: rgb(var(--v-theme-surface));
    border: 1px solid rgba(var(--v-theme-on-surface), 0.06);
    transition: border-color 0.15s ease, transform 0.15s ease;
}
.item-row:hover {
    border-color: rgba(var(--v-theme-primary), 0.35);
    transform: translateX(2px);
}
.item-index {
    font-size: 0.7rem;
    font-weight: 700;
    min-width: 22px;
    height: 22px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    background: rgba(var(--v-theme-primary), 0.12);
    color: rgb(var(--v-theme-primary));
}
.item-name {
    font-size: 0.85rem;
    color: rgba(var(--v-theme-on-surface), 0.9);
    word-break: break-word;
    flex: 1;
    min-width: 0;
}

/* ════ Empty state ════ */
.empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 18px 8px;
    color: rgba(var(--v-theme-on-surface), 0.55);
    font-size: 0.8rem;
}
.empty-icon {
    opacity: 0.5;
}

/* ════ Responsive ════ */
@media (max-width: 600px) {
    .hero-content {
        padding: 12px;
    }
    .hero-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
    }
}
</style>
