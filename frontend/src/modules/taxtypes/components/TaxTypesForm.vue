<script lang="ts" setup>
import { computed, ref, watch } from 'vue'
import type { ValidationRule } from 'vuetify'
import { useTranslate } from '@/composables/useTranslate'
import { formulaKeypress, formulaPaste, sanitizeFormulaValue, type FormulaMode } from '@/helpers/formulaOnly'
// Icons
import MdiPercentOutline from '~icons/mdi/percent-outline'
import MdiFunctionVariant from '~icons/mdi/function-variant'
import MdiPencilOutline from '~icons/mdi/pencil-outline'
import MdiCalculatorVariantOutline from '~icons/mdi/calculator-variant-outline'
import MdiClose from '~icons/mdi/close'
import MdiContentSaveOutline from '~icons/mdi/content-save-outline'
import MdiTag from '~icons/mdi/tag-outline'
import MdiTextLong from '~icons/mdi/text-long'
import MdiInformationOutline from '~icons/mdi/information-outline'
import MdiReceiptTextOutline from '~icons/mdi/receipt-text-outline'

// ─── Types ───────────────────────────────────────────────────────────────────
type FormulaType = 'formula' | 'percentage' | 'manual'

interface FormulaMeta {
    value: FormulaType
    label: string
    color: string
    icon: any
    hint: string
    placeholder: string
}

// ─── Props / Emits ───────────────────────────────────────────────────────────
const props = withDefaults(defineProps<{
    modelValue: boolean
    title: string
    data: TaxTypesForm
    saving?: boolean
}>(), {
    saving: false,
})

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'submit'): void
    (e: 'close'): void
}>()

const t = useTranslate()
const formRef = ref()

// ─── Formula type metadata ──────────────────────────────────────────────────
const formulaTypes = computed<FormulaMeta[]>(() => [
    {
        value:       'formula',
        label:       t('formulaTypeFormula'),
        color:       'primary',
        icon:        MdiFunctionVariant,
        hint:        t('formulaHintFormula'),
        placeholder: 'x * 11%',
    },
    {
        value:       'percentage',
        label:       t('formulaTypePercentage'),
        color:       'success',
        icon:        MdiPercentOutline,
        hint:        t('formulaHintPercentage'),
        placeholder: '11 atau 11.5',
    },
    {
        value:       'manual',
        label:       t('formulaTypeManual'),
        color:       'deep-orange',
        icon:        MdiPencilOutline,
        hint:        t('formulaHintManual'),
        placeholder: t('formulaInputAtTransaction'),
    },
])

const currentMeta = computed<FormulaMeta>(
    () => formulaTypes.value.find(f => f.value === props.data.formula_type) ?? formulaTypes.value[1],
)

const isManualType = computed(() => props.data.formula_type === 'manual')

/**
 * Mode aktif untuk sanitasi input. `manual` fallback ke 'formula' secara default
 * (tidak dipakai karena field di-disable saat manual).
 */
const activeMode = computed<FormulaMode>(
    () => (props.data.formula_type === 'percentage' ? 'percentage' : 'formula'),
)

// ─── Validation rules ────────────────────────────────────────────────────────
const rules = {
    required: (v: unknown): true | string => {
        if (typeof v === 'string') return v.trim().length > 0 || t('fieldRequired', { field: '' })
        return !!v || t('fieldRequired', { field: '' })
    },
    maxLen: (max: number) => (v: string | null | undefined): true | string =>
        !v || v.length <= max || t('fieldMaxChar', { field: '', length: max }),
    /**
     * Whitelist char untuk mode formula: digit, x, operator + - * /, tanda kurung,
     * titik desimal, persen, dan spasi. Defense-in-depth setelah keypress filter.
     */
    allowedCharsFormula: (v: string | null | undefined): true | string =>
        !v || /^[0-9+\-*/().% x]*$/.test(v) || t('formulaRuleInvalidChar'),
    /** `%` harus tepat setelah digit. */
    percentAdjacentToDigit: (v: string | null | undefined): true | string => {
        if (!v) return true
        if (/(^|[^\d])%/.test(v)) return t('formulaRulePercent')
        return true
    },
    /** Percentage mode: angka saja, opsional satu titik desimal. */
    numericOnly: (v: string | null | undefined): true | string => {
        if (!v) return true
        return /^\d+(\.\d+)?$/.test(v) || t('formulaRulePercentageNumber')
    },
}

const formulaRules = computed<ValidationRule[]>(() => {
    if (isManualType.value) return []
    if (activeMode.value === 'percentage') {
        return [rules.required, rules.maxLen(10), rules.numericOnly]
    }
    return [rules.required, rules.maxLen(1000), rules.allowedCharsFormula, rules.percentAdjacentToDigit]
})

