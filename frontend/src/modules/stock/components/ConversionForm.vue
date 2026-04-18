<script lang="ts" setup>
import { onMounted, ref } from 'vue'
// Import Composables
import { useTranslate } from '@/composables/useTranslate'
import { useField, useForm } from 'vee-validate'
// Import Components
import BaseAutocomplete from '@/components/base/BaseAutocomplete.vue'
// Import Validations
import { conversionSchema } from '@/validations/conversionSchema'
import { useStockStore } from '@/stores/stock'
import { useMessageStore } from '@/stores/message'
import { useItemUnitsStore } from '@/stores/item_units'

const props = defineProps<{
    modelValue: boolean,
    data: ConversionForm | null
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void,
    (e: 'submit', value: any): void,
    (e: 'close'): void
}>()

const t = useTranslate()

// Store Definition
const message = useMessageStore()
const stockStore = useStockStore()
const unitStore = useItemUnitsStore()

const loadingForm = ref<boolean>(false)
const baseUnitItem = ref<SelectItem[]>([])
const derivedUnitItem = ref<SelectItem[]>([])
const stock_uid = ref<string | null>(props.data?.stock_uid ?? null)

const { handleSubmit, setValues, resetForm, errors } = useForm<ConversionForm>({
    validationSchema: conversionSchema(t),
})

setValues({
    stock_uid: stock_uid.value,
    base_unit_uid: props.data?.base_unit_uid,
    derived_unit_uid: props.data?.derived_unit_uid,
    convert_qty: props.data?.convert_qty,
    converted_qty: props.data?.converted_qty,
})

const { value: base_unit_uid } = useField('base_unit_uid')
const { value: derived_unit_uid } = useField('derived_unit_uid')
const { value: convert_qty } = useField<number>('convert_qty')
const { value: converted_qty } = useField<number>('converted_qty')

const loadBaseUnitData = async (uid: string | null) => {
    try {
        baseUnitItem.value = []

        if (!uid) return

        await stockStore.getStockUnitByStockUid(uid)

        baseUnitItem.value = stockStore.dataStockUnit.flatMap(stock => {return { title: stock.unit.symbol, value: stock.unit.uid }} )
    } catch (err) {
        console.error("Error :", err)
    }
}

const loadDerivedUnitData = async () => {
    try {
        derivedUnitItem.value = []

        await unitStore.fetch({
            page: 1,
            itemsPerPage: -1,
            sortBy: [{ key: 'created_at', order: 'desc' }],
            search: ''
        })

        derivedUnitItem.value = unitStore.data.map((item) => ({
            title: item.symbol,
            value: item.uid
        }))
    } catch (err) {
        console.error("Error :", err)
    }
}

onMounted(async () => {
    loadingForm.value = true
    console.log("Props Data :", props.data)
    try {
        const uid = props.data?.stock_uid ?? null

        await Promise.all([
            loadBaseUnitData(uid),
            loadDerivedUnitData()
        ])
    } catch {
        message.setMessage({
            text: t('loadingFormError'),
            timeout: 3000,
            color: 'error'
        })
    } finally {
        loadingForm.value = false
    }
})

const handleSelectBaseUnit = async (value: any) => {
    base_unit_uid.value = value
}

const handleSelectDerivedUnit = async (value: any) => {
    derived_unit_uid.value = value
}

const submit = handleSubmit((value: ConversionForm) => {
    emit('submit', value)
})

const handleClose = () => {
    resetForm()
    emit('close')
}
</script>

<template>
    <v-dialog
        :persistent="loadingForm"
        max-width="500"
        :model-value="props.modelValue"
        @update:model-value="async value => emit('update:modelValue', value)"
    >
        <v-card class="rounded-lg">
            <v-card-title>{{ t('conversion') }}</v-card-title>
            <v-form @submit.prevent="submit">
                <v-card-text>
                    <v-row>
                        <v-col cols="12" sm="12" md="12">
                            <v-card>
                                <v-card-text>
                                    <v-row>
                                        <v-col cols="6" sm="6" md="6">
                                            <div class="text-caption text-disabled">{{ t('itemName') }} :</div>
                                            <v-divider class="border-opacity-25 mt-1 mb-2" variant="dotted" content-offset="2rem" />
                                            <div class="text-caption">{{ props.data?.item_name }}</div>
                                        </v-col>
                                        <v-col cols="6" sm="6" md="6">
                                            <div class="text-caption text-disabled">Qty :</div>
                                            <v-divider class="border-opacity-25 mt-1 mb-2" variant="dotted" content-offset="2rem" />
                                            <div class="text-caption">{{ props.data?.current_qty?.map((unit) => { return unit.qty + ' ' + unit.unit_symbol }).join(', ') }}</div>
                                        </v-col>
                                    </v-row>
                                </v-card-text>
                            </v-card>
                        </v-col>
                    </v-row>
                    <v-row>
                        <v-col cols="12" sm="6" md="6">
                            <v-number-input
                                v-model="convert_qty"
                                :label="t('convertQty')"
                                control-variant="stacked"
                                variant="outlined"
                                density="comfortable"
                                :min="0"
                                :error-messages="errors.convert_qty"
                                autocomplete="off"
                            />
                        </v-col>
                        <v-col cols="12" sm="6" md="6">
                            <base-autocomplete
                                :loading="stockStore.loading"
                                :items="baseUnitItem"
                                :label="t('basicUnit')"
                                v-model="base_unit_uid"
                                :error-messages="errors.base_unit_uid"
                                @update:model-value="handleSelectBaseUnit"
                                :disabled="stockStore.loading"
                                :clearable="true"
                            />
                        </v-col>
                        <v-col cols="12" sm="6" md="6">
                            <v-number-input
                                v-model="converted_qty"
                                :label="t('convertedQty')"
                                control-variant="stacked"
                                variant="outlined"
                                density="comfortable"
                                :min="0"
                                :error-messages="errors.converted_qty"
                                autocomplete="off"
                            />
                        </v-col>
                        <v-col cols="12" sm="6" md="6">
                            <base-autocomplete
                                :loading="unitStore.loading"
                                :items="derivedUnitItem"
                                :label="t('derivedUnit')"
                                v-model="derived_unit_uid"
                                :error-messages="errors.derived_unit_uid"
                                @update:model-value="handleSelectDerivedUnit"
                                :disabled="unitStore.loading"
                                :clearable="true"
                            />
                        </v-col>
                    </v-row>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn :text="t('cancel')" @click="handleClose" :disabled="loadingForm" />
                    <v-btn type="submit" :text="t('save')" color="success" :disabled="loadingForm" />
                </v-card-actions>
            </v-form>
        </v-card>
    </v-dialog>
</template>

<style scoped></style>