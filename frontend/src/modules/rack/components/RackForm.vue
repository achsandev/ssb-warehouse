<script lang="ts" setup>
import { useField, useForm } from 'vee-validate'
// Import Composables
import { useTranslate } from '@/composables/useTranslate'
// Import Validations
import { rackSchema } from '@/validations'

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

const { handleSubmit, setValues, resetForm, errors } = useForm<RackForm>({
    validationSchema: rackSchema(t),
})

setValues({
    name: props.data.name,
    additional_info: props.data.additional_info ?? ''
})

const { value: name } = useField('name')
const { value: additional_info } = useField('additional_info')

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