<script lang="ts" setup>
import { computed } from 'vue'
import type { ActivityItem } from '@/stores/dashboard'
// Icons
import MdiTruckDelivery from '~icons/mdi/truck-delivery'
import MdiClipboardList from '~icons/mdi/clipboard-list-outline'
import MdiPackageVariantClosed from '~icons/mdi/package-variant-closed'

const props = defineProps<{ items: ActivityItem[] }>()

const typeMeta: Record<string, { icon: any; color: string; label: string }> = {
    receipt: { icon: MdiTruckDelivery,       color: 'primary', label: 'Penerimaan' },
    usage:   { icon: MdiPackageVariantClosed, color: 'deep-orange', label: 'Pemakaian' },
    request: { icon: MdiClipboardList,       color: 'info', label: 'Permintaan' },
}

const statusColor = (s: string) => {
    switch ((s ?? '').toLowerCase()) {
        case 'waiting approval': return 'warning'
        case 'approved':         return 'success'
        case 'rejected':         return 'red'
        case 'revised':          return 'deep-orange'
        case 'converted':        return 'blue'
        default:                 return 'grey'
    }
}

const formatRel = (iso: string) => {
    if (!iso) return '-'
    const d = new Date(iso)
    if (Number.isNaN(d.getTime())) return '-'
    const diffMs = Date.now() - d.getTime()
    const mins = Math.floor(diffMs / 60000)
    if (mins < 1) return 'baru saja'
    if (mins < 60) return `${mins} menit lalu`
    const hrs = Math.floor(mins / 60)
    if (hrs < 24) return `${hrs} jam lalu`
    const days = Math.floor(hrs / 24)
    if (days < 30) return `${days} hari lalu`
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

const list = computed(() => props.items.map((it) => ({
    ...it,
    meta: typeMeta[it.type] ?? typeMeta.request,
    statusColor: statusColor(it.status),
    relative: formatRel(it.date),
})))
</script>

<template>
    <div class="activity-list">
        <div v-if="!items.length" class="empty-state text-body-2 text-medium-emphasis text-center py-6">
            Belum ada aktivitas
        </div>

        <div v-for="(it, idx) in list" :key="idx" class="activity-row">
            <div class="activity-icon" :style="{ background: `rgba(var(--v-theme-${it.meta.color}), 0.12)` }">
                <v-icon :icon="it.meta.icon" :color="it.meta.color" size="18" />
            </div>
            <div class="grow min-w-0">
                <div class="d-flex align-center ga-2 flex-wrap">
                    <span class="text-body-2 font-weight-medium text-truncate">{{ it.meta.label }}</span>
                    <span class="text-caption text-medium-emphasis">{{ it.number }}</span>
                </div>
                <div class="text-caption text-medium-emphasis">
                    oleh <span class="font-weight-medium">{{ it.user || '-' }}</span> · {{ it.relative }}
                </div>
            </div>
            <v-chip :color="it.statusColor" size="x-small" variant="tonal" class="shrink-0">
                {{ it.status }}
            </v-chip>
        </div>
    </div>
</template>

<style scoped>
.activity-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
    max-height: 380px;
    overflow-y: auto;
}
.activity-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 10px;
    border-radius: 8px;
    transition: background 0.15s ease;
}
.activity-row:hover {
    background: rgba(var(--v-theme-on-surface), 0.03);
}
.activity-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.empty-state { padding: 24px 8px; }
</style>
