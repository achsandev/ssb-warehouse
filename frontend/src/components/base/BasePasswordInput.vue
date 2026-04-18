<script lang="ts" setup>
import { ref } from 'vue'
// Import Icons
import CodiconEye from '~icons/codicon/eye'
import CodiconEyeClosed from '~icons/codicon/eye-closed'

const props = withDefaults(defineProps<{
    modelValue?: string
    label: string
    variant?: 'outlined' | 'plain' | 'underlined' | 'filled' | 'solo' | 'solo-inverted' | 'solo-filled'
    density?: 'default' | 'comfortable' | 'compact'
}>(), {
    variant: 'outlined',
    density: 'compact'
})

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void
}>()

const updateValue = (value: string) => {
    emit('update:modelValue', value)
}

const isVisible = ref<boolean>(false)
</script>

<template>
    <v-text-field
        :model-value="props.modelValue"
        :type="isVisible ? 'text' : 'password'"
        :label="props.label"
        :variant="props.variant"
        :density="props.density"
        :append-inner-icon="isVisible ? CodiconEye : CodiconEyeClosed"
        autocomplete="current-password"
        @update:model-value="updateValue"
        @click:append-inner="() => isVisible = !isVisible"
    />
</template>

<style scoped></style>