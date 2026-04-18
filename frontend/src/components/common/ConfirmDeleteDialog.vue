<script lang="ts" setup>
// Import Composables
import { useTranslate } from '@/composables/useTranslate'

const t = useTranslate()

const props = defineProps<{
    modelValue: boolean,
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void,
    (e: 'delete'): void,
    (e: 'close'): void
}>()

const handleDelete = () => {
    emit('delete')
}

const handleClose = () => {
    emit('close')
}
</script>

<template>
    <v-dialog
        :model-value="props.modelValue"
        max-width="500"
        @update:model-value="value => emit('update:modelValue', value)"
    >
        <v-card class="rounded-lg">
            <v-card-title>{{ t('confirmDeleteDialogTitle') }}</v-card-title>
            <v-card-text>{{ t('confirmDeleteDialogText') }}</v-card-text>
            <v-card-actions>
                <v-spacer />
                <v-btn :text="t('cancel')" @click="handleClose" />
                <v-btn :text="t('delete')" color="error" @click="handleDelete" />
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<style scoped>
.custom {
    z-index: 1007 !important;
}

.custom-overlay {
    z-index: 1006 !important;
}
</style>