<script lang="ts" setup>
import { computed, ref, watch } from 'vue'
import { useField, useForm } from 'vee-validate'
// Composables
import { useTranslate } from '@/composables/useTranslate'
// Validations
import { warehouseSchema } from '@/validations'
// Icons
import MdiWarehouse from '~icons/mdi/warehouse'
import MdiMapMarkerOutline from '~icons/mdi/map-marker-outline'
import MdiTextBoxOutline from '~icons/mdi/text-box-outline'
import MdiInformationOutline from '~icons/mdi/information-outline'
import MdiTagOutline from '~icons/mdi/tag-outline'
import MdiClose from '~icons/mdi/close'
import MdiContentSaveOutline from '~icons/mdi/content-save-outline'

// ─── Constants ───────────────────────────────────────────────────────────────
const NAME_MAX = 120
const ADDRESS_MAX = 255
const INFO_MAX = 500

// ─── Props / Emits ───────────────────────────────────────────────────────────
const props = withDefaults(
    defineProps<{
        modelValue: boolean
        title: string
        data: WarehouseForm
        saving?: boolean
    }>(),
    { saving: false },
)

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'submit', values: WarehouseForm): void
    (e: 'close'): void
}>()

const t = useTranslate()
const formRef = ref()

// ─── Form state ──────────────────────────────────────────────────────────────
const { handleSubmit, setValues, resetForm, errors } = useForm<WarehouseForm>({
    validationSchema: warehouseSchema(t),
})

setValues({
    name:            props.data.name ?? '',
    address:         props.data.address ?? '',
    additional_info: props.data.additional_info ?? '',
})

const { value: name } = useField<string>('name')
const { value: address } = useField<string>('address')
const { value: additional_info } = useField<string>('additional_info')

// ─── Counter string (dirender di hint supaya tidak menambah error space) ────
const nameCounter = computed(() => `${(name.value ?? '').length} / ${NAME_MAX}`)
const addressCounter = computed(() => `${(address.value ?? '').length} / ${ADDRESS_MAX}`)
const infoCounter = computed(() => `${(additional_info.value ?? '').length} / ${INFO_MAX}`)

// ─── Handlers ────────────────────────────────────────────────────────────────
/**
 * Submit dengan trim whitespace akhir. Menghindari duplikasi data yang
 * disebabkan trailing space (mis. "Gudang A" vs "Gudang A ").
 */
const submit = handleSubmit((values: WarehouseForm) => {
    emit('submit', {
        name:            (values.name ?? '').trim(),
        address:         (values.address ?? '').trim(),
        additional_info: (values.additional_info ?? '').trim() || undefined,
    })
})

const handleClose = () => {
    formRef.value?.resetValidation?.()
    resetForm()
    emit('close')
}

// Reset validasi saat dialog ditutup — jaga agar error lama tidak tampil
// ketika dialog dibuka ulang untuk row berbeda.
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
        max-width="560"
        scrollable
        :persistent="saving"
        @update:model-value="(value) => emit('update:modelValue', value)"
    >
        <v-card class="rounded-lg warehouse-form-card" :loading="saving">
            <!-- Header -->
            <v-toolbar color="transparent" density="comfortable" flat>
                <template #prepend>
                    <v-icon :icon="MdiWarehouse" color="primary" class="ms-2" />
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

            <v-form
                ref="formRef"
                validate-on="input"
                @submit.prevent="submit"
            >
                <v-card-text class="pa-4 pa-sm-5">
                    <!-- ════ Informasi Umum ════ -->
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
                                <v-text-field
                                    :label="t('address')"
                                    v-model="address"
                                    variant="outlined"
                                    density="comfortable"
                                    hide-details="auto"
                                    :error-messages="errors.address"
                                    :disabled="saving"
                                    :prepend-inner-icon="MdiMapMarkerOutline"
                                    :maxlength="ADDRESS_MAX"
                                    :persistent-hint="true"
                                    :hint="addressCounter"
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
.warehouse-form-card {
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

/* Redam warna hint supaya counter terasa sekunder (bukan kompetisi dengan label). */
:deep(.v-messages__message) {
    opacity: 0.75;
}
</style>
