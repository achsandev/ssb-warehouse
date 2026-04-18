<script lang="ts" setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useField, useForm } from 'vee-validate'
// Import Composables
import { useTranslate } from '@/composables/useTranslate'
// Import Validations
import { stockSchema } from '@/validations'
// Import Stores
import { useWarehouseStore } from '@/stores/warehouse'
import { useRackStore } from '@/stores/rack'
import { useTankStore } from '@/stores/tank'
import { useMessageStore } from '@/stores/message'
// Import Lookup APIs
import { get as getItemsLookup } from '@/api/lookup/item.api'
// Import Components
import BaseAutocomplete from '@/components/base/BaseAutocomplete.vue'

const props = defineProps<{
    modelValue: boolean
    title: string
    data: StockList
    saving?: boolean
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'submit', values: StockForm): void
    (e: 'close'): void
}>()

const t = useTranslate()

// ========================
// Stores
// ========================
const message = useMessageStore()
const warehouseStore = useWarehouseStore()
const rackStore = useRackStore()
const tankStore = useTankStore()

// ========================
// State
// ========================
const loadingForm = ref<boolean>(false)

const itemsRawData   = ref<any[]>([])
const itemOptions    = ref<SelectItem[]>([])
const warehouseOptions = ref<SelectItem[]>([])
const rackOptions    = ref<SelectItem[]>([])
const tankOptions    = ref<SelectItem[]>([])

// ========================
// Form
// ========================
const { handleSubmit, setValues, resetForm, errors } = useForm<StockForm>({
    validationSchema: stockSchema(t),
})

setValues({
    item_uid: props.data.item?.uid ?? '',
    warehouse_uid: props.data.warehouse?.uid ?? '',
    rack_uid: props.data.rack?.uid ?? null,
    tank_uid: props.data.tank?.uid ?? null,
    unit_uid: props.data.unit?.uid ?? '',
    qty: props.data.stock_units?.[0]?.qty ?? null,
})

const { value: item_uid }      = useField<string>('item_uid')
const { value: warehouse_uid } = useField<string>('warehouse_uid')
const { value: rack_uid }      = useField<string | null>('rack_uid')
const { value: tank_uid }      = useField<string | null>('tank_uid')
const { value: unit_uid }      = useField<string>('unit_uid')
const { value: qty }           = useField<number | null>('qty')

// ========================
// Data Loaders
// ========================
const loadItems = async () => {
    try {
        const res = await getItemsLookup()
        itemsRawData.value = res.data
        itemOptions.value = res.data.map((item: any) => ({
            title: item.name,
            value: item.uid,
        }))
    } catch (err) {
        console.error('Error loading items:', err)
    }
}

// Computed unit label (symbol) from selected item
const selectedUnitLabel = computed(() => {
    if (!item_uid.value) return ''
    const found = itemsRawData.value.find((i) => i.uid === item_uid.value)
    return found?.unit ? `${found.unit.symbol} — ${found.unit.name}` : ''
})

const loadWarehouses = async () => {
    try {
        await warehouseStore.fetch({
            page: 1,
            itemsPerPage: -1,
            sortBy: [{ key: 'name', order: 'asc' }],
            search: '',
        })
        warehouseOptions.value = warehouseStore.data.map((w) => ({
            title: w.name,
            value: w.uid,
        }))
    } catch (err) {
        console.error('Error loading warehouses:', err)
    }
}

const loadRacks = async (warehouseUid: string) => {
    try {
        rackOptions.value = []
        if (!warehouseUid) return
        await rackStore.getByUid(warehouseUid, {
            page: 1,
            itemsPerPage: -1,
            sortBy: [{ key: 'name', order: 'asc' }],
            search: '',
        })
        rackOptions.value = rackStore.data.map((r) => ({
            title: r.name,
            value: r.uid,
        }))
    } catch (err) {
        console.error('Error loading racks:', err)
    }
}

const loadTanks = async (warehouseUid: string) => {
    try {
        tankOptions.value = []
        if (!warehouseUid) return
        await tankStore.getByUid(warehouseUid, {
            page: 1,
            itemsPerPage: -1,
            sortBy: [{ key: 'name', order: 'asc' }],
            search: '',
        })
        tankOptions.value = tankStore.data.map((tk) => ({
            title: tk.name,
            value: tk.uid,
        }))
    } catch (err) {
        console.error('Error loading tanks:', err)
    }
}

