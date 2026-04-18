<script lang="ts" setup>
import { computed, onMounted } from 'vue'
import { useField, useForm } from 'vee-validate'
// Composables
import { useTranslate } from '@/composables/useTranslate'
// Stores
import { usePaymentMethodsStore } from '@/stores/payment_methods'
import { usePaymentDurationStore } from '@/stores/payment_duration'
import { useTaxTypesStore } from '@/stores/tax_types'
// Validations
import { supplierSchema } from '@/validations'
// Helpers
import { numberOnly } from '@/helpers/numberOnly'
// Components
import BaseNpwpInput from '@/components/base/BaseNpwpInput.vue'
import BaseAutocomplete from '@/components/base/BaseAutocomplete.vue'
// Icons
import MdiDomain from '~icons/mdi/domain'
import MdiClose from '~icons/mdi/close'
import MdiContentSaveOutline from '~icons/mdi/content-save-outline'
import MdiAccount from '~icons/mdi/account-outline'
import MdiMapMarkerOutline from '~icons/mdi/map-marker-outline'
import MdiPhoneOutline from '~icons/mdi/phone-outline'
import MdiEmailOutline from '~icons/mdi/email-outline'
import MdiAccountTie from '~icons/mdi/account-tie-outline'
import MdiCreditCardOutline from '~icons/mdi/credit-card-outline'
import MdiInformationOutline from '~icons/mdi/information-outline'
import MdiTextLong from '~icons/mdi/text-long'

// ─── Props / Emits (tidak diubah — kompatibel dengan parent) ────────────────
const props = withDefaults(defineProps<{
    modelValue: boolean
    title: string
    data: SupplierList
    saving?: boolean
}>(), {
    saving: false,
})

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'submit', values: SupplierForm): void
    (e: 'close'): void
}>()

// ─── Stores & composables ───────────────────────────────────────────────────
const t = useTranslate()
const paymentMethodeStore = usePaymentMethodsStore()
const paymentDurationStore = usePaymentDurationStore()
const taxTypesStore = useTaxTypesStore()

// ─── Form (vee-validate — tidak diubah logika submit/close) ─────────────────
const { handleSubmit, setValues, resetForm, errors } = useForm<SupplierForm>({
    validationSchema: supplierSchema(t),
})

setValues({
    name:                 props.data.name,
    address:              props.data.address,
    phone_number:         props.data.phone_number,
    npwp_number:          props.data.npwp_number,
    pic_name:             props.data.pic_name,
    email:                props.data.email,
    payment_method_uid:   props.data.payment_method?.uid,
    payment_duration_uid: props.data.payment_duration?.uid,
    tax_type_uid:         props.data.tax_types?.map(x => x.uid) ?? [],
    additional_info:      props.data.additional_info ?? '',
})

const { value: name }                 = useField<string>('name')
const { value: address }              = useField<string>('address')
const { value: phone_number }         = useField<string>('phone_number')
const { value: npwp_number }          = useField<string>('npwp_number')
const { value: pic_name }             = useField<string>('pic_name')
const { value: email }                = useField<string>('email')
const { value: payment_method_uid }   = useField<string | null>('payment_method_uid')
const { value: payment_duration_uid } = useField<string | null>('payment_duration_uid')
const { value: tax_type_uid }         = useField<string[]>('tax_type_uid')
const { value: additional_info }      = useField<string>('additional_info')

// ─── Select items (reaktif ke store.data, tidak perlu ref manual) ───────────
const toSelect = <T extends { uid: string; name: string }>(list: T[]): SelectItem[] =>
    list.map(i => ({ title: i.name, value: i.uid }))

const paymentMethodItem   = computed<SelectItem[]>(() => toSelect(paymentMethodeStore.data as any))
const paymentDurationItem = computed<SelectItem[]>(() => toSelect(paymentDurationStore.data as any))
const taxTypeItem         = computed<SelectItem[]>(() => toSelect(taxTypesStore.data as any))

// ─── Lookup loader (DRY) ────────────────────────────────────────────────────
const LOOKUP_PARAMS: TableParams = {
    page: 1,
    itemsPerPage: -1,
    sortBy: [{ key: 'created_at', order: 'desc' }],
    search: '',
}

const loadLookup = async (store: { fetch: (p: TableParams) => Promise<void> }): Promise<void> => {
    try {
        await store.fetch(LOOKUP_PARAMS)
    } catch (err) {
        // Biarkan halaman tetap dapat dibuka meski lookup gagal — error di store sudah notify user.
        console.error('[SupplierForm] lookup load error:', err)
    }
}

onMounted(() => {
    void Promise.all([
        loadLookup(paymentMethodeStore),
        loadLookup(paymentDurationStore),
        loadLookup(taxTypesStore),
    ])
})

// ─── Submit / Close (tidak mengubah kontrak dengan parent) ──────────────────
const submit = handleSubmit((values: SupplierForm) => emit('submit', values))

const handleClose = () => {
    resetForm()
    emit('close')
}
</script>

