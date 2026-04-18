<script lang="ts" setup>
import { ref } from 'vue'
// Import Composables
import { useTranslate } from '@/composables/useTranslate'

const t = useTranslate()

const props = defineProps<{
    modelValue: boolean,
    withNotes?: boolean,
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void,
    (e: 'reject', notes: string): void,
    (e: 'close'): void
}>()

const notes = ref('')
const notesError = ref('')

const handleReject = () => {
    if (props.withNotes && !notes.value.trim()) {
        notesError.value = t('fieldRequired', { field: t('rejectReason') })
        return
    }
    emit('reject', notes.value.trim())
    notes.value = ''
    notesError.value = ''
}

const handleClose = () => {
    notes.value = ''
    notesError.value = ''
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
            <v-card-title>{{ t('confirmRejectDialogTitle') }}</v-card-title>
            <v-card-text>
                <p class="mb-3">{{ t('confirmRejectDialogText') }}</p>
                <v-textarea
                    v-if="props.withNotes"
                    v-model="notes"
                    :label="t('rejectReason')"
                    :error-messages="notesError"
                    rows="3"
                    variant="outlined"
                    density="compact"
                    hide-details="auto"
                    @update:model-value="notesError = ''"
                />
            </v-card-text>
            <v-card-actions>
                <v-spacer />
                <v-btn :text="t('cancel')" @click="handleClose" />
                <v-btn :text="t('reject')" color="error" @click="handleReject" />
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