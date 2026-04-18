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
    (e: 'approve', notes: string): void,
    (e: 'close'): void
}>()

const notes = ref('')

const handleApprove = () => {
    emit('approve', notes.value)
    notes.value = ''
}

const handleClose = () => {
    notes.value = ''
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
            <v-card-title>{{ t('confirmApproveDialogTitle') }}</v-card-title>
            <v-card-text>
                <p class="mb-3">{{ t('confirmApproveDialogText') }}</p>
                <v-textarea
                    v-if="props.withNotes"
                    v-model="notes"
                    label="Catatan (opsional)"
                    rows="3"
                    variant="outlined"
                    density="compact"
                    hide-details
                />
            </v-card-text>
            <v-card-actions>
                <v-spacer />
                <v-btn :text="t('cancel')" @click="handleClose" />
                <v-btn :text="t('approve')" color="success" @click="handleApprove" />
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