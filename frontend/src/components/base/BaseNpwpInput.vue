<script lang="ts" setup>
// Import Helpers
import { numberOnly } from '@/helpers/numberOnly'

const props = defineProps<{
    modelValue: string|undefined|unknown,
    label: string,
    errorMessages?: string
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void
}>()

const formatNpwp = (value: string) => {
    const onlyNumbers = value.replace(/\D/g, '')
    let formatted = ''

    if (onlyNumbers.length > 0) formatted += onlyNumbers.substring(0, 2)
    if (onlyNumbers.length > 2) formatted += '.' + onlyNumbers.substring(2, 5)
    if (onlyNumbers.length > 5) formatted += '.' + onlyNumbers.substring(5, 8)
    if (onlyNumbers.length > 8) formatted += '-' + onlyNumbers.substring(8, 9)
    if (onlyNumbers.length > 9) formatted += '.' + onlyNumbers.substring(9, 12)
    if (onlyNumbers.length > 12) formatted += '.' + onlyNumbers.substring(12, 15)
    if (onlyNumbers.length > 15) formatted += '-' + onlyNumbers.substring(15, 22)

    return formatted
}

const handleInput = (value: string) => {
    const formatted = formatNpwp(value)

    emit('update:modelValue', formatted)
}
</script>

<template>
    <v-text-field
        :model-value="props.modelValue"
        :label="label"
        variant="outlined"
        density="comfortable"
        :error-messages="errorMessages"
        autocomplete="off"
        @update:model-value="handleInput"
        @keypress="numberOnly"
    />
</template>

<style scoped></style>