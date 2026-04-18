<script lang="ts" setup>
import { computed } from 'vue'
import { AgCharts } from 'ag-charts-vue3'
import type { AgChartOptions } from 'ag-charts-community'
import { useThemeStore } from '@/stores/theme'
import type { CategorySlice } from '@/stores/dashboard'

const props = defineProps<{ data: CategorySlice[] }>()
const themeStore = useThemeStore()

const palette = ['#4472C4', '#E15759', '#59A14F', '#F28E2B', '#B07AA1', '#76B7B2', '#FF9DA7', '#9C755F']

const options = computed<AgChartOptions>(() => {
    const isDark = themeStore.resolvedTheme === 'dark'
    const total = props.data.reduce((s, d) => s + (d.total || 0), 0)

    return {
        data: props.data,
        theme: isDark ? 'ag-default-dark' : 'ag-default',
        background: { visible: false },
        padding: { top: 8, right: 8, bottom: 8, left: 8 },
        series: [
            {
                type: 'donut',
                angleKey: 'total',
                calloutLabelKey: 'name',
                sectorLabelKey: 'total',
                innerRadiusRatio: 0.6,
                fills: palette,
                strokes: palette,
                strokeWidth: 1,
                innerLabels: [
                    { text: total.toLocaleString('id-ID'), fontSize: 24, fontWeight: 'bold' },
                    { text: 'Total Item', fontSize: 12, color: 'rgba(0,0,0,0.55)' },
                ],
                sectorLabel: {
                    color: '#ffffff',
                    fontWeight: 'bold',
                    formatter: ({ value }: { value: number }) => String(value),
                },
                calloutLabel: { enabled: true, fontSize: 11 },
            } as any,
        ],
        legend: { position: 'bottom', item: { marker: { shape: 'circle' } } },
    } as unknown as AgChartOptions
})
</script>

<template>
    <div class="chart-container">
        <ag-charts :options="options" />
    </div>
</template>

<style scoped>
.chart-container {
    width: 100%;
    height: 320px;
}
</style>
