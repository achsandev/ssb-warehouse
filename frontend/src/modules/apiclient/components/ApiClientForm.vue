<script lang="ts" setup>
import { computed, ref, watch } from 'vue'
import type { ValidationRule } from 'vuetify'
import { useTranslate } from '@/composables/useTranslate'
// Icons
import MdiApi from '~icons/mdi/api'
import MdiTagOutline from '~icons/mdi/tag-outline'
import MdiLinkVariant from '~icons/mdi/link-variant'
import MdiTextBoxOutline from '~icons/mdi/text-box-outline'
import MdiInformationOutline from '~icons/mdi/information-outline'
import MdiToggleSwitchOutline from '~icons/mdi/toggle-switch-outline'
import MdiClose from '~icons/mdi/close'
import MdiContentSaveOutline from '~icons/mdi/content-save-outline'

// ─── Constants ───────────────────────────────────────────────────────────────
const NAME_MAX = 150
const URL_MAX = 512
const DESC_MAX = 1000

// ─── Props / Emits ───────────────────────────────────────────────────────────
const props = withDefaults(
    defineProps<{
        modelValue: boolean
        title: string
        data: ApiClientForm
        saving?: boolean
    }>(),
    { saving: false },
)

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'submit'): void
    (e: 'close'): void
}>()

const t = useTranslate()
const formRef = ref()

// ─── Validation rules ────────────────────────────────────────────────────────
const rules = {
    required: (v: unknown): true | string => {
        if (typeof v === 'string') return v.trim().length > 0 || t('fieldRequired', { field: '' })
        return !!v || t('fieldRequired', { field: '' })
    },
    maxLen: (max: number) => (v: string | null | undefined): true | string =>
        !v || v.length <= max || t('fieldMaxChar', { field: '', length: max }),
    url: (v: string | null | undefined): true | string => {
        if (!v) return true
        try {
            const url = new URL(v)
            return ['http:', 'https:'].includes(url.protocol) || t('apiClientUrlMustHttp')
        } catch {
            return t('apiClientUrlInvalid')
        }
    },
}

const nameCounter = computed(() => `${(props.data.name ?? '').length} / ${NAME_MAX}`)
const urlCounter = computed(() => `${(props.data.url ?? '').length} / ${URL_MAX}`)
const descCounter = computed(() => `${(props.data.description ?? '').length} / ${DESC_MAX}`)

// ─── Handlers ────────────────────────────────────────────────────────────────
const commitString = (field: 'name' | 'url' | 'description', value: unknown) => {
    const raw = typeof value === 'string' ? value : ''
    ;(props.data as any)[field] = raw
}

const handleSubmit = async () => {
    if (!formRef.value) return
    const { valid } = await formRef.value.validate()
    if (!valid) return

    props.data.name = (props.data.name ?? '').toString().trim()
    props.data.url = (props.data.url ?? '').toString().trim()
    props.data.description = (props.data.description ?? '').toString().trim() || null

    emit('submit')
}