// ========================
// Cascade: Unit depends on Item
// ========================
// Auto-fill unit when item changes
watch(item_uid, (newVal) => {
    if (!newVal) {
        unit_uid.value = ''
        return
    }
    const found = itemsRawData.value.find((i) => i.uid === newVal)
    unit_uid.value = found?.unit?.uid ?? ''
})

// ========================
// Cascade: Rack & Tank depend on Warehouse
// ========================
watch(warehouse_uid, async (newVal, oldVal) => {
    if (!newVal) {
        rackOptions.value = []
        tankOptions.value = []
        rack_uid.value = null
        tank_uid.value = null
        return
    }
    // Clear child selections only when user actively changes warehouse
    if (oldVal !== undefined && newVal !== oldVal) {
        rack_uid.value = null
        tank_uid.value = null
    }
    await Promise.all([loadRacks(newVal), loadTanks(newVal)])
})

// ========================
// Lifecycle
// ========================
onMounted(async () => {
    loadingForm.value = true
    try {
        const loaders: Promise<any>[] = [
            loadItems(),
            loadWarehouses(),
        ]
        if (props.data.warehouse?.uid) {
            loaders.push(
                loadRacks(props.data.warehouse.uid),
                loadTanks(props.data.warehouse.uid),
            )
        }
        await Promise.all(loaders)
    } catch {
        message.setMessage({
            text: t('loadingFormError'),
            timeout: 3000,
            color: 'error',
        })
    } finally {
        loadingForm.value = false
    }
})

// ========================
// Actions
// ========================
const onSubmit = handleSubmit((values) => {
    emit('submit', values)
})

const onClose = () => {
    resetForm()
    emit('update:modelValue', false)
    emit('close')
}
</script>

<template>
    <v-dialog
        :model-value="modelValue"
        max-width="600"
        :persistent="loadingForm || saving"
        @update:model-value="onClose"
    >
        <v-card :loading="loadingForm">
            <v-card-title class="text-h6 pa-4">
                {{ title }}
            </v-card-title>

            <v-divider />

            <v-card-text class="pa-4">
                <v-form @submit.prevent="onSubmit">
                    <v-row dense>
                        <!-- Item -->
                        <v-col cols="12">
                            <base-autocomplete
                                v-model="item_uid"
                                :label="t('item')"
                                :items="itemOptions"
                                :error-messages="errors.item_uid"
                                :disabled="loadingForm"
                                clearable
                            />
                        </v-col>

                        <!-- Warehouse -->
                        <v-col cols="12">
                            <base-autocomplete
                                v-model="warehouse_uid"
                                :label="t('warehouse')"
                                :items="warehouseOptions"
                                :error-messages="errors.warehouse_uid"
                                :disabled="loadingForm"
                                clearable
                            />
                        </v-col>

                        <!-- Rack (cascaded from Warehouse) -->
                        <v-col cols="12" sm="6">
                            <base-autocomplete
                                v-model="rack_uid"
                                :label="t('rack')"
                                :items="rackOptions"
                                :error-messages="errors.rack_uid"
                                :disabled="loadingForm || !warehouse_uid"
                                clearable
                            />
                        </v-col>

                        <!-- Tank (cascaded from Warehouse) -->
                        <v-col cols="12" sm="6">
                            <base-autocomplete
                                v-model="tank_uid"
                                :label="t('tank')"
                                :items="tankOptions"
                                :error-messages="errors.tank_uid"
                                :disabled="loadingForm || !warehouse_uid"
                                clearable
                            />
                        </v-col>

                        <!-- Unit (auto-filled from item, readonly) -->
                        <v-col cols="12" sm="6">
                            <v-text-field
                                :model-value="selectedUnitLabel"
                                :label="t('unit')"
                                :error-messages="errors.unit_uid"
                                :placeholder="item_uid ? t('noDataAvailable') : t('selectItemFirst')"
                                variant="outlined"
                                density="compact"
                                readonly
                            />
                        </v-col>

                        <!-- Qty -->
                        <v-col cols="12" sm="6">
                            <v-number-input
                                v-model="qty"
                                :label="t('qty')"
                                :error-messages="errors.qty"
                                :disabled="loadingForm"
                                :min="0"
                                control-variant="stacked"
                                variant="outlined"
                                density="compact"
                            />
                        </v-col>
                    </v-row>
                </v-form>
            </v-card-text>

            <v-divider />

            <v-card-actions class="pa-4 justify-end">
                <v-btn
                    variant="text"
                    :disabled="loadingForm || saving"
                    @click="onClose"
                >
                    {{ t('cancel') }}
                </v-btn>
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