// ─── Handlers ────────────────────────────────────────────────────────────────
/**
 * Mutasi terkontrol ke parent. Khusus field `formula`, input difilter realtime
 * ke charset yang diizinkan supaya user tidak bisa mengetik huruf/simbol liar
 * (paste, IME, keyboard mobile) — sinkron dengan rule validasi.
 */
const commitString = (field: 'name' | 'description' | 'formula', value: unknown) => {
    let raw = typeof value === 'string' ? value : ''
    if (field === 'formula') raw = sanitizeFormulaValue(raw, activeMode.value)
    ;(props.data as any)[field] = raw
}

/**
 * Saat ganti formula_type ke 'manual', field `formula` dikosongkan karena
 * nilai akan diinput saat transaksi. Mencegah data lama tertinggal.
 */
const handleChangeType = (value: FormulaType) => {
    props.data.formula_type = value
    if (value === 'manual') {
        props.data.formula = ''
        formRef.value?.resetValidation?.()
    }
}

const handleSubmit = async () => {
    if (!formRef.value) return
    const { valid } = await formRef.value.validate()
    if (!valid) return

    // Final trim — jaga-jaga input tanpa debounce.
    props.data.name = (props.data.name ?? '').toString().trim()
    props.data.description = (props.data.description ?? '').toString().trim()
    props.data.formula = isManualType.value ? '' : (props.data.formula ?? '').toString().trim()

    emit('submit')
}

const handleClose = () => {
    formRef.value?.resetValidation?.()
    emit('close')
}

// Clear validation saat dialog tertutup / dibuka ulang.
watch(() => props.modelValue, (open) => {
    if (!open) formRef.value?.resetValidation?.()
})
</script>

<template>
    <v-dialog
        :model-value="props.modelValue"
        max-width="560"
        scrollable
        :persistent="saving"
        @update:model-value="value => emit('update:modelValue', value)"
    >
        <v-card class="rounded-lg tax-form-card" :loading="saving">
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
                    <!-- Basic info -->
                    <section class="form-section mb-4">
                        <header class="section-head">
                            <v-icon :icon="MdiTag" size="16" />
                            <span>{{ t('generalInfo') }}</span>
                        </header>
                        <v-divider class="mb-3" />
                        <v-row dense>
                            <v-col cols="12">
                                <v-text-field
                                    :label="t('taxTypes')"
                                    :model-value="props.data.name"
                                    variant="outlined"
                                    density="comfortable"
                                    hide-details="auto"
                                    :rules="[rules.required, rules.maxLen(255)]"
                                    :disabled="saving"
                                    maxlength="255"
                                    autocomplete="off"
                                    :prepend-inner-icon="MdiTag"
                                    @update:model-value="v => commitString('name', v)"
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
                                    :rules="[rules.maxLen(500)]"
                                    maxlength="500"
                                    :prepend-inner-icon="MdiTextLong"
                                    @update:model-value="v => commitString('description', v)"
                                />
                            </v-col>
                        </v-row>
                    </section>

                    <!-- Formula -->
                    <section class="form-section">
                        <header class="section-head">
                            <v-icon :icon="currentMeta.icon" size="16" :color="currentMeta.color" />
                            <span>{{ t('formula') }}</span>
                        </header>
                        <v-divider class="mb-3" />

                        <!-- Segmented type selector -->
                        <div class="type-grid mb-3">
                            <button
                                v-for="opt in formulaTypes"
                                :key="opt.value"
                                type="button"
                                class="type-chip"
                                :class="{ 'type-chip--active': props.data.formula_type === opt.value }"
                                :style="{ '--accent': `rgb(var(--v-theme-${opt.color}))` }"
                                :aria-pressed="props.data.formula_type === opt.value"
                                :disabled="saving"
                                @click="handleChangeType(opt.value)"
                            >
                                <v-icon :icon="opt.icon" size="18" />
                                <span class="type-label">{{ opt.label }}</span>
                            </button>
                        </div>

                        <v-text-field
                            :label="t('formula')"
                            :model-value="props.data.formula"
                            :placeholder="currentMeta.placeholder"
                            variant="outlined"
                            density="comfortable"
                            hide-details="auto"
                            :disabled="isManualType || saving"
                            :rules="formulaRules"
                            maxlength="1000"
                            autocomplete="off"
                            class="formula-input"
                            :prepend-inner-icon="currentMeta.icon"
                            @update:model-value="v => commitString('formula', v)"
                            @keypress="(e: KeyboardEvent) => formulaKeypress(e, activeMode)"
                            @paste="(e: ClipboardEvent) => formulaPaste(e, activeMode)"
                        />

                        <v-alert
                            :icon="MdiInformationOutline"
                            :color="currentMeta.color"
                            variant="tonal"
                            density="compact"
                            class="mt-3"
                        >
                            <template v-if="props.data.formula_type === 'formula'">
                                <div class="text-caption font-weight-bold mb-1">{{ t('formulaRulesTitle') }}</div>
                                <ul class="rules-list text-caption">
                                    <li>{{ t('formulaRuleSubtotal') }}</li>
                                    <li>{{ t('formulaRuleOperators') }}</li>
                                    <li>{{ t('formulaRuleDecimal') }}</li>
                                    <li>{{ t('formulaRulePercent') }}</li>
                                    <li>{{ t('formulaRuleAllowed') }}</li>
                                </ul>
                            </template>
                            <template v-else-if="props.data.formula_type === 'percentage'">
                                <div class="text-caption font-weight-bold mb-1">{{ t('percentageRulesTitle') }}</div>
                                <ul class="rules-list text-caption">
                                    <li>{{ t('percentageRuleDigitsOnly') }}</li>
                                    <li>{{ t('percentageRuleDecimal') }}</li>
                                    <li>{{ t('percentageRuleNoPercent') }}</li>
                                </ul>
                            </template>
                            <span v-else class="text-caption">{{ currentMeta.hint }}</span>
                        </v-alert>
                    </section>

                    <!-- Section: Pengaturan Tambahan (DPP) -->
                    <section class="form-section mt-4">
                        <header class="section-head">
                            <v-icon :icon="MdiReceiptTextOutline" size="16" />
                            <span>{{ t('taxSettings') }}</span>
                        </header>
                        <v-divider class="mb-3" />
                        <div class="dpp-row">
                            <div class="dpp-info">
                                <div class="dpp-title">{{ t('useDpp') }}</div>
                                <div class="dpp-hint text-caption text-medium-emphasis">
                                    {{ t('useDppHint') }}
                                </div>
                            </div>
                            <v-switch
                                :model-value="!!props.data.uses_dpp"
                                color="primary"
                                hide-details
                                inset
                                :disabled="saving"
                                :aria-label="t('useDpp')"
                                @update:model-value="v => (props.data.uses_dpp = !!v)"
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
.tax-form-card {
    overflow: hidden;
}