const handleClose = () => {
    formRef.value?.resetValidation?.()
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
        max-width="620"
        scrollable
        :persistent="saving"
        @update:model-value="(value) => emit('update:modelValue', value)"
    >
        <v-card class="rounded-lg apiclient-form-card" :loading="saving">
            <!-- Header -->
            <v-toolbar color="transparent" density="comfortable" flat>
                <template #prepend>
                    <v-icon :icon="MdiApi" color="primary" class="ms-2" />
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

            <v-form ref="formRef" validate-on="input" @submit.prevent="handleSubmit">
                <v-card-text class="pa-4 pa-sm-5">
                    <!-- ════ Informasi Umum ════ -->
                    <section class="form-section mb-4">
                        <header class="section-head">
                            <v-icon :icon="MdiInformationOutline" size="16" />
                            <span>{{ t('generalInfo') }}</span>
                        </header>
                        <v-divider class="mb-3" />
                        <v-row dense>
                            <v-col cols="12">
                                <v-text-field
                                    :label="t('apiClientName')"
                                    :model-value="props.data.name"
                                    variant="outlined"
                                    density="comfortable"
                                    hide-details="auto"
                                    :rules="[rules.required, rules.maxLen(NAME_MAX)]"
                                    :disabled="saving"
                                    :prepend-inner-icon="MdiTagOutline"
                                    :maxlength="NAME_MAX"
                                    persistent-hint
                                    :hint="nameCounter"
                                    autocomplete="off"
                                    @update:model-value="(v) => commitString('name', v)"
                                />
                            </v-col>
                            <v-col cols="12">
                                <v-text-field
                                    :label="t('apiClientUrl')"
                                    :model-value="props.data.url"
                                    variant="outlined"
                                    density="comfortable"
                                    hide-details="auto"
                                    :rules="[rules.required, rules.maxLen(URL_MAX), rules.url]"
                                    :disabled="saving"
                                    :prepend-inner-icon="MdiLinkVariant"
                                    :maxlength="URL_MAX"
                                    placeholder="https://partner.example.com"
                                    persistent-hint
                                    :hint="urlCounter"
                                    autocomplete="off"
                                    @update:model-value="(v) => commitString('url', v)"
                                />
                            </v-col>
                            <v-col cols="12">
                                <v-textarea
                                    :label="t('description')"
                                    :model-value="props.data.description"
                                    variant="outlined"
                                    density="comfortable"
                                    hide-details="auto"
                                    rows="2"
                                    auto-grow
                                    :disabled="saving"
                                    :rules="[rules.maxLen(DESC_MAX)]"
                                    :prepend-inner-icon="MdiTextBoxOutline"
                                    :maxlength="DESC_MAX"
                                    persistent-hint
                                    :hint="descCounter"
                                    autocomplete="off"
                                    @update:model-value="(v) => commitString('description', v)"
                                />
                            </v-col>
                        </v-row>
                    </section>

                    <!-- ════ Status & Origin ════ -->
                    <section class="form-section">
                        <header class="section-head">
                            <v-icon :icon="MdiToggleSwitchOutline" size="16" />
                            <span>{{ t('apiClientSettings') }}</span>
                        </header>
                        <v-divider class="mb-3" />

                        <div class="switch-row">
                            <div class="switch-info">
                                <div class="switch-title">{{ t('activeStatus') }}</div>
                                <div class="switch-hint text-caption text-medium-emphasis">
                                    {{ t('apiClientActiveHint') }}
                                </div>
                            </div>
                            <v-switch
                                :model-value="!!props.data.is_active"
                                color="primary"
                                hide-details
                                inset
                                :disabled="saving"
                                :aria-label="t('activeStatus')"
                                @update:model-value="(v) => (props.data.is_active = !!v)"
                            />
                        </div>

                        <div class="switch-row mt-2">
                            <div class="switch-info">
                                <div class="switch-title">{{ t('apiClientEnforceOrigin') }}</div>
                                <div class="switch-hint text-caption text-medium-emphasis">
                                    {{ t('apiClientEnforceOriginHint') }}
                                </div>
                            </div>
                            <v-switch
                                :model-value="!!props.data.enforce_origin"
                                color="primary"
                                hide-details
                                inset
                                :disabled="saving"
                                :aria-label="t('apiClientEnforceOrigin')"
                                @update:model-value="(v) => (props.data.enforce_origin = !!v)"
                            />
                        </div>
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
.apiclient-form-card {
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

.switch-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
    border-radius: 10px;
    background: rgb(var(--v-theme-surface));
    transition: border-color 0.15s ease;
}
.switch-row:hover {
    border-color: rgba(var(--v-theme-primary), 0.35);
}
.switch-info {
    flex: 1;
    min-width: 0;
}
.switch-title {
    font-weight: 600;
    font-size: 0.92rem;
    color: rgba(var(--v-theme-on-surface), 0.92);
}
.switch-hint {
    margin-top: 2px;
    line-height: 1.4;
}
.switch-row :deep(.v-switch) {
    margin: 0;
    padding: 0;
    flex-shrink: 0;
}

@media (max-width: 500px) {
    .switch-row {
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
    }
    .switch-row :deep(.v-switch) {
        align-self: flex-end;
    }
}
</style>