<template>
    <v-dialog
        max-width="1000"
        :model-value="props.modelValue"
        :persistent="saving"
        scrollable
        @update:model-value="value => emit('update:modelValue', value)"
    >
        <v-card class="supplier-form-card rounded-lg" :loading="saving">
            <!-- Header -->
            <v-toolbar color="transparent" density="comfortable" flat>
                <template #prepend>
                    <v-icon :icon="MdiDomain" class="ms-2" />
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

            <v-form @submit.prevent="submit">
                <v-card-text class="pa-4 pa-sm-5">
                    <!-- Company info -->
                    <section class="form-section mb-4">
                        <header class="section-head">
                            <v-icon :icon="MdiDomain" size="16" />
                            <span>{{ t('generalInfo') }}</span>
                        </header>
                        <v-divider class="mb-3" />
                        <v-row dense>
                            <v-col cols="12" md="6">
                                <v-text-field
                                    v-model="name"
                                    :label="t('name')"
                                    :error-messages="errors.name"
                                    :disabled="saving"
                                    :prepend-inner-icon="MdiDomain"
                                    variant="outlined"
                                    density="comfortable"
                                    hide-details="auto"
                                    autocomplete="off"
                                    maxlength="255"
                                />
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-text-field
                                    v-model="address"
                                    :label="t('address')"
                                    :error-messages="errors.address"
                                    :disabled="saving"
                                    :prepend-inner-icon="MdiMapMarkerOutline"
                                    variant="outlined"
                                    density="comfortable"
                                    hide-details="auto"
                                    autocomplete="off"
                                    maxlength="500"
                                />
                            </v-col>
                            <v-col cols="12" sm="6" md="4">
                                <v-text-field
                                    v-model="phone_number"
                                    type="tel"
                                    inputmode="tel"
                                    :label="t('phoneNumber')"
                                    :error-messages="errors.phone_number"
                                    :disabled="saving"
                                    :prepend-inner-icon="MdiPhoneOutline"
                                    variant="outlined"
                                    density="comfortable"
                                    hide-details="auto"
                                    autocomplete="off"
                                    maxlength="20"
                                    @keypress="numberOnly"
                                />
                            </v-col>
                            <v-col cols="12" sm="6" md="4">
                                <base-npwp-input
                                    v-model="npwp_number"
                                    :label="t('npwpNumber')"
                                    :error-messages="errors.npwp_number"
                                />
                            </v-col>
                            <v-col cols="12" sm="6" md="4">
                                <v-text-field
                                    v-model="email"
                                    type="email"
                                    inputmode="email"
                                    :label="t('email')"
                                    :error-messages="errors.email"
                                    :disabled="saving"
                                    :prepend-inner-icon="MdiEmailOutline"
                                    variant="outlined"
                                    density="comfortable"
                                    hide-details="auto"
                                    autocomplete="off"
                                    maxlength="255"
                                />
                            </v-col>
                        </v-row>
                    </section>

                    <!-- Contact person -->
                    <section class="form-section mb-4">
                        <header class="section-head">
                            <v-icon :icon="MdiAccount" size="16" />
                            <span>{{ t('picName') }}</span>
                        </header>
                        <v-divider class="mb-3" />
                        <v-row dense>
                            <v-col cols="12">
                                <v-text-field
                                    v-model="pic_name"
                                    :label="t('picName')"
                                    :error-messages="errors.pic_name"
                                    :disabled="saving"
                                    :prepend-inner-icon="MdiAccountTie"
                                    variant="outlined"
                                    density="comfortable"
                                    hide-details="auto"
                                    autocomplete="off"
                                    maxlength="255"
                                />
                            </v-col>
                        </v-row>
                    </section>

                    <!-- Payment & tax -->
                    <section class="form-section mb-4">
                        <header class="section-head">
                            <v-icon :icon="MdiCreditCardOutline" size="16" />
                            <span>{{ t('paymentMethods') }} &amp; {{ t('taxTypes') }}</span>
                        </header>
                        <v-divider class="mb-3" />
                        <v-row dense>
                            <v-col cols="12" sm="6" md="4">
                                <base-autocomplete
                                    v-model="payment_method_uid"
                                    :items="paymentMethodItem"
                                    :label="t('paymentMethods')"
                                    :error-messages="errors.payment_method_uid"
                                    :loading="paymentMethodeStore.loading"
                                    :disabled="paymentMethodeStore.loading || saving"
                                    :clearable="true"
                                    @update:model-value="v => (payment_method_uid = v)"
                                />
                            </v-col>
                            <v-col cols="12" sm="6" md="4">
                                <base-autocomplete
                                    v-model="payment_duration_uid"
                                    :items="paymentDurationItem"
                                    :label="t('paymentDuration')"
                                    :error-messages="errors.payment_duration_uid"
                                    :loading="paymentDurationStore.loading"
                                    :disabled="paymentDurationStore.loading || saving"
                                    :clearable="true"
                                    @update:model-value="v => (payment_duration_uid = v)"
                                />
                            </v-col>
                            <v-col cols="12" md="4">
                                <base-autocomplete
                                    v-model="tax_type_uid"
                                    :items="taxTypeItem"
                                    :label="t('taxTypes')"
                                    :error-messages="errors.tax_type_uid"
                                    :loading="taxTypesStore.loading"
                                    :disabled="taxTypesStore.loading || saving"
                                    :clearable="true"
                                    :multiple="true"
                                    @update:model-value="v => (tax_type_uid = v)"
                                />
                            </v-col>
                        </v-row>
                    </section>

                    <!-- Additional info -->
                    <section class="form-section">
                        <header class="section-head">
                            <v-icon :icon="MdiInformationOutline" size="16" />
                            <span>{{ t('additionalInfo') }}</span>
                        </header>
                        <v-divider class="mb-3" />
                        <v-textarea
                            v-model="additional_info"
                            :label="t('additionalInfo')"
                            :error-messages="errors.additional_info"
                            :disabled="saving"
                            :prepend-inner-icon="MdiTextLong"
                            variant="outlined"
                            density="comfortable"
                            hide-details="auto"
                            autocomplete="off"
                            rows="3"
                            auto-grow
                            maxlength="2000"
                        />
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
.supplier-form-card {
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
</style>
