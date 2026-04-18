<script lang="ts" setup>
import { computed, onMounted, ref } from 'vue'
import { useField, useForm } from 'vee-validate'
import { useTranslate } from '@/composables/useTranslate'
import { cashPurchaseSchema } from '@/validations/cashPurchaseSchema'
import { warehousesLookup, purchaseOrdersLookup } from '@/api/cash_purchase.api'
import BaseDateInput from '@/components/base/BaseDateInput.vue'
import SystemUiconsClose from '~icons/system-uicons/close'
import dayjs from 'dayjs'

const props = defineProps<{
    modelValue: boolean
    title: string
    data: CashPurchaseList
    saving?: boolean
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'submit', values: CashPurchaseForm): void
    (e: 'close'): void
}>()

const t = useTranslate()

const loadingForm = ref(false)

type WarehouseOption = { uid: string; name: string; cash_balance: number }
type PoOption = {
    uid: string
    po_number: string
    po_date: string
    project_name: string | null
    total_amount: number
    details: { item_name: string; unit_symbol: string; qty: number; price: number; total: number }[]
}

const warehouseOptions = ref<WarehouseOption[]>([])
const poOptions        = ref<PoOption[]>([])

const isEdit = computed(() => !!props.data.uid)
const isBusy = computed(() => loadingForm.value || !!props.saving)

// ─── Form ─────────────────────────────────────────────────────────────────────
const { handleSubmit, setValues, errors } = useForm<CashPurchaseForm>({
    validationSchema: cashPurchaseSchema(t),
})

setValues({
    purchase_date: props.data.purchase_date
        ? dayjs(props.data.purchase_date as string).format('YYYY-MM-DD')
        : dayjs().format('YYYY-MM-DD'),
    warehouse_uid: props.data.warehouse_uid ?? null,
    po_uid:        props.data.po_uid        ?? null,
    notes:         props.data.notes         ?? null,
})

const { value: purchase_date  } = useField<string>('purchase_date')
const { value: warehouse_uid  } = useField<string | null>('warehouse_uid')
const { value: po_uid         } = useField<string | null>('po_uid')
const { value: notes          } = useField<string | null>('notes')

// ─── Derived: selected warehouse & PO ────────────────────────────────────────
const selectedWarehouse = computed(() =>
    warehouseOptions.value.find(w => w.uid === warehouse_uid.value) ?? null,
)

const selectedPo = computed(() =>
    poOptions.value.find(p => p.uid === po_uid.value) ?? null,
)

// ─── Autocomplete items ───────────────────────────────────────────────────────
const warehouseItems = computed(() =>
    warehouseOptions.value.map(w => ({
        title: w.name,
        value: w.uid,
    })),
)

const poItems = computed(() =>
    poOptions.value.map(p => ({
        title: p.project_name ? `${p.po_number} — ${p.project_name}` : p.po_number,
        value: p.uid,
    })),
)

// ─── PO detail table headers ──────────────────────────────────────────────────
const poDetailHeaders = [
    { title: 'No',        value: 'no',          align: 'center' as const, width: '50px', sortable: false },
    { title: t('item'),   value: 'item_name',   align: 'start'  as const, sortable: false },
    { title: t('unit'),   value: 'unit_symbol', align: 'start'  as const, sortable: false },
    { title: t('qty'),    value: 'qty',         align: 'end'    as const, sortable: false },
    { title: t('price'),  value: 'price',       align: 'end'    as const, sortable: false },
    { title: t('total'),  value: 'total',       align: 'end'    as const, sortable: false },
]

// ─── Load lookups ─────────────────────────────────────────────────────────────
const loadLookups = async () => {
    loadingForm.value = true
    try {
        const [wRes, pRes] = await Promise.all([
            warehousesLookup(),
            purchaseOrdersLookup(),
        ])
        warehouseOptions.value = wRes.data?.data ?? wRes.data ?? []
        poOptions.value        = pRes.data?.data ?? pRes.data ?? []
    } catch {
        // silently ignore
    } finally {
        loadingForm.value = false
    }
}

onMounted(loadLookups)

// ─── Submit ───────────────────────────────────────────────────────────────────
const onSubmit = handleSubmit((values) => emit('submit', values))
const onClose  = () => emit('close')
</script>

