<script lang="ts" setup>
defineProps<{
    title: string
    subtitle?: string
    icon?: any
    /** Vuetify color key untuk accent icon. */
    color?: string
    /** Loading state menampilkan skeleton/overlay. */
    loading?: boolean
}>()
</script>

<template>
    <v-card class="section-card h-100" rounded="lg" variant="flat" :border="true" :loading="loading">
        <v-card-title class="d-flex align-center ga-2 pa-4 pb-2">
            <div v-if="icon" class="section-icon" :style="{ background: `rgba(var(--v-theme-${color ?? 'primary'}), 0.12)` }">
                <v-icon :icon="icon" :color="color ?? 'primary'" size="18" />
            </div>
            <div class="grow min-w-0">
                <div class="text-subtitle-2 font-weight-bold">{{ title }}</div>
                <div v-if="subtitle" class="text-caption text-medium-emphasis">{{ subtitle }}</div>
            </div>
            <slot name="toolbar" />
        </v-card-title>

        <v-divider class="mx-4" />

        <v-card-text class="pa-4 pt-3">
            <slot />
        </v-card-text>
    </v-card>
</template>

<style scoped>
.section-card {
    transition: box-shadow 0.2s ease;
}
.section-card:hover {
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}
.section-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
</style>