/* ── Section ─────────────────────────────────────────────────────────────── */
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

/* ── Segmented type selector ─────────────────────────────────────────────── */
.type-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 8px;
}
.type-chip {
    all: unset;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    padding: 10px 8px;
    border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
    border-radius: 10px;
    font-size: 0.78rem;
    font-weight: 500;
    color: rgba(var(--v-theme-on-surface), 0.75);
    background: rgb(var(--v-theme-surface));
    transition: border-color 0.15s ease, background 0.15s ease, transform 0.15s ease, color 0.15s ease;
    text-align: center;
    min-height: 64px;
}
.type-chip:hover:not(:disabled) {
    border-color: var(--accent);
    color: var(--accent);
    transform: translateY(-1px);
}
.type-chip:focus-visible {
    outline: 2px solid var(--accent);
    outline-offset: 2px;
}
.type-chip:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}
.type-chip--active {
    border-color: var(--accent);
    background: color-mix(in srgb, var(--accent) 10%, transparent);
    color: var(--accent);
    font-weight: 700;
}
.type-label {
    line-height: 1.1;
    word-break: break-word;
}

/* ── Formula input ───────────────────────────────────────────────────────── */
.formula-input :deep(input) {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    letter-spacing: 0.02em;
}

/* ── DPP switch row ──────────────────────────────────────────────────────── */
.dpp-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
    border-radius: 10px;
    background: rgb(var(--v-theme-surface));
    transition: border-color 0.15s ease;
}
.dpp-row:hover {
    border-color: rgba(var(--v-theme-primary), 0.35);
}
.dpp-info {
    flex: 1;
    min-width: 0;
}
.dpp-title {
    font-weight: 600;
    font-size: 0.92rem;
    color: rgba(var(--v-theme-on-surface), 0.92);
}
.dpp-hint {
    margin-top: 2px;
    line-height: 1.4;
}

/* v-switch perlu sedikit ruang; hilangkan margin bawaan */
.dpp-row :deep(.v-switch) {
    margin: 0;
    padding: 0;
    flex-shrink: 0;
}

@media (max-width: 500px) {
    .dpp-row {
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
    }
    .dpp-row :deep(.v-switch) {
        align-self: flex-end;
    }
}

/* ── Rules list (inside v-alert) ─────────────────────────────────────────── */
.rules-list {
    margin: 0;
    padding-left: 18px;
    line-height: 1.55;
}
.rules-list :deep(code),
.rules-list code {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    background: rgba(0, 0, 0, 0.08);
    padding: 0 4px;
    border-radius: 4px;
    font-size: 0.9em;
}

/* ── Responsive ──────────────────────────────────────────────────────────── */
@media (max-width: 500px) {
    .type-grid {
        grid-template-columns: 1fr;
    }
    .type-chip {
        flex-direction: row;
        justify-content: flex-start;
        gap: 10px;
        min-height: 44px;
    }
}
</style>
