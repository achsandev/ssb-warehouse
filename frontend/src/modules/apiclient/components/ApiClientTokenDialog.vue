<script lang="ts" setup>
import { computed, reactive, ref, watch } from 'vue'
import type { ValidationRule } from 'vuetify'
import { useTranslate } from '@/composables/useTranslate'
import { useMessageStore } from '@/stores/message'
// Icons
import MdiKeyVariant from '~icons/mdi/key-variant'
import MdiClose from '~icons/mdi/close'
import MdiContentCopy from '~icons/mdi/content-copy'
import MdiAlertOutline from '~icons/mdi/alert-outline'
import MdiCalendarClockOutline from '~icons/mdi/calendar-clock-outline'
import MdiTagOutline from '~icons/mdi/tag-outline'
import MdiContentSaveOutline from '~icons/mdi/content-save-outline'
import MdiCheckCircleOutline from '~icons/mdi/check-circle-outline'

// ─── Props / Emits ───────────────────────────────────────────────────────────
const props = withDefaults(
    defineProps<{
        modelValue: boolean
        clientName: string
        // Plaintext token yang baru di-generate. null = belum di-generate.
        generated: ApiClientGeneratedToken | null
        saving?: boolean
    }>(),
    { saving: false },
)

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'submit', payload: ApiClientGenerateTokenForm): void
    (e: 'close'): void
}>()

const t = useTranslate()
const message = useMessageStore()
const formRef = ref()

// Available abilities — match BE whitelist di GenerateTokenRequest::ALLOWED_ABILITIES.
const ABILITY_OPTIONS = [
    { value: 'items:read', label: 'items:read — daftar & detail items' },
    { value: 'item-requests:create', label: 'item-requests:create — buat item request' },
] as const

const form = reactive<ApiClientGenerateTokenForm>({
    name: '',
    abilities: ['*'],
    expires_at: null,
})

const grantAll = ref(true)

// ─── Validation ──────────────────────────────────────────────────────────────
const rules = {
    required: (v: unknown): true | string => {
        if (typeof v === 'string') return v.trim().length > 0 || t('fieldRequired', { field: '' })
        return !!v || t('fieldRequired', { field: '' })
    },
    futureDate: (v: string | null | undefined): true | string => {
        if (!v) return true
        const dt = new Date(v)
        if (isNaN(dt.getTime())) return t('apiClientExpiresInvalid')
        return dt.getTime() > Date.now() || t('apiClientExpiresMustFuture')
    },
}

const abilitiesEffective = computed<string[]>(() =>
    grantAll.value ? ['*'] : form.abilities.filter((a) => a !== '*'),
)

// ─── Handlers ────────────────────────────────────────────────────────────────
const handleSubmit = async () => {
    if (!formRef.value) return
    const { valid } = await formRef.value.validate()
    if (!valid) return

    emit('submit', {
        name: form.name.trim(),
        abilities: abilitiesEffective.value,
        expires_at: form.expires_at || null,
    })
}

const handleClose = () => {
    formRef.value?.resetValidation?.()
    emit('close')
}

const handleCopy = async (value: string) => {
    try {
        await navigator.clipboard.writeText(value)
        message.setMessage({
            text: t('apiClientTokenCopied'),
            timeout: 1500,
            color: 'success',
        })
    } catch {
        message.setMessage({
            text: t('apiClientTokenCopyFailed'),
            timeout: 2000,
            color: 'error',
        })
    }
}

// Reset form saat dialog ditutup.
watch(
    () => props.modelValue,
    (open) => {
        if (!open) {
            form.name = ''
            form.abilities = ['*']
            form.expires_at = null
            grantAll.value = true
            formRef.value?.resetValidation?.()
        }
    },
)
</script>

