<script lang="ts" setup>
import { computed } from 'vue'
import { AgCharts } from 'ag-charts-vue3'
import type { AgChartOptions } from 'ag-charts-community'
import { useThemeStore } from '@/stores/theme'
import type { StockMovementPoint } from '@/stores/dashboard'

const props = defineProps<{ data: StockMovementPoint[] }>()
const themeStore = useThemeStore()

const options = computed<AgChartOptions>(() => {
    const isDark = themeStore.resolvedTheme === 'dark'

    // Cast: AgChartOptions adalah union (cartesian/polar/dll) sehingga inferensi
    // literal object tidak match. Kita tahu ini bar chart cartesian.
    return {
        data: props.data,
        theme: isDark ? 'ag-default-dark' : 'ag-default',
        background: { visible: false },
        padding: { top: 8, right: 16, bottom: 8, left: 8 },
        series: [
            {
                type: 'bar',
                xKey: 'month',
                yKey: 'receive',
                yName: 'Penerimaan',
                fill: '#4472C4',
                stroke: '#4472C4',
                cornerRadius: 6,
            },
            {
                type: 'bar',
                xKey: 'month',
                yKey: 'usage',
                yName: 'Pemakaian',
                fill: '#E15759',
                stroke: '#E15759',
                cornerRadius: 6,
            },
        ],
        axes: {
            x: {
                type: 'category',
                position: 'bottom',
                label: { fontSize: 11 },
            },
            y: {
                type: 'number',
                position: 'left',
                label: { fontSize: 11 },
                nice: true,
            },
        },
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
