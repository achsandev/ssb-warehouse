<script lang="ts" setup>
/**
 * Reusable dialog untuk action approve/reject/cancel transfer.
 * - approve: konfirmasi sederhana
 * - reject: notes wajib (min 3 char)
 * - cancel: notes opsional
 */
import { computed, ref, watch } from 'vue'
import { useTranslate } from '@/composables/useTranslate'
// Icons
import MdiClose from '~icons/mdi/close'
import MdiCheckCircleOutline from '~icons/mdi/check-circle-outline'
import MdiCloseCircleOutline from '~icons/mdi/close-circle-outline'
import MdiCancel from '~icons/mdi/cancel'

type ActionMode = 'approve' | 'reject' | 'cancel'

const props = defineProps<{
    modelValue: boolean
    mode: ActionMode
    transferNumber?: string
    loading?: boolean
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'confirm', notes: string): void
    (e: 'close'): void
}>()

const t = useTranslate()

const notes = ref('')
const notesError = ref('')

watch(() => props.modelValue, (open) => {
    if (open) {
        notes.value = ''
        notesError.value = ''
    }
})

// ─── Mode-specific config ────────────────────────────────────────────────────
const config = computed(() => {
    switch (props.mode) {
        case 'approve':
            return {
                title: t('approveTransferTitle'),
                color: 'success' as const,
                icon: MdiCheckCircleOutline,
                hint: t('approveTransferConfirm'),
                buttonLabel: t('approve') || 'Setujui',
                showNotes: false,
                notesRequired: false,
                notesLabel: '',
            }
        case 'reject':
            return {
                title: t('rejectTransferTitle'),
                color: 'error' as const,
                icon: MdiCloseCircleOutline,
                hint: t('rejectReasonHint'),
                buttonLabel: t('reject') || 'Tolak',
                showNotes: true,
                notesRequired: true,
                notesLabel: t('rejectReason'),
            }
        case 'cancel':
            return {
                title: t('cancelTransferTitle'),
                color: 'warning' as const,
                icon: MdiCancel,
                hint: t('cancelTransferConfirm'),
                buttonLabel: t('cancel') || 'Batalkan',
                showNotes: true,
                notesRequired: false,
                notesLabel: `${t('notes')} (${t('optional')})`,
            }
    }
})

// ─── Handlers ────────────────────────────────────────────────────────────────
const handleConfirm = () => {
    if (config.value.notesRequired && (!notes.value || notes.value.trim().length < 3)) {
        notesError.value = t('rejectReasonHint')
        return
    }
    notesError.value = ''
    emit('confirm', notes.value.trim())
}

const handleClose = () => {
    emit('update:modelValue', false)
    emit('close')
}
</script>

<template>
    <v-dialog
        :model-value="modelValue"
        max-width="520"
        :persistent="loading"
        @update:model-value="(v) => !v && handleClose()"
    >
        <v-card class="rounded-lg" :loading="loading">
            <v-card-title class="d-flex align-center ga-2 px-5 py-4">
                <v-icon :icon="config.icon" :color="config.color" />
                <span class="text-h6 font-weight-semibold">{{ config.title }}</span>
                <v-spacer />
                <v-btn
                    :icon="MdiClose"
                    variant="text"
                    size="small"
                    :disabled="loading"
                    @click="handleClose"
                />
            </v-card-title>

            <v-divider />

            <v-card-text class="pa-5">
                <v-alert
                    :type="config.color"
                    variant="tonal"
                    density="compact"
                    class="mb-4"
                >
                    <div v-if="transferNumber" class="text-caption mb-1">
                        <strong>{{ t('transferNumber') || 'No. Transfer' }}:</strong> {{ transferNumber }}
                    </div>
                    <div class="text-body-2">{{ config.hint }}</div>
                </v-alert>

                <v-textarea
                    v-if="config.showNotes"
                    v-model="notes"
                    :label="config.notesLabel"
                    :error-messages="notesError"
                    variant="outlined"
                    density="comfortable"
                    rows="3"
                    auto-grow
                    counter="500"
                    maxlength="500"
                    autofocus
                />
            </v-card-text>

            <v-divider />

            <v-card-actions class="pa-4 justify-end ga-2">
                <v-btn variant="tonal" :disabled="loading" @click="handleClose">
                    {{ t('cancel') }}
                </v-btn>
                <v-btn
                    :color="config.color"
                    variant="elevated"
                    :loading="loading"
                    :prepend-icon="config.icon"
                    @click="handleConfirm"
                >
                    {{ config.buttonLabel }}
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>