<template>
    <v-dialog
        :model-value="props.modelValue"
        max-width="560"
        scrollable
        persistent
        @update:model-value="(v) => emit('update:modelValue', v)"
    >
        <v-card class="rounded-lg" :loading="saving">
            <v-toolbar color="transparent" density="comfortable" flat>
                <template #prepend>
                    <v-icon :icon="MdiKeyVariant" color="primary" class="ms-2" />
                </template>
                <v-toolbar-title class="text-subtitle-1 font-weight-bold">
                    {{ t('apiClientGenerateToken') }} — {{ clientName }}
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

            <!-- ════ STATE 1: Generate form ════ -->
            <v-form
                v-if="!generated"
                ref="formRef"
                validate-on="input"
                @submit.prevent="handleSubmit"
            >
                <v-card-text class="pa-4 pa-sm-5">
                    <v-alert
                        :icon="MdiAlertOutline"
                        color="warning"
                        variant="tonal"
                        density="compact"
                        class="mb-4"
                    >
                        {{ t('apiClientReplaceWarning') }}
                    </v-alert>

                    <v-text-field
                        v-model="form.name"
                        :label="t('apiClientTokenName')"
                        :placeholder="`v1-${new Date().getFullYear()}`"
                        variant="outlined"
                        density="comfortable"
                        hide-details="auto"
                        :rules="[rules.required]"
                        :disabled="saving"
                        :prepend-inner-icon="MdiTagOutline"
                        maxlength="100"
                        autocomplete="off"
                        :persistent-hint="true"
                        :hint="t('apiClientTokenNameHint')"
                    />

                    <v-text-field
                        v-model="form.expires_at"
                        :label="t('apiClientExpiresAt')"
                        type="datetime-local"
                        variant="outlined"
                        density="comfortable"
                        hide-details="auto"
                        :rules="[rules.futureDate]"
                        :disabled="saving"
                        :prepend-inner-icon="MdiCalendarClockOutline"
                        :persistent-hint="true"
                        :hint="t('apiClientExpiresHint')"
                        clearable
                        class="mt-3"
                    />

                    <div class="abilities-block mt-4">
                        <div class="abilities-title">{{ t('apiClientAbilities') }}</div>
                        <v-radio-group
                            v-model="grantAll"
                            hide-details
                            density="compact"
                            class="mb-2"
                        >
                            <v-radio
                                :value="true"
                                :label="t('apiClientAbilityAll')"
                            />
                            <v-radio
                                :value="false"
                                :label="t('apiClientAbilitySpecific')"
                            />
                        </v-radio-group>

                        <div v-if="!grantAll" class="ability-options">
                            <v-checkbox
                                v-for="opt in ABILITY_OPTIONS"
                                :key="opt.value"
                                v-model="form.abilities"
                                :label="opt.label"
                                :value="opt.value"
                                hide-details
                                density="compact"
                            />
                        </div>
                    </div>
                </v-card-text>

                <v-divider />

                <v-card-actions class="pa-3 pa-sm-4">
                    <v-spacer />
                    <v-btn variant="text" :text="t('cancel')" :disabled="saving" @click="handleClose" />
                    <v-btn
                        type="submit"
                        color="primary"
                        variant="flat"
                        :prepend-icon="MdiContentSaveOutline"
                        :loading="saving"
                        :disabled="saving"
                    >
                        {{ t('apiClientGenerateToken') }}
                    </v-btn>
                </v-card-actions>
            </v-form>

            <!-- ════ STATE 2: Token plaintext display (one-time) ════ -->
            <template v-else>
                <v-card-text class="pa-4 pa-sm-5">
                    <v-alert
                        :icon="MdiCheckCircleOutline"
                        color="success"
                        variant="tonal"
                        density="compact"
                        class="mb-4"
                    >
                        <div class="text-caption font-weight-bold">{{ t('apiClientTokenGenerated') }}</div>
                        <div class="text-caption">{{ t('apiClientTokenOneTimeWarning') }}</div>
                    </v-alert>

                    <div class="token-block">
                        <div class="token-label">{{ t('apiClientPlainTextToken') }}</div>
                        <div class="token-value">
                            <code class="token-code">{{ generated.plain_text_token }}</code>
                            <v-btn
                                :icon="MdiContentCopy"
                                variant="tonal"
                                color="primary"
                                density="comfortable"
                                size="small"
                                :aria-label="t('apiClientCopyToken')"
                                @click="handleCopy(generated.plain_text_token)"
                            />
                        </div>
                    </div>

                    <v-row dense class="mt-3">
                        <v-col cols="12" sm="6">
                            <div class="meta-item">
                                <div class="meta-label">{{ t('apiClientTokenName') }}</div>
                                <div class="meta-value">{{ generated.name }}</div>
                            </div>
                        </v-col>
                        <v-col cols="12" sm="6">
                            <div class="meta-item">
                                <div class="meta-label">{{ t('apiClientExpiresAt') }}</div>
                                <div class="meta-value">
                                    {{ generated.expires_at ?? t('apiClientNeverExpires') }}
                                </div>
                            </div>
                        </v-col>
                        <v-col cols="12">
                            <div class="meta-item">
                                <div class="meta-label">{{ t('apiClientAbilities') }}</div>
                                <div class="meta-value">
                                    <v-chip
                                        v-for="ab in generated.abilities"
                                        :key="ab"
                                        size="x-small"
                                        variant="tonal"
                                        color="primary"
                                        class="me-1 font-weight-bold"
                                    >
                                        {{ ab }}
                                    </v-chip>
                                </div>
                            </div>
                        </v-col>
                    </v-row>
                </v-card-text>

                <v-divider />

                <v-card-actions class="pa-3 pa-sm-4">
                    <v-spacer />
                    <v-btn color="primary" variant="flat" @click="handleClose">
                        {{ t('done') }}
                    </v-btn>
                </v-card-actions>
            </template>
        </v-card>
    </v-dialog>
</template>

<style scoped>
.abilities-block {
    border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
    border-radius: 10px;
    padding: 12px;
    background: rgb(var(--v-theme-surface));
}
.abilities-title {
    font-weight: 600;
    font-size: 0.85rem;
    margin-bottom: 6px;
    color: rgba(var(--v-theme-on-surface), 0.92);
}
.ability-options {
    margin-top: 6px;
    padding-left: 4px;
}

.token-block {
    border: 1px dashed rgba(var(--v-theme-primary), 0.45);
    border-radius: 10px;
    padding: 12px;
    background: rgba(var(--v-theme-primary), 0.04);
}
.token-label {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    font-weight: 700;
    color: rgb(var(--v-theme-primary));
    margin-bottom: 6px;
}
.token-value {
    display: flex;
    align-items: center;
    gap: 8px;
}
.token-code {
    flex: 1;
    min-width: 0;
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 0.85rem;
    background: rgb(var(--v-theme-surface));
    padding: 8px 10px;
    border-radius: 6px;
    border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
    word-break: break-all;
    user-select: all;
}

.meta-item {
    padding: 8px 10px;
    border-radius: 8px;
    background: rgb(var(--v-theme-surface));
    border: 1px solid rgba(var(--v-theme-on-surface), 0.06);
}
.meta-label {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: rgba(var(--v-theme-on-surface), 0.55);
    font-weight: 500;
}
.meta-value {
    font-size: 0.85rem;
    font-weight: 600;
    color: rgba(var(--v-theme-on-surface), 0.92);
    margin-top: 2px;
    word-break: break-word;
}
</style>
