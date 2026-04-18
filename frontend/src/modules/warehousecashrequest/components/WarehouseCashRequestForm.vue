<script lang="ts" setup>
import { computed, onMounted, ref } from 'vue'
import { useField, useForm } from 'vee-validate'
import { useTranslate } from '@/composables/useTranslate'
import { warehouseCashRequestSchema } from '@/validations/warehouseCashRequestSchema'
import { warehousesLookup } from '@/api/warehouse_cash_request.api'
import BaseDateInput from '@/components/base/BaseDateInput.vue'
import BaseCurrencyInput from '@/components/base/BaseCurrencyInput.vue'
import SystemUiconsClose from '~icons/system-uicons/close'
import MdiPaperclip from '~icons/mdi/paperclip'
import dayjs from 'dayjs'

const props = defineProps<{
    modelValue: boolean
    title: string
    data: WarehouseCashRequestList
    saving?: boolean
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'submit', values: WarehouseCashRequestForm): void
    (e: 'close'): void
}>()

const t = useTranslate()

const loadingForm      = ref(false)
const warehouseOptions = ref<WarehouseOption[]>([])

const isBusy = computed(() => loadingForm.value || !!props.saving)

// ─── Form ─────────────────────────────────────────────────────────────────────
const { handleSubmit, setValues, errors } = useForm<WarehouseCashRequestForm>({
    validationSchema: warehouseCashRequestSchema(t),
})

setValues({
    request_date:    props.data.request_date
        ? dayjs(props.data.request_date as string).format('YYYY-MM-DD')
        : dayjs().format('YYYY-MM-DD'),
    warehouse_uid:   props.data.warehouse_uid ?? null,
    amount:          props.data.amount ?? null,
    notes:           props.data.notes ?? null,
    attachment:      undefined,
})

const { value: request_date  } = useField<string>('request_date')
const { value: warehouse_uid } = useField<string | null>('warehouse_uid')
const { value: amount        } = useField<number | null>('amount')
const { value: notes         } = useField<string | null>('notes')
const { value: attachment    } = useField<File | File[] | null | undefined>('attachment')

// ─── Selected warehouse helper ────────────────────────────────────────────────
const selectedWarehouse = computed<WarehouseOption | null>(
    () => warehouseOptions.value.find(w => w.uid === warehouse_uid.value) ?? null,
)

const cashBalanceWarning = computed(() =>
    selectedWarehouse.value !== null && selectedWarehouse.value.cash_balance >= 1_000_000,
)

const warehouseItems = computed(() =>
    warehouseOptions.value.map(w => ({
        title: `${w.name} — Saldo: Rp ${w.cash_balance.toLocaleString('id-ID')}`,
        value: w.uid,
        disabled: w.cash_balance >= 1_000_000,
    })),
)

// ─── Load warehouses lookup ───────────────────────────────────────────────────
const loadLookups = async () => {
    loadingForm.value = true
    try {
        const res = await warehousesLookup()
        warehouseOptions.value = res.data?.data ?? res.data ?? []
    } catch {
        // silently ignore
    } finally {
        loadingForm.value = false
    }
}

onMounted(loadLookups)

// ─── Submit ───────────────────────────────────────────────────────────────────
const onSubmit = handleSubmit((values) => {
    emit('submit', values)
})
const onClose  = () => emit('close')
</script>

<template>
    <v-dialog
        :model-value="modelValue"
        max-width="640"
        :persistent="isBusy"
        scrollable
        @update:model-value="onClose"
    >
        <v-card :loading="loadingForm" rounded="lg">

            <!-- Title bar -->
            <v-card-title class="d-flex align-center gap-2 px-6 py-4">
                <span class="text-h6 font-weight-semibold">{{ title }}</span>
                <v-spacer />
                <v-btn :icon="SystemUiconsClose" variant="text" size="small" :disabled="isBusy" @click="onClose" />
            </v-card-title>

            <v-divider />

            <v-card-text class="px-6 py-5">
                <v-form @submit.prevent="onSubmit">

                    <!-- General Info -->
                    <div class="section-header mb-3">
                        <span class="text-subtitle-2 font-weight-bold text-primary">{{ t('generalInfo') }}</span>
                    </div>

                    <v-row>
                        <!-- Request Date -->
                        <v-col cols="12">
                            <base-date-input
                                v-model="request_date"
                                :disabled="loadingForm"
                            />
                        </v-col>

                        <!-- Warehouse -->
                        <v-col cols="12">
                            <v-autocomplete
                                v-model="warehouse_uid"
                                :items="warehouseItems"
                                item-title="title"
                                item-value="value"
                                :label="t('warehouse')"
                                :placeholder="t('selectWarehouse')"
                                :error-messages="errors.warehouse_uid"
                                :disabled="loadingForm || !!data.uid"
                                variant="outlined"
                                hide-details="auto"
                                clearable
                            />
                        </v-col>

                        <!-- Cash balance warning -->
                        <v-col v-if="cashBalanceWarning" cols="12">
                            <v-alert
                                type="warning"
                                variant="tonal"
                                density="compact"
                                class="text-caption"
                            >
                                {{ t('cashBalanceSufficient') }}
                                <strong> (Rp {{ selectedWarehouse?.cash_balance.toLocaleString('id-ID') }})</strong>.
                                {{ t('cashBalanceBelowLimit') }}
                            </v-alert>
                        </v-col>

                        <!-- Current cash balance info -->
                        <v-col v-if="selectedWarehouse && !cashBalanceWarning" cols="12">
                            <v-alert
                                type="info"
                                variant="tonal"
                                density="compact"
                                class="text-caption"
                            >
                                {{ t('currentCashBalance') }}:
                                <strong>Rp {{ selectedWarehouse.cash_balance.toLocaleString('id-ID') }}</strong>
                            </v-alert>
                        </v-col>

                        <!-- Amount -->
                        <v-col cols="12">
                            <base-currency-input
                                v-model="amount"
                                :label="t('requestAmount')"
                                :error-message="errors.amount"
                                :loading="loadingForm"
                            />
                        </v-col>

                        <!-- Notes -->
                        <v-col cols="12">
                            <v-textarea
                                v-model="notes"
                                :label="t('notes')"
                                :disabled="loadingForm"
                                rows="2"
                                auto-grow
                                density="compact"
                                variant="outlined"
                                hide-details="auto"
                            />
                        </v-col>

                        <!-- Attachment -->
                        <v-col cols="12">
                            <v-file-input
                                v-model="attachment"
                                :label="t('attachment')"
                                :disabled="loadingForm"
                                accept=".pdf,.jpg,.jpeg,.png"
                                variant="outlined"
                                prepend-icon=""
                                :prepend-inner-icon="MdiPaperclip"
                                hide-details="auto"
                                clearable
                            />
                        </v-col>
                    </v-row>

                </v-form>
            </v-card-text>

            <v-divider />

            <!-- Actions -->
            <v-card-actions class="px-6 py-4 justify-end gap-2">
                <v-btn variant="tonal" :disabled="isBusy" @click="onClose">{{ t('cancel') }}</v-btn>
                <v-btn
                    color="primary"
                    variant="elevated"
                    :loading="saving"
                    :disabled="loadingForm"
                    @click="onSubmit"
                >
                    {{ t('save') }}
                </v-btn>
            </v-card-actions>

        </v-card>
    </v-dialog>
</template>

<style scoped>
.section-header { display: flex; align-items: center; gap: 6px; }
</style>