<template>
    <v-dialog
        :model-value="modelValue"
        max-width="800"
        :persistent="isBusy"
        scrollable
        @update:model-value="onClose"
    >
        <v-card :loading="loadingForm" rounded="lg">

            <!-- Title bar -->
            <v-card-title class="d-flex align-center gap-2 px-6 py-4">
                <span class="text-h6 font-weight-semibold">{{ title }}</span>
                <v-spacer />
                <v-btn
                    :icon="SystemUiconsClose"
                    variant="text"
                    size="small"
                    :disabled="isBusy"
                    @click="onClose"
                />
            </v-card-title>

            <v-divider />

            <v-card-text class="px-6 py-5">
                <v-form @submit.prevent="onSubmit">

                    <!-- Section: General Info -->
                    <div class="section-header mb-3">
                        <span class="text-subtitle-2 font-weight-bold text-primary">{{ t('generalInfo') }}</span>
                    </div>

                    <v-row dense>
                        <!-- Purchase Date -->
                        <v-col cols="12">
                            <base-date-input
                                v-model="purchase_date"
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

                        <!-- Warehouse readonly info -->
                        <template v-if="selectedWarehouse">
                            <v-col cols="12">
                                <v-text-field
                                    :model-value="'Rp ' + Number(selectedWarehouse.cash_balance).toLocaleString('id-ID')"
                                    :label="t('currentCashBalance')"
                                    variant="outlined"
                                    hide-details
                                    readonly
                                />
                            </v-col>
                        </template>

                        <!-- Purchase Order -->
                        <v-col cols="12">
                            <v-autocomplete
                                v-model="po_uid"
                                :items="poItems"
                                item-title="title"
                                item-value="value"
                                :label="t('selectPurchaseOrder')"
                                :placeholder="t('selectPurchaseOrder')"
                                :error-messages="errors.po_uid"
                                :disabled="loadingForm || isEdit"
                                variant="outlined"
                                hide-details="auto"
                                clearable
                            />
                        </v-col>

                        <!-- PO readonly info -->
                        <template v-if="selectedPo">
                            <v-col cols="12">
                                <v-divider class="my-1" />
                                <p class="text-subtitle-2 font-weight-bold text-primary mt-3 mb-2">{{ t('poInformation') }}</p>
                            </v-col>

                            <v-col cols="12" md="6">
                                <v-text-field
                                    :model-value="selectedPo.po_number"
                                    :label="t('poNumber')"
                                    variant="outlined"
                                    density="compact"
                                    hide-details
                                    readonly
                                />
                            </v-col>

                            <v-col cols="12" md="6">
                                <v-text-field
                                    :model-value="selectedPo.po_date ? dayjs(selectedPo.po_date).format('DD MMM YYYY') : '-'"
                                    :label="t('poDate')"
                                    variant="outlined"
                                    density="compact"
                                    hide-details
                                    readonly
                                />
                            </v-col>

                            <v-col cols="12" md="6">
                                <v-text-field
                                    :model-value="selectedPo.project_name || '-'"
                                    :label="t('projectName')"
                                    variant="outlined"
                                    density="compact"
                                    hide-details
                                    readonly
                                />
                            </v-col>

                            <v-col cols="12" md="6">
                                <v-text-field
                                    :model-value="'Rp ' + Number(selectedPo.total_amount).toLocaleString('id-ID')"
                                    :label="t('poTotalAmount')"
                                    variant="outlined"
                                    density="compact"
                                    hide-details
                                    readonly
                                />
                            </v-col>
                        </template>

                        <!-- PO Detail preview table -->
                        <v-col v-if="selectedPo && selectedPo.details?.length" cols="12">
                            <v-divider class="mb-2" />
                            <p class="text-subtitle-2 font-weight-bold mb-2">{{ t('details') }}</p>
                            <v-data-table
                                :headers="poDetailHeaders"
                                :items="selectedPo.details"
                                density="compact"
                                hide-default-footer
                                :items-per-page="-1"
                            >
                                <template #item.no="{ index }">{{ index + 1 }}</template>
                                <template #item.price="{ item: d }">
                                    Rp {{ Number(d.price).toLocaleString('id-ID') }}
                                </template>
                                <template #item.total="{ item: d }">
                                    Rp {{ Number(d.total).toLocaleString('id-ID') }}
                                </template>
                                <template #bottom>
                                    <div class="d-flex justify-end pa-2">
                                        <span class="text-body-2 font-weight-bold">
                                            Total: Rp {{ Number(selectedPo.total_amount).toLocaleString('id-ID') }}
                                        </span>
                                    </div>
                                </template>
                            </v-data-table>
                        </v-col>

                        <!-- Notes -->
                        <v-col cols="12">
                            <v-textarea
                                v-model="notes"
                                :label="t('notes')"
                                :disabled="loadingForm"
                                rows="2"
                                auto-grow
                                variant="outlined"
                                hide-details="auto"
                            />
                        </v-col>
                    </v-row>

                </v-form>
            </v-card-text>

            <v-divider />

            <v-card-actions class="px-6 py-4">
                <v-spacer />
                <v-btn
                    variant="text"
                    :disabled="isBusy"
                    @click="onClose"
                >
                    {{ t('cancel') }}
                </v-btn>
                <v-btn
                    color="primary"
                    variant="flat"
                    :loading="isBusy"
                    @click="onSubmit"
                >
                    {{ t('save') }}
                </v-btn>
            </v-card-actions>

        </v-card>
    </v-dialog>
</template>
