<script lang="ts" setup>
// Import Icons
import FlowbiteRefreshOutline from '~icons/flowbite/refresh-outline'

const props = defineProps<{
    loading: boolean,
    disabled: boolean
}>()

const emit = defineEmits<{
    (e: 'click'): void
}>()
</script>

<template>
    <v-btn
        :loading="props.loading"
        :icon="FlowbiteRefreshOutline"
        variant="outlined"
        density="comfortable"
        class="ml-3"
        color="grey-darken-1"
        :disabled="props.disabled"
        @click="emit('click')"
    />
</template>

<style scoped></style>