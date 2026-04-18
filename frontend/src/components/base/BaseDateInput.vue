<script lang="ts" setup>
import { computed, ref } from 'vue'
import SolarCalendarLinear from '~icons/solar/calendar-linear'

const props = defineProps<{
    modelValue: string | Date | null | undefined,
    label?: string,
    readonly?: boolean,
    errorMessages?: string | string[],
    min?: string | Date | null,
    max?: string | Date | null,
}>()

const toDate = (v: string | Date | null | undefined): Date | undefined => {
    if (!v) return undefined
    return typeof v === 'string' ? new Date(v) : v
}

const minDate = computed(() => toDate(props.min))
const maxDate = computed(() => toDate(props.max))

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void
}>()

const date = ref<Date | null | undefined>(
    typeof props.modelValue === 'string'
        ? new Date(props.modelValue)
        : props.modelValue
)

const formatDate = (date: Date | null) => {
    if (!date) return ''
    const y = date.getFullYear()
    const m = String(date.getMonth() + 1).padStart(2, '0')
    const d = String(date.getDate()).padStart(2, '0')
    return `${y}-${m}-${d}`
}

const handleChangeDate = (value: Date | null) => {
    date.value = value
    const formatted = formatDate(value)
    emit('update:modelValue', formatted)
}
</script>

<template>
    <v-text-field
        :model-value="formatDate(date ?? null)"
        :label="props.label || 'YYYY-MM-DD'"
        :error-messages="props.errorMessages"
        variant="outlined"
        density="comfortable"
        :append-inner-icon="SolarCalendarLinear"
        autocomplete="off"
        readonly
    >
        <template v-if="!props.readonly">
            <v-menu
                activator="parent"
                :close-on-content-click="false"
            >
                <v-date-picker
                    :model-value="date"
                    :min="minDate"
                    :max="maxDate"
                    hide-header
                    @update:model-value="handleChangeDate"
                />
            </v-menu>
        </template>
    </v-text-field>
</template>

<style scoped></style>