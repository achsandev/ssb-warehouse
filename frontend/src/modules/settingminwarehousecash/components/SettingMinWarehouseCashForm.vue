<script lang="ts" setup>
import { computed, ref, watch } from 'vue'
import type { ValidationRule } from 'vuetify'
import BaseCurrencyInput from '@/components/base/BaseCurrencyInput.vue'
import { useTranslate } from '@/composables/useTranslate'
import { formatRupiah } from '@/utils/currency'
// Icons
import MdiCashSync from '~icons/mdi/cash-sync'
import MdiCashLock from '~icons/mdi/cash-lock'
import MdiWarehouse from '~icons/mdi/warehouse'
import MdiInformationOutline from '~icons/mdi/information-outline'
import MdiAlertCircleOutline from '~icons/mdi/alert-circle-outline'
import MdiClose from '~icons/mdi/close'
import MdiContentSaveOutline from '~icons/mdi/content-save-outline'

// ─── Props / Emits ───────────────────────────────────────────────────────────
const props = withDefaults(
    defineProps<{
        modelValue: boolean
        title: string
        warehouse: SettingMinWarehouseCashList | null
        saving?: boolean
    }>(),
    { saving: false },
)

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'submit', value: number | null): void
    (e: 'close'): void
}>()

const t = useTranslate()
const formRef = ref()
const minCash = ref<number | null>(null)
const clearThreshold = ref<boolean>(false)

// ─── Validation ──────────────────────────────────────────────────────────────
const rules: Record<string, ValidationRule> = {
    nonNegative: (v: unknown): true | string => {
        if (v === null || v === undefined || v === '') return true
        const n = Number(v)
        if (!Number.isFinite(n)) return t('settingMinCashInvalid')
        return n >= 0 || t('settingMinCashMustNonNegative')
    },
}

// ─── Derived ────────────────────────────────────────────────────────────────
const previewBelowMin = computed<boolean>(() => {
    if (clearThreshold.value) return false
    if (minCash.value === null) return false
    const balance = props.warehouse?.cash_balance ?? 0
    return balance < (minCash.value ?? 0)
})

const balanceFormatted = computed<string>(() =>
    formatRupiah(props.warehouse?.cash_balance ?? 0),
)

const newMinFormatted = computed<string>(() =>
    clearThreshold.value || minCash.value === null
        ? '—'
        : formatRupiah(minCash.value ?? 0),
)

// ─── Handlers ────────────────────────────────────────────────────────────────
const handleSubmit = async () => {
    if (!formRef.value) return
    const { valid } = await formRef.value.validate()
    if (!valid) return

    const payload = clearThreshold.value ? null : (minCash.value ?? 0)
    emit('submit', payload)
}

const handleClose = () => {
    formRef.value?.resetValidation?.()
    emit('close')
}

// Sync nilai awal saat dialog dibuka untuk warehouse berbeda.
watch(
    () => [props.modelValue, props.warehouse?.uid] as const,
    ([open, _uid]) => {
        if (!open) {
            formRef.value?.resetValidation?.()
            return
        }
        clearThreshold.value = props.warehouse?.min_cash === null
        minCash.value = props.warehouse?.min_cash ?? null
    },
    { immediate: true },
)
</script>

