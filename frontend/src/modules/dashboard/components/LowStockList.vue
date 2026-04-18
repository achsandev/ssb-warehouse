<script lang="ts" setup>
import type { LowStockItem } from '@/stores/dashboard'

const props = defineProps<{ items: LowStockItem[] }>()

const pctColor = (pct: number) => {
    if (pct < 25) return 'error'
    if (pct < 60) return 'warning'
    return 'success'
}
</script>

<template>
    <div class="low-stock-list">
        <div v-if="!items.length" class="empty-state text-body-2 text-medium-emphasis text-center py-6">
            Tidak ada item di bawah minimum stock 🎉
        </div>

        <div
            v-for="(item, idx) in items"
            :key="idx"
            class="low-stock-row"
        >
            <div class="grow min-w-0">
                <div class="text-body-2 font-weight-medium text-truncate" :title="item.name">{{ item.name }}</div>
                <div class="text-caption text-medium-emphasis">
                    Current: {{ item.current_stock.toLocaleString('id-ID') }}
                    / Min: {{ item.min_qty.toLocaleString('id-ID') }}
                </div>
            </div>
            <div class="low-stock-bar">
                <v-progress-linear
                    :model-value="Math.min(item.pct, 100)"
                    :color="pctColor(item.pct)"
                    height="6"
                    rounded
                />
                <span class="text-caption font-weight-bold ms-2" :class="`text-${pctColor(item.pct)}`">
                    {{ item.pct }}%
                </span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.low-stock-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-height: 320px;
    overflow-y: auto;
}
.low-stock-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 10px;
    border: 1px solid rgba(var(--v-theme-on-surface), 0.06);
    border-radius: 8px;
    transition: background 0.15s ease;
}
.low-stock-row:hover {
    background: rgba(var(--v-theme-on-surface), 0.02);
}
.low-stock-bar {
    display: flex;
    align-items: center;
    min-width: 140px;
    flex-shrink: 0;
}
.low-stock-bar :deep(.v-progress-linear) {
    flex: 1;
}
.empty-state {
    padding: 24px 8px;
}
@media (max-width: 600px) {
    .low-stock-row {
        flex-direction: column;
        align-items: stretch;
    }
    .low-stock-bar { min-width: 0; }
}
</style>
