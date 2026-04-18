<script lang="ts" setup>
/**
 * Field "Nilai DPP" per-row pada detail Purchase Order.
 *
 * Ditampilkan hanya saat tax type baris memiliki `uses_dpp = true`.
 * Formula DPP yang dipakai = entri pertama dari daftar formula aktif
 * (master Setting DPP Formula). Tidak ada UI pemilihan — seperti field
 * "Jumlah Pajak" yang murni readonly.
 *
 * Kontrak:
 *  - `modelValue` = uid formula DPP terpilih. Komponen otomatis meng-emit
 *    uid formula pertama saat tersedia (atau null saat kosong) agar parent
 *    dapat menyimpan referensi untuk audit.
 *  - `update:amount` diemit setiap nilai DPP dihitung ulang.
 *  - Nilai reaktif terhadap qty × price — saat `price` diketik, DPP
 *    langsung ter-update tanpa aksi tambahan user.
 */
import { computed, watch } from 'vue'
import BaseCurrencyInput from '@/components/base/BaseCurrencyInput.vue'
import { useTranslate } from '@/composables/useTranslate'
import { computeDppAmount, type DppFormulaMeta } from '../composables/usePoTaxAmount'

const props = withDefaults(
    defineProps<{
        /** Daftar formula DPP aktif (urutan memengaruhi default). */
        formulas: DppFormulaMeta[]
        qty: number | string | null | undefined
        price: number | string | null | undefined
        /** UID formula DPP terpilih untuk row ini. */
        modelValue: string | null | undefined
        disabled?: boolean
    }>(),
    { disabled: false },
)

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | null): void
    (e: 'update:amount', value: number): void
}>()

const t = useTranslate()

// ─── Auto-select formula pertama ───────────────────────────────────────────
/**
 * Saat `formulas` tersedia dan `modelValue` belum di-set (atau sudah tidak
 * valid), set ke formula pertama. Guard `modelValue !== firstUid` mencegah
 * loop infinite akibat emit balik ke parent.
 */
watch(
    () => [props.formulas, props.modelValue] as const,
    ([list, current]) => {
        const firstUid = list.length > 0 ? list[0].uid : null
        const stillValid = !!current && list.some((f) => f.uid === current)
        if (!stillValid && current !== firstUid) {
            emit('update:modelValue', firstUid)
        }
    },
    { immediate: true, deep: true },
)

// ─── Derivations ───────────────────────────────────────────────────────────
const selected = computed<DppFormulaMeta | null>(() => {
    const uid = props.modelValue ?? (props.formulas[0]?.uid ?? null)
    if (!uid) return null
    return props.formulas.find((f) => f.uid === uid) ?? props.formulas[0] ?? null
})

const dppAmount = computed<number>(() =>
    computeDppAmount(props.qty, props.price, selected.value),
)

// Emit setiap perubahan nilai — parent menyimpan untuk payload submit.
watch(dppAmount, (v) => emit('update:amount', v), { immediate: true })
</script>

<template>
    <base-currency-input
        :label="t('dppAmount')"
        :model-value="dppAmount"
        readonly
    />
</template>
