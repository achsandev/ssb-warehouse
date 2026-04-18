<script lang="ts" setup>
import { computed, ref, watch } from 'vue'
import { useField, useForm } from 'vee-validate'
// Composables
import { useTranslate } from '@/composables/useTranslate'
// Validations
import { tankSchema } from '@/validations'
// Icons
import MdiWaterOutline from '~icons/mdi/water-outline'
import MdiTagOutline from '~icons/mdi/tag-outline'
import MdiTextBoxOutline from '~icons/mdi/text-box-outline'
import MdiInformationOutline from '~icons/mdi/information-outline'
import MdiClose from '~icons/mdi/close'
import MdiContentSaveOutline from '~icons/mdi/content-save-outline'

// ─── Constants ───────────────────────────────────────────────────────────────
const NAME_MAX = 100
const INFO_MAX = 500

// ─── Props / Emits ───────────────────────────────────────────────────────────
const props = withDefaults(
    defineProps<{
        modelValue: boolean
        title: string
        data: TankForm
        saving?: boolean
    }>(),
    { saving: false },
)

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'submit', values: TankForm): void
    (e: 'close'): void
}>()

const t = useTranslate()
const formRef = ref()

// ─── Form state ──────────────────────────────────────────────────────────────
const { handleSubmit, setValues, resetForm, errors } = useForm<TankForm>({
    validationSchema: tankSchema(t),
})

setValues({
    name:            props.data.name ?? '',
    additional_info: props.data.additional_info ?? '',
})

const { value: name } = useField<string>('name')
const { value: additional_info } = useField<string>('additional_info')

const nameCounter = computed(() => `${(name.value ?? '').length} / ${NAME_MAX}`)
const infoCounter = computed(() => `${(additional_info.value ?? '').length} / ${INFO_MAX}`)

// ─── Handlers ────────────────────────────────────────────────────────────────
/**
 * Submit dengan trim — menghindari duplikasi data karena trailing whitespace.
 */
const submit = handleSubmit((values: TankForm) => {
    emit('submit', {
        ...values,
        name:            (values.name ?? '').trim(),
        additional_info: (values.additional_info ?? '').trim(),
    })
})

const handleClose = () => {
    formRef.value?.resetValidation?.()
    resetForm()
    emit('close')
}

watch(
    () => props.modelValue,
    (open) => {
        if (!open) formRef.value?.resetValidation?.()
    },
)
</script>

<template>
    <v-dialog
        :model-value="props.modelValue"
        max-width="520"
        scrollable
        :persistent="saving"
        @update:model-value="(value) => emit('update:modelValue', value)"
    >
        <v-card class="rounded-lg tank-form-card" :loading="saving">
            <!-- Header -->
            <v-toolbar color="transparent" density="comfortable" flat>
                <template #prepend>
                    <v-icon :icon="MdiWaterOutline" color="primary" class="ms-2" />
                </template>
                <v-toolbar-title class="text-subtitle-1 font-weight-bold">
                    {{ props.title }}
                </v-toolbar-title>
                <template #append>
                    <v-btn
                        :icon="MdiClose"
                        variant="text"
                        density="comfortable"
                        :disabled="saving"
                        :aria-label="t('close')"
                        @click="handleClose"
                    />
                </template>
            </v-toolbar>

            <v-form ref="formRef" validate-on="input" @submit.prevent="submit">
                <v-card-text class="pa-4 pa-sm-5">
                    <section class="form-section">
                        <header class="section-head">
                            <v-icon :icon="MdiInformationOutline" size="16" />
                            <span>{{ t('generalInfo') }}</span>
                        </header>
                        <v-divider class="mb-3" />

                        <v-row dense>
                            <v-col cols="12">
                                <v-text-field
                                    :label="t('name')"
                                    v-model="name"
                                    variant="outlined"
                                    density="comfortable"
                                    hide-details="auto"
                                    :error-messages="errors.name"
                                    :disabled="saving"
                                    :prepend-inner-icon="MdiTagOutline"
                                    :maxlength="NAME_MAX"
                                    :persistent-hint="true"
                                    :hint="nameCounter"
                                    autocomplete="off"
                                />
                            </v-col>

                            <v-col cols="12">
                                <v-textarea
                                    :label="t('additionalInfo')"
                                    v-model="additional_info"
                                    variant="outlined"
                                    density="comfortable"
                                    rows="3"
                                    auto-grow
                                    hide-details="auto"
                                    :error-messages="errors.additional_info"
                                    :disabled="saving"
                                    :prepend-inner-icon="MdiTextBoxOutline"
                                    :maxlength="INFO_MAX"
                                    :persistent-hint="true"
                                    :hint="infoCounter"
                                    autocomplete="off"
                                />
                            </v-col>
                        </v-row>
                    </section>
                </v-card-text>

                <v-divider />

                <v-card-actions class="pa-3 pa-sm-4">
                    <v-spacer />
                    <v-btn
                        variant="text"
                        :text="t('cancel')"
                        :disabled="saving"
                        @click="handleClose"
                    />
                    <v-btn
                        type="submit"
                        color="success"
                        variant="flat"
                        :prepend-icon="MdiContentSaveOutline"
                        :loading="saving"
                        :disabled="saving"
                    >
                        {{ t('save') }}
                    </v-btn>
                </v-card-actions>
            </v-form>
        </v-card>
    </v-dialog>
</template>

<style scoped>
.tank-form-card {
    overflow: hidden;
}

.form-section {
    background: rgba(var(--v-theme-on-surface), 0.02);
    border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
    border-radius: 12px;
    padding: 14px;
}
.section-head {
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 600;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: rgba(var(--v-theme-on-surface), 0.8);
    margin-bottom: 6px;
}

:deep(.v-messages__message) {
    opacity: 0.75;
}
</style>
