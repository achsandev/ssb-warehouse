<script lang="ts" setup>
/**
 * StockConversionDialog — konversi qty stok antar satuan.
 * Dipakai di Items page untuk user yang punya permission items.update.
 *
 * Flow:
 *  1. Pilih record stok (jika item punya lebih dari 1 lokasi)
 *  2. Pilih satuan sumber (dari stock_units yang tersedia)
 *  3. Isi qty yang akan dikonversi
 *  4. Pilih satuan tujuan + qty hasil konversi
 *  5. Submit → stockStore.conversion()
 */
import { computed, ref, watch } from 'vue'
import { useField, useForm } from 'vee-validate'
import { useTranslate } from '@/composables/useTranslate'
import { conversionSchema } from '@/validations/conversionSchema'
import { useStockStore } from '@/stores/stock'
import { api } from '@/api/api'
// Base components
import BaseAsyncSelect from '@/components/base/BaseAsyncSelect.vue'
import type { AsyncFetcher } from '@/components/base/BaseAsyncSelect.vue'
// Icons (unplugin-icons / Iconify)
import MdiClose from '~icons/mdi/close'
import MdiPackageVariantClosed from '~icons/mdi/package-variant-closed'
import MdiSwapHorizontalBold from '~icons/mdi/swap-horizontal-bold'
import MdiWarehouse from '~icons/mdi/warehouse'
import MdiRuler from '~icons/mdi/ruler'
import MdiContentSaveOutline from '~icons/mdi/content-save-outline'

type Option = { title: string; value: string }

type StockUnit = {
    uid: string
    unit_uid: string
    unit_name: string
    unit_symbol: string
    qty: number
}

type StockRecord = {
    uid: string
    warehouse?: { name: string } | null
    rack?: { name: string } | null
    tank?: { name: string } | null
    stock_units?: StockUnit[] | null
}

const props = defineProps<{
    modelValue: boolean
    item: ItemsList | null
    stocks: StockRecord[] | null
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'success'): void
    (e: 'close'): void
}>()

const t = useTranslate()
const stockStore = useStockStore()

// ─── State ───────────────────────────────────────────────────────────────────
const { handleSubmit, setValues, resetForm, errors } = useForm<ConversionForm>({
    validationSchema: conversionSchema(t),
})

const { value: stock_uid } = useField<string>('stock_uid')
const { value: base_unit_uid } = useField<string>('base_unit_uid')
const { value: derived_unit_uid } = useField<string>('derived_unit_uid')
const { value: convert_qty } = useField<number>('convert_qty')
const { value: converted_qty } = useField<number>('converted_qty')

// Reset saat dialog dibuka
watch(() => props.modelValue, (open) => {
    if (!open) return
    resetForm()
    // Auto-pick stock kalau cuma 1
    if (props.stocks?.length === 1) {
        setValues({ stock_uid: props.stocks[0].uid } as any)
    }
})

// ─── Derived ────────────────────────────────────────────────────────────────
const formatLocation = (s: StockRecord) => {
    const parts = [s.warehouse?.name || '-']
    if (s.rack?.name) parts.push(`(${s.rack.name})`)
    if (s.tank?.name) parts.push(`(${s.tank.name})`)
    return parts.join(' ')
}

const stockOptions = computed<Option[]>(() =>
    (props.stocks ?? []).map(s => ({ title: formatLocation(s), value: s.uid }))
)

const selectedStock = computed<StockRecord | null>(() =>
    props.stocks?.find(s => s.uid === stock_uid.value) ?? null
)

const baseUnitOptions = computed<Option[]>(() =>
    (selectedStock.value?.stock_units ?? []).map(su => ({
        title: `${su.unit_symbol} – ${su.unit_name} (${su.qty})`,
        value: su.unit_uid,
    }))
)

const availableQty = computed<number>(() => {
    const su = selectedStock.value?.stock_units?.find(u => u.unit_uid === base_unit_uid.value)
    return su?.qty ?? 0
})

const qtyOverAvailable = computed(() =>
    Number(convert_qty.value ?? 0) > availableQty.value
)

// ─── Fetcher satuan tujuan (semua satuan kecuali yang sudah jadi base) ─────
const fetchUnit: AsyncFetcher<Option> = async ({ search, page, perPage, signal }) => {
    const res = await api.get('/item_units', {
        params: { page, per_page: perPage, search, sort_by: 'name', sort_dir: 'asc' },
        signal,
    })
    const payload = res.data ?? {}
    const meta = payload.meta ?? payload.pagination ?? {}
    return {
        items: (payload.data ?? []).map((u: any) => ({
            title: `${u.symbol} – ${u.name}`,
            value: u.uid,
        })),
        hasMore: Number(meta.current_page ?? page) < Number(meta.last_page ?? 1),
    }
}

const excludedDerivedUnits = computed(() =>
    base_unit_uid.value ? [base_unit_uid.value] : []
)

// ─── Submit ──────────────────────────────────────────────────────────────────
const submit = handleSubmit(async (values) => {
    if (qtyOverAvailable.value) return

    await stockStore.conversion({
        stock_uid: values.stock_uid,
        base_unit_uid: values.base_unit_uid,
        derived_unit_uid: values.derived_unit_uid,
        convert_qty: Number(values.convert_qty),
        converted_qty: Number(values.converted_qty),
    })

    emit('success')
    emit('update:modelValue', false)
})

