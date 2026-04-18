<script lang="ts" setup>
import { computed, toRef } from 'vue'
import { useCountUp } from '../composables/useCountUp'

interface Props {
    label: string
    value: number
    icon?: any
    /** Vuetify color name or hex (#RRGGBB) untuk accent. */
    color?: string
    /** Optional sub-label (e.g. "this month", "pending"). */
    hint?: string
    /** Optional trend in %. Positive = up (green), negative = down (red). */
    trend?: number | null
    /** Decimals untuk count-up (default 0). */
    decimals?: number
}

const props = withDefaults(defineProps<Props>(), {
    color: 'primary',
    hint: '',
    trend: null,
    decimals: 0,
})

const valueRef = toRef(props, 'value')
const { display } = useCountUp(valueRef, { decimals: props.decimals })

const trendColor = computed<string | undefined>(() => {
    if (props.trend == null) return undefined
    return props.trend >= 0 ? 'success' : 'error'
})

const trendLabel = computed(() => {
    if (props.trend == null) return ''
    const sign = props.trend >= 0 ? '+' : ''
    return `${sign}${props.trend}%`
})
</script>

<template>
    <v-card class="kpi-card h-100" rounded="lg" variant="flat" :border="true">
        <v-card-text class="d-flex flex-column ga-3 pa-4">
            <div class="d-flex align-start justify-space-between">
                <div class="kpi-label text-caption text-medium-emphasis font-weight-medium text-uppercase">
                    {{ label }}
                </div>
                <div v-if="icon" class="kpi-icon" :style="{ background: `rgba(var(--v-theme-${color}), 0.12)` }">
                    <v-icon :icon="icon" :color="color" size="20" />
                </div>
            </div>

            <div class="kpi-value" :style="{ color: `rgb(var(--v-theme-${color}))` }">
                {{ display.toLocaleString('id-ID') }}
            </div>

            <div class="d-flex align-center ga-2">
                <v-chip
                    v-if="trend != null"
                    :color="trendColor"
                    size="x-small"
                    variant="tonal"
                    class="font-weight-bold"
                >
                    {{ trendLabel }}
                </v-chip>
                <span v-if="hint" class="text-caption text-medium-emphasis">{{ hint }}</span>
            </div>
        </v-card-text>
    </v-card>
</template>

<style scoped>
.kpi-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
}
.kpi-label {
    letter-spacing: 0.06em;
    line-height: 1.2;
}
.kpi-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.kpi-value {
    font-size: 1.9rem;
    font-weight: 700;
    line-height: 1.1;
    font-variant-numeric: tabular-nums;
}
@media (max-width: 600px) {
    .kpi-value { font-size: 1.5rem; }
}
</style>
