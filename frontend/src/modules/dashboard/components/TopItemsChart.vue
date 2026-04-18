<script lang="ts" setup>
import { computed } from 'vue'
import { AgCharts } from 'ag-charts-vue3'
import type { AgChartOptions } from 'ag-charts-community'
import { useThemeStore } from '@/stores/theme'
import type { TopItem } from '@/stores/dashboard'

const props = defineProps<{ data: TopItem[] }>()
const themeStore = useThemeStore()

const options = computed<AgChartOptions>(() => {
    const isDark = themeStore.resolvedTheme === 'dark'

    // Truncate long item names so chart axis tidy.
    const chartData = props.data.map((d) => ({
        ...d,
        name: d.name.length > 24 ? d.name.slice(0, 22) + '…' : d.name,
    }))

    return {
        data: chartData,
        theme: isDark ? 'ag-default-dark' : 'ag-default',
        background: { visible: false },
        padding: { top: 8, right: 16, bottom: 8, left: 8 },
        series: [
            {
                type: 'bar',
                direction: 'horizontal',
                xKey: 'name',
                yKey: 'total',
                yName: 'Jumlah Request',
                fill: '#59A14F',
                stroke: '#59A14F',
                cornerRadius: 6,
                label: { enabled: true, fontSize: 11, fontWeight: 'bold' },
            },
        ],
        axes: {
            // Horizontal bar: kategori di sumbu Y, nilai di sumbu X.
            y: {
                type: 'category',
                position: 'left',
                label: { fontSize: 11 },
            },
            x: {
                type: 'number',
                position: 'bottom',
                label: { fontSize: 11 },
                nice: true,
            },
        },
        legend: { enabled: false },
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
