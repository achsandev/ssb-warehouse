<script lang="ts" setup>
const props = withDefaults(defineProps<{
    loading?: boolean,
    label: string,
    items: SelectItem[],
    modelValue: number | string | object | number[] | string[] | object[] | null | unknown,
    errorMessages?: string | readonly string[] | null | undefined,
    chips?: boolean,
    multiple?: boolean,
    disabled?: boolean,
    clearable?: boolean
}>(), {
    chips: false,
    multiple: false,
    disabled: false,
    clearable: false
})

const emit = defineEmits<{
    (e: 'update:modelValue', value: any): void
}>()

const handleSelected = (value: any) => {
    emit('update:modelValue', value)
}
</script>

<template>
    <v-autocomplete
        :model-value="props.modelValue"
        :loading="props.loading"
        :items="props.items"
        :label="props.label"
        item-title="title"
        item-value="value"
        variant="outlined"
        density="comfortable"
        :error-messages="props.errorMessages"
        @update:model-value="handleSelected"
        autocomplete="off"
        :chips="props.chips"
        :multiple="props.multiple"
        :disabled="props.disabled"
        :clearable="props.clearable"
        hide-details="auto"
    />
</template>

<style scoped>
</style>