const handleClose = () => {
    resetForm()
    emit('update:modelValue', false)
    emit('close')
}
</script>

<template>
    <v-dialog
        :model-value="modelValue"
        max-width="680"
        persistent
        scrollable
    >
        <v-card class="rounded-lg d-flex flex-column" :loading="stockStore.loading" style="max-height: 90vh;">
            <!-- Header -->
            <v-card-title class="d-flex align-center ga-2 px-4 px-sm-6 py-3 py-sm-4 bg-primary text-white">
                <v-icon :icon="MdiSwapHorizontalBold" class="shrink-0" />
                <div class="d-flex flex-column overflow-hidden grow">
                    <span class="text-subtitle-1 text-sm-h6 font-weight-semibold">
                        {{ t('convertStock') }}
                    </span>
                    <span class="text-caption opacity-80 text-truncate">
                        {{ item?.name }}
                    </span>
                </div>
                <v-btn
                    :icon="MdiClose"
                    variant="text"
                    size="small"
                    color="white"
                    class="shrink-0 ms-auto"
                    :disabled="stockStore.loading"
                    @click="handleClose"
                />
            </v-card-title>

            <v-card-text class="pa-4 pa-sm-6 grow overflow-y-auto">
                <!-- Info barang yang akan dikonversi (readonly) -->
                <v-sheet rounded="lg" border class="pa-3 mb-4 d-flex align-center ga-3">
                    <v-icon :icon="MdiPackageVariantClosed" color="primary" size="24" class="shrink-0" />
                    <div class="d-flex flex-column overflow-hidden grow">
                        <span class="text-overline">{{ t('item') }}</span>
                        <span class="text-body-2 font-weight-semibold text-truncate">
                            {{ item?.name || '-' }}
                        </span>
                        <span v-if="item?.code" class="text-caption text-medium-emphasis">
                            {{ item.code }}
                        </span>
                    </div>
                </v-sheet>

                <v-alert
                    type="info"
                    variant="tonal"
                    density="compact"
                    class="mb-4"
                    :text="t('stockConversionHint')"
                />

                <!-- Pilih lokasi stok -->
                <div class="section-header mb-3">
                    <v-icon :icon="MdiWarehouse" size="18" color="primary" />
                    <span class="text-subtitle-2 font-weight-bold text-primary">{{ t('selectStockLocation') }}</span>
                </div>
                <v-sheet rounded="lg" border class="pa-2 mb-4">
                    <v-radio-group
                        v-model="stock_uid"
                        :error-messages="errors.stock_uid"
                        hide-details="auto"
                        density="compact"
                    >
                        <v-radio
                            v-for="opt in stockOptions"
                            :key="opt.value"
                            :value="opt.value"
                            :label="opt.title"
                            color="primary"
                        />
                    </v-radio-group>
                </v-sheet>

                <!-- Konversi -->
                <div class="section-header mb-3">
                    <v-icon :icon="MdiRuler" size="18" color="primary" />
                    <span class="text-subtitle-2 font-weight-bold text-primary">{{ t('conversionDetails') }}</span>
                </div>
                <v-row dense>
                    <v-col cols="12" md="6">
                        <base-async-select
                            :key="`base-unit-${stock_uid}`"
                            v-model="base_unit_uid"
                            :fetcher="(async () => ({ items: baseUnitOptions, hasMore: false })) as any"
                            :initial-selected="null"
                            :label="t('fromUnit')"
                            :error-messages="errors.base_unit_uid"
                            :disabled="!stock_uid"
                        />
                    </v-col>
                    <v-col cols="12" md="6">
                        <v-number-input
                            v-model="convert_qty"
                            :label="t('convertQty')"
                            :error-messages="qtyOverAvailable ? t('qtyOverAvailable', { available: availableQty, symbol: '' }) : errors.convert_qty"
                            :min="0.01"
                            :step="0.01"
                            :disabled="!base_unit_uid"
                            variant="outlined"
                            density="comfortable"
                            hide-details="auto"
                            control-variant="stacked"
                        />
                    </v-col>
                    <v-col cols="12" md="6">
                        <base-async-select
                            v-model="derived_unit_uid"
                            :fetcher="fetchUnit"
                            :label="t('toUnit')"
                            :error-messages="errors.derived_unit_uid"
                            :exclude-values="excludedDerivedUnits"
                            :disabled="!base_unit_uid"
                        />
                    </v-col>
                    <v-col cols="12" md="6">
                        <v-number-input
                            v-model="converted_qty"
                            :label="t('convertedQty')"
                            :error-messages="errors.converted_qty"
                            :min="0.01"
                            :step="0.01"
                            :disabled="!derived_unit_uid"
                            variant="outlined"
                            density="comfortable"
                            hide-details="auto"
                            control-variant="stacked"
                        />
                    </v-col>
                </v-row>
            </v-card-text>

            <v-divider />

            <v-card-actions class="pa-4 justify-end ga-2">
                <v-btn variant="tonal" :disabled="stockStore.loading" @click="handleClose">
                    {{ t('cancel') }}
                </v-btn>
                <v-btn
                    color="primary"
                    variant="elevated"
                    :loading="stockStore.loading"
                    :disabled="qtyOverAvailable"
                    :prepend-icon="MdiContentSaveOutline"
                    @click="submit"
                >
                    {{ t('save') }}
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<style scoped>
.section-header {
    display: flex;
    align-items: center;
    gap: 8px;
}
</style>
