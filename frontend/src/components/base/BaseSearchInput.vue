<script lang="ts" setup>
// Import Icons
import BitcoinIconsSearchOutline from '~icons/bitcoin-icons/search-outline'
// Import Plugins
import { useDebounceFn } from '@vueuse/core'
// Import Composables
import { useTranslate } from '@/composables/useTranslate'

const t = useTranslate()

const props = withDefaults(defineProps<{
    loading: boolean,
    modelValue: string
}>(), {
    modelValue: ''
})

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void
}>()

const handleInput = useDebounceFn((value: string) => {
    emit('update:modelValue', value)
}, 1000)
</script>

<template>
    <v-text-field
        :loading="props.loading"
        :model-value="props.modelValue"
        class="input-search"
        :label="t('search')"
        variant="outlined"
        density="compact"
        rounded="lg"
        :append-inner-icon="BitcoinIconsSearchOutline"
        clearable
        hide-details
        @update:model-value="handleInput"
    />
</template>

<style scoped>
.input-search {
    width: 100%;
    margin-top: 5px;
}

@media (min-width: 600px) {
    .input-search {
        max-width: 380px;
        min-width: 380px;
    }
}
</style>