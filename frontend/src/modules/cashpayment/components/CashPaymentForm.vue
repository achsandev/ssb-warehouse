<script lang="ts" setup>
import { computed, onMounted, ref } from 'vue'
import { useField, useForm } from 'vee-validate'
import { useTranslate } from '@/composables/useTranslate'
import { cashPaymentSchema } from '@/validations/cashPaymentSchema'
import { warehousesLookup } from '@/api/cash_payment.api'
import BaseDateInput from '@/components/base/BaseDateInput.vue'
import BaseCurrencyInput from '@/components/base/BaseCurrencyInput.vue'
import SystemUiconsClose from '~icons/system-uicons/close'
import MdiPaperclip from '~icons/mdi/paperclip'
import dayjs from 'dayjs'

const props = defineProps<{
    modelValue: boolean
    title: string
    data: CashPaymentList
    saving?: boolean
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'submit', values: CashPaymentForm): void
    (e: 'close'): void
}>()

const t = useTranslate()

const loadingForm      = ref(false)
const warehouseOptions = ref<{ uid: string; name: string; cash_balance: number }[]>([])

const isEdit = computed(() => !!props.data.uid)
const isBusy = computed(() => loadingForm.value || !!props.saving)

// ─── Form ─────────────────────────────────────────────────────────────────────
const { handleSubmit, setValues, errors } = useForm<CashPaymentForm>({
    validationSchema: cashPaymentSchema(t, !!props.data.uid),
})

setValues({
    payment_date:  props.data.payment_date
        ? dayjs(props.data.payment_date as string).format('YYYY-MM-DD')
        : dayjs().format('YYYY-MM-DD'),
    warehouse_uid: props.data.warehouse_uid ?? null,
    description:   props.data.description ?? null,
    amount:        props.data.amount ?? null,
    notes:         props.data.notes ?? null,
})

const { value: payment_date  } = useField<string>('payment_date')
const { value: warehouse_uid } = useField<string | null>('warehouse_uid')
const { value: description   } = useField<string | null>('description')
const { value: amount        } = useField<number | null>('amount')
const { value: notes         } = useField<string | null>('notes')
const { value: spk           } = useField<File | null>('spk')
const { value: attachment    } = useField<File | null>('attachment')

// ─── Warehouse items ───────────────────────────────────────────────────────────
const warehouseItems = computed(() =>
    warehouseOptions.value.map(w => ({
        title: `${w.name} — Saldo: Rp ${w.cash_balance.toLocaleString('id-ID')}`,
        value: w.uid,
    })),
)

const selectedWarehouse = computed(() =>
    warehouseOptions.value.find(w => w.uid === warehouse_uid.value) ?? null,
)

// ─── Load lookups ─────────────────────────────────────────────────────────────
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

const onClose = () => emit('close')
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
                        <!-- Payment Date -->
                        <v-col cols="12">
                            <base-date-input
                                v-model="payment_date"
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
                                :disabled="loadingForm || isEdit"
                                variant="outlined"
                                hide-details="auto"
                                clearable
                            />
                        </v-col>

                        <!-- Cash balance info -->
                        <v-col v-if="selectedWarehouse" cols="12">
                            <v-sheet
                                color="info"
                                variant="tonal"
                                rounded
                                class="pa-3 text-caption"
                            >
                                {{ t('currentCashBalance') }}:
                                <strong>Rp {{ selectedWarehouse.cash_balance.toLocaleString('id-ID') }}</strong>
                            </v-sheet>
                        </v-col>

                        <!-- Amount -->
                        <v-col cols="12">
                            <base-currency-input
                                v-model="amount"
                                :label="t('paymentAmount')"
                                :error-message="errors.amount"
                                :loading="loadingForm"
                            />
                        </v-col>

                        <!-- Description -->
                        <v-col cols="12">
                            <v-textarea
                                v-model="description"
                                :label="t('paymentDescription')"
                                :error-messages="errors.description"
                                :disabled="loadingForm"
                                rows="2"
                                auto-grow
                                density="compact"
                                variant="outlined"
                                hide-details="auto"
                            />
                        </v-col>

                        <!-- Documents (create only) -->
                        <template v-if="!isEdit">
                            <v-col cols="12">
                                <v-divider class="mb-3" />
                                <p class="text-subtitle-2 font-weight-bold text-primary mb-3">{{ t('documents') }}</p>
                            </v-col>

                            <!-- SPK Document -->
                            <v-col cols="12">
                                <v-file-input
                                    v-model="spk"
                                    :label="t('spkDocument') + ' *'"
                                    :error-messages="errors.spk"
                                    :disabled="loadingForm"
                                    accept=".pdf"
                                    variant="outlined"
                                    hide-details="auto"
                                    prepend-icon=""
                                    :prepend-inner-icon="MdiPaperclip"
                                    clearable
                                />
                            </v-col>

                            <!-- Attachment -->
                            <v-col cols="12">
                                <v-file-input
                                    v-model="attachment"
                                    :label="t('attachment') + ' *'"
                                    :error-messages="errors.attachment"
                                    :disabled="loadingForm"
                                    accept=".pdf"
                                    variant="outlined"
                                    hide-details="auto"
                                    prepend-icon=""
                                    :prepend-inner-icon="MdiPaperclip"
                                    clearable
                                />
                            </v-col>
                        </template>

                        <!-- Existing documents (edit mode) -->
                        <template v-else>
                            <v-col v-if="data.spk_name || data.attachment_name" cols="12">
                                <v-divider class="mb-3" />
                                <p class="text-subtitle-2 font-weight-bold text-primary mb-2">{{ t('documents') }}</p>
                                <div class="d-flex flex-column gap-1 text-caption text-medium-emphasis">
                                    <span v-if="data.spk_name">
                                        <strong>{{ t('spkDocument') }}:</strong> {{ data.spk_name }}
                                    </span>
                                    <span v-if="data.attachment_name">
                                        <strong>{{ t('attachment') }}:</strong> {{ data.attachment_name }}
                                    </span>
                                </div>
                            </v-col>
                        </template>

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
