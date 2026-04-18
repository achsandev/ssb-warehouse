<script lang="ts" setup>
import { computed, ref, watch } from 'vue'
import type { ValidationRule } from 'vuetify'
import { useTranslate } from '@/composables/useTranslate'
import { formulaKeypress, formulaPaste, sanitizeFormulaValue } from '@/helpers/formulaOnly'
// Icons
import MdiClose from '~icons/mdi/close'
import MdiContentSaveOutline from '~icons/mdi/content-save-outline'
import MdiFunctionVariant from '~icons/mdi/function-variant'
import MdiTagOutline from '~icons/mdi/tag-outline'
import MdiTextLong from '~icons/mdi/text-long'
import MdiInformationOutline from '~icons/mdi/information-outline'
import MdiToggleSwitchOutline from '~icons/mdi/toggle-switch-outline'
import MdiCalculatorVariantOutline from '~icons/mdi/calculator-variant-outline'

// ─── Props / Emits ───────────────────────────────────────────────────────────
const props = withDefaults(
    defineProps<{
        modelValue: boolean
        title: string
        data: SettingDppFormulaForm
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
    allowedCharsFormula: (v: string | null | undefined): true | string =>
        !v || /^[0-9+\-*/().% x]*$/.test(v) || t('formulaRuleInvalidChar'),
    percentAdjacentToDigit: (v: string | null | undefined): true | string => {
        if (!v) return true
        if (/(^|[^\d])%/.test(v)) return t('formulaRulePercent')
        return true
    },
    balancedParens: (v: string | null | undefined): true | string => {
        if (!v) return true
        let depth = 0
        for (const ch of v) {
            if (ch === '(') depth++
            else if (ch === ')') depth--
            if (depth < 0) return t('formulaRuleParens')
        }
        return depth === 0 || t('formulaRuleParens')
    },
}

const nameRules = computed<ValidationRule[]>(() => [rules.required, rules.maxLen(150)])
const formulaRules = computed<ValidationRule[]>(() => [
    rules.required,
    rules.maxLen(1000),
    rules.allowedCharsFormula,
    rules.percentAdjacentToDigit,
    rules.balancedParens,
])
const descriptionRules = computed<ValidationRule[]>(() => [rules.maxLen(500)])

// ─── Handlers ────────────────────────────────────────────────────────────────
const commitString = (field: 'name' | 'description' | 'formula', value: unknown) => {
    let raw = typeof value === 'string' ? value : ''
    if (field === 'formula') raw = sanitizeFormulaValue(raw, 'formula')
    ;(props.data as any)[field] = raw
}

const handleSubmit = async () => {
    if (!formRef.value) return
    const { valid } = await formRef.value.validate()
    if (!valid) return

    props.data.name = (props.data.name ?? '').toString().trim()
    props.data.formula = (props.data.formula ?? '').toString().trim()
    props.data.description = (props.data.description ?? '').toString().trim()

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
        max-width="560"
        scrollable
        :persistent="saving"
        @update:model-value="(value) => emit('update:modelValue', value)"
    >
        <v-card class="rounded-lg dpp-form-card" :loading="saving">
            <!-- Header -->
            <v-toolbar color="transparent" density="comfortable" flat>
                <template #prepend>
                    <v-icon :icon="MdiCalculatorVariantOutline" class="ms-2" />
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
                    <!-- General Info -->
                    <section class="form-section mb-4">
                        <header class="section-head">
                            <v-icon :icon="MdiTagOutline" size="16" />
                            <span>{{ t('generalInfo') }}</span>
                        </header>
                        <v-divider class="mb-3" />
                        <v-row dense>
                            <v-col cols="12">
                                <v-text-field
                                    :label="t('formulaName')"
                                    :model-value="props.data.name"
                                    variant="outlined"
                                    density="comfortable"
                                    hide-details="auto"
                                    :rules="nameRules"
                                    :disabled="saving"
                                    maxlength="150"
                                    autocomplete="off"
                                    :prepend-inner-icon="MdiTagOutline"
                                    @update:model-value="(v) => commitString('name', v)"
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
                                    :rules="descriptionRules"
                                    maxlength="500"
                                    :prepend-inner-icon="MdiTextLong"
                                    @update:model-value="(v) => commitString('description', v)"
                                />
                            </v-col>
                        </v-row>
                    </section>

                    <!-- Formula -->
                    <section class="form-section mb-4">
                        <header class="section-head">
                            <v-icon :icon="MdiFunctionVariant" size="16" color="primary" />
                            <span>{{ t('formula') }}</span>
                        </header>
                        <v-divider class="mb-3" />

                        <v-text-field
                            :label="t('formula')"
                            :model-value="props.data.formula"
                            placeholder="x * 11%"
                            variant="outlined"
                            density="comfortable"
                            hide-details="auto"
                            :disabled="saving"
                            :rules="formulaRules"
                            maxlength="1000"
                            autocomplete="off"
                            class="formula-input"
                            :prepend-inner-icon="MdiFunctionVariant"
                            @update:model-value="(v) => commitString('formula', v)"
                            @keypress="(e: KeyboardEvent) => formulaKeypress(e, 'formula')"
                            @paste="(e: ClipboardEvent) => formulaPaste(e, 'formula')"
                        />

                        <v-alert
                            :icon="MdiInformationOutline"
                            color="primary"
                            variant="tonal"
                            density="compact"
                            class="mt-3"
                        >
                            <div class="text-caption font-weight-bold mb-1">
                                {{ t('formulaRulesTitle') }}
                            </div>
                            <ul class="rules-list text-caption">
                                <li>{{ t('formulaRuleSubtotal') }}</li>
                                <li>{{ t('formulaRuleOperators') }}</li>
                                <li>{{ t('formulaRuleDecimal') }}</li>
                                <li>{{ t('formulaRulePercent') }}</li>
                                <li>{{ t('formulaRuleAllowed') }}</li>
                            </ul>
                        </v-alert>
                    </section>

                    <!-- Status -->
                    <section class="form-section">
                        <header class="section-head">
                            <v-icon :icon="MdiToggleSwitchOutline" size="16" />
                            <span>{{ t('status') }}</span>
                        </header>
                        <v-divider class="mb-3" />
                        <div class="status-row">
                            <div class="status-info">
                                <div class="status-title">{{ t('activeStatus') }}</div>
                                <div class="status-hint text-caption text-medium-emphasis">
                                    {{ t('dppFormulaActiveHint') }}
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
.dpp-form-card {
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

.formula-input :deep(input) {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    letter-spacing: 0.02em;
}

.status-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
    border-radius: 10px;
    background: rgb(var(--v-theme-surface));
    transition: border-color 0.15s ease;
}
.status-row:hover {
    border-color: rgba(var(--v-theme-primary), 0.35);
}
.status-info {
    flex: 1;
    min-width: 0;
}
.status-title {
    font-weight: 600;
    font-size: 0.92rem;
    color: rgba(var(--v-theme-on-surface), 0.92);
}
.status-hint {
    margin-top: 2px;
    line-height: 1.4;
}
.status-row :deep(.v-switch) {
    margin: 0;
    padding: 0;
    flex-shrink: 0;
}

.rules-list {
    margin: 0;
    padding-left: 18px;
    line-height: 1.55;
}

@media (max-width: 500px) {
    .status-row {
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
    }
    .status-row :deep(.v-switch) {
        align-self: flex-end;
    }
}
</style>
