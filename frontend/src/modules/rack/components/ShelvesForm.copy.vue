<script lang="ts" setup>
import { onMounted, ref } from 'vue'
import { useField, useForm } from 'vee-validate'
// Import Composables
import { useTranslate } from '@/composables/useTranslate'
// Import Validations
import { rackSchema } from '@/validations'
// Import Store
import { useWarehouseStore } from '@/stores/warehouse';

const props = defineProps<{
    modelValue: boolean,
    title: string,
    data: RackForm
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void,
    (e: 'submit', values: RackForm): void,
    (e: 'close'): void
}>()

const t = useTranslate()
const warehouseStore = useWarehouseStore()

const warehouseItem = ref<SelectItem[]>([])

const { handleSubmit, setValues, resetForm, errors } = useForm<RackForm>({
    validationSchema: rackSchema(t),
})

setValues({
    warehouse_uid: props.data.warehouse_uid,
    name: props.data.name,
    additional_info: props.data.additional_info ?? ''
})

const { value: name } = useField('name')
const { value: warehouse_uid } = useField('warehouse_uid')
const { value: warehouse_name } = useField('warehouse_name')
const { value: additional_info } = useField('additional_info')

const loadWarehouseData = async () => {
    try {
        warehouseItem.value = []

        await warehouseStore.fetch({
            page: 1,
            itemsPerPage: -1,
            sortBy: [{ key: 'created_at', order: 'desc' }],
            search: ''
        })

        warehouseItem.value = warehouseStore.data.map((item) => ({
            title: item.name,
            value: item.uid
        }))
    } catch (err) {
        console.error("Error :", err)
    }
}

onMounted(async () => {
    await Promise.all([
        loadWarehouseData()
    ])
})

const handleSelectWarehouse = (value: any) => {
    warehouse_uid.value = value

    const selected = warehouseItem.value.find(item => item.value === value)
    warehouse_name.value = selected?.title || ''
}

const submit = handleSubmit((values: RackForm) => {
    emit('submit', values)
})

const handleClose = () => {
    resetForm()
    emit('close')
}
</script>

<template>
    <v-dialog
        max-width="500"
        :model-value="props.modelValue"
        @update:model-value="async value => emit('update:modelValue', value)"
    >
        <v-card class="rounded-lg">
            <v-card-title>{{ props.title }}</v-card-title>
            <v-form @submit.prevent="submit">
                <v-card-text>
                    <v-row>
                        <v-col cols="12">
                            <v-autocomplete
                                :loading="warehouseStore.loading"
                                :items="warehouseItem"
                                :label="t('warehouse')"
                                v-model="warehouse_uid"
                                variant="outlined"
                                density="comfortable"
                                :error-messages="errors.warehouse_uid"
                                @update:model-value="handleSelectWarehouse"
                                autocomplete="off"
                                :disabled="warehouseStore.loading"
                                clearable
                            />
                        </v-col>
                        <v-col cols="12" class="mt-n3">
                            <v-text-field
                                :label="t('name')"
                                v-model="name"
                                variant="outlined"
                                density="comfortable"
                                :error-messages="errors.name"
                                autocomplete="off"
                            />
                        </v-col>
                        <v-col cols="12" class="mt-n3">
                            <v-textarea
                                row-height="30"
                                :label="t('additionalInfo')"
                                v-model="additional_info"
                                variant="outlined"
                                density="comfortable"
                                :error-messages="errors.additional_info"
                                autocomplete="off"
                            />
                        </v-col>
                    </v-row>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn :text="t('cancel')" @click="handleClose" />
                    <v-btn type="submit" :text="t('save')" color="success" />
                </v-card-actions>
            </v-form>
        </v-card>
    </v-dialog>
</template>

<style></style>