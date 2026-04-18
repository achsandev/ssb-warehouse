<script lang="ts" setup>
/**
 * Field "Jumlah Pajak" per-row pada detail Purchase Order.
 *
 * Perilaku berdasarkan `meta.formula_type`:
 *  - `percentage` : readonly, nilai otomatis = subtotal × rate/100.
 *  - `formula`    : readonly, nilai otomatis = evaluate(formula) dengan x = subtotal.
 *  - `manual`     : editable, user mengetik nilai pajak sendiri (v-model).
 *  - null / tidak dipilih : readonly dengan nilai 0.
 */
import { computed } from 'vue'
import BaseCurrencyInput from '@/components/base/BaseCurrencyInput.vue'
import { useTranslate } from '@/composables/useTranslate'
import { computeAutoTaxAmount, isManualTax, type TaxMeta } from '../composables/usePoTaxAmount'

const props = withDefaults(defineProps<{
    meta: TaxMeta | null | undefined
    qty: number | string | null | undefined
    price: number | string | null | undefined
    /** Nilai manual (hanya relevan saat meta.formula_type = 'manual'). */
    modelValue: number | null | undefined
    disabled?: boolean
}>(), {
    disabled: false,
})

const emit = defineEmits<{
    (e: 'update:modelValue', value: number): void
}>()

const t = useTranslate()

const isManual = computed(() => isManualTax(props.meta))

/** Nilai yang ditampilkan: manual → modelValue; selain itu → hasil otomatis. */
const displayValue = computed<number>(() => {
    if (isManual.value) {
        const v = typeof props.modelValue === 'number' ? props.modelValue : Number(props.modelValue)
        return Number.isFinite(v) ? v : 0
    }
    return computeAutoTaxAmount(props.qty, props.price, props.meta ?? null) ?? 0
})

const onUpdate = (value: number) => {
    if (!isManual.value) return // guard: mode readonly tidak boleh emit.
    const n = typeof value === 'number' ? value : Number(value)
    emit('update:modelValue', Number.isFinite(n) ? n : 0)
}
</script>

<template>
    <base-currency-input
        :model-value="displayValue"
        :label="t('taxAmount')"
        :disabled="disabled"
        :readonly="!isManual"
        @update:model-value="onUpdate"
    />
</template>