<template>
    <v-dialog
        :model-value="props.modelValue"
        max-width="540"
        scrollable
        :persistent="saving"
        @update:model-value="(v) => emit('update:modelValue', v)"
    >
        <v-card class="rounded-lg minkas-form-card" :loading="saving">
            <!-- Header -->
            <v-toolbar color="transparent" density="comfortable" flat>
                <template #prepend>
                    <v-icon :icon="MdiCashLock" color="primary" class="ms-2" />
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
                    <!-- Context block — show which warehouse + current balance -->
                    <section class="ctx-block mb-4">
                        <div class="ctx-row">
                            <v-icon :icon="MdiWarehouse" size="18" class="ctx-icon" />
                            <div class="ctx-text">
                                <div class="ctx-label">{{ t('warehouse') }}</div>
                                <div class="ctx-value">{{ warehouse?.name ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="ctx-row">
                            <v-icon :icon="MdiCashSync" size="18" class="ctx-icon" />
                            <div class="ctx-text">
                                <div class="ctx-label">{{ t('settingMinCashCurrentBalance') }}</div>
                                <div class="ctx-value font-mono">{{ balanceFormatted }}</div>
                            </div>
                        </div>
                    </section>

                    <!-- Threshold input -->
                    <section class="form-section">
                        <header class="section-head">
                            <v-icon :icon="MdiCashLock" size="16" />
                            <span>{{ t('settingMinCashThreshold') }}</span>
                        </header>
                        <v-divider class="mb-3" />

                        <v-switch
                            :model-value="clearThreshold"
                            color="warning"
                            hide-details
                            inset
                            density="comfortable"
                            :disabled="saving"
                            class="mb-2"
                            @update:model-value="(v) => (clearThreshold = !!v)"
                        >
                            <template #label>
                                <span class="text-body-2">
                                    {{ t('settingMinCashDisableThreshold') }}
                                </span>
                            </template>
                        </v-switch>

                        <base-currency-input
                            v-show="!clearThreshold"
                            v-model="minCash"
                            :label="t('settingMinCash')"
                            :disabled="saving"
                            :error-messages="undefined"
                        />

                        <v-alert
                            v-if="clearThreshold"
                            :icon="MdiInformationOutline"
                            color="info"
                            variant="tonal"
                            density="compact"
                            class="mt-3 text-caption"
                        >
                            {{ t('settingMinCashDisableHint') }}
                        </v-alert>
                    </section>

                    <!-- Live preview impact -->
                    <section class="preview-block mt-4">
                        <div class="preview-row">
                            <span class="preview-label">{{ t('settingMinCashNewThreshold') }}</span>
                            <span class="preview-value font-mono">{{ newMinFormatted }}</span>
                        </div>
                        <div class="preview-row">
                            <span class="preview-label">{{ t('settingMinCashBalanceAfter') }}</span>
                            <v-chip
                                v-if="!clearThreshold && minCash !== null"
                                size="x-small"
                                variant="tonal"
                                :color="previewBelowMin ? 'error' : 'success'"
                                class="font-weight-bold"
                            >
                                <v-icon
                                    v-if="previewBelowMin"
                                    :icon="MdiAlertCircleOutline"
                                    size="12"
                                    class="me-1"
                                />
                                {{ previewBelowMin ? t('settingMinCashBelow') : t('settingMinCashOk') }}
                            </v-chip>
                            <span v-else class="text-caption text-medium-emphasis">—</span>
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
.minkas-form-card {
    overflow: hidden;
}

.ctx-block {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 12px;
    background: rgba(var(--v-theme-on-surface), 0.025);
    border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
    border-radius: 12px;
}
.ctx-row {
    display: flex;
    align-items: center;
    gap: 10px;
}
.ctx-icon {
    color: rgb(var(--v-theme-primary));
    flex-shrink: 0;
}
.ctx-text {
    flex: 1;
    min-width: 0;
}
.ctx-label {
    font-size: 0.66rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: rgba(var(--v-theme-on-surface), 0.55);
    font-weight: 500;
}
.ctx-value {
    font-size: 0.92rem;
    font-weight: 600;
    color: rgba(var(--v-theme-on-surface), 0.92);
    word-break: break-word;
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

.preview-block {
    border: 1px dashed rgba(var(--v-theme-primary), 0.35);
    border-radius: 10px;
    padding: 10px 12px;
    background: rgba(var(--v-theme-primary), 0.04);
}
.preview-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 4px 0;
}
.preview-label {
    font-size: 0.78rem;
    color: rgba(var(--v-theme-on-surface), 0.7);
}
.preview-value {
    font-size: 0.88rem;
    font-weight: 700;
    color: rgba(var(--v-theme-on-surface), 0.95);
}

.font-mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
}

@media (max-width: 500px) {
    .preview-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 2px;
    }
}
</style>
