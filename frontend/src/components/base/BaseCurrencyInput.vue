<script lang="ts" setup>
// Import Helpers
import { numberOnly } from '@/helpers/numberOnly'

const props = withDefaults(defineProps<{
    loading?: boolean,
    disabled?: boolean,
    readonly?: boolean,
    label: string,
    errorMessages?: string | readonly string[] | null | undefined,
    modelValue: string | undefined | unknown
}>(), {
    loading: false,
    disabled: false,
    readonly: false,
})

const emit = defineEmits<{
    (e: 'update:modelValue', value: number): void
}>()

const formatRupiah = (value: number | string | unknown): string => {
    if (!value) return ''

    const num = Number(String(value).replace(/\D/g, ''))
    const format = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num)
    
    return format
}

const unformatRupiah = (value: string): number => {
    const cleanValue = String(value).replace(/\D/g, '')
    return Number(cleanValue)
}

const handleInput = (value: string) => {
    const raw = unformatRupiah(value)
    const formatted = formatRupiah(raw)

    const input = document.activeElement as HTMLInputElement
    if (input) input.value = formatted

    emit('update:modelValue', raw)
}
</script>

<template>
    <v-text-field
        :model-value="formatRupiah(props.modelValue ?? 0)"
        :loading="props.loading"
        :disabled="props.disabled"
        :readonly="props.readonly"
        :label="props.label"
        variant="outlined"
        density="comfortable"
        :error-messages="errorMessages"
        autocomplete="off"
        hide-details="auto"
        @update:model-value="handleInput"
        @keypress="numberOnly"
    />
</template>

<style scoped></style